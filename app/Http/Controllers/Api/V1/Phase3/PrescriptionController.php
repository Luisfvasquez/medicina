<?php

namespace App\Http\Controllers\Api\V1\Phase3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Prescription\StorePrescriptionRequest;
use App\Http\Requests\Api\V1\Prescription\UpdatePrescriptionRequest;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('user_api')->user();
        $clinicBranchId = $request->query('clinic_branch_id');

        $prescriptions = Prescription::with(['patient', 'user', 'consultation', 'items'])
            ->when($user->role === 'DOCTOR', fn($q) => $q->where('user_id', $user->id))
            ->when($clinicBranchId, fn($q) => $q->where('clinic_branch_id', $clinicBranchId))
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $prescriptions]);
    }

    public function store(StorePrescriptionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['public_token'] = $this->generatePublicToken();

        $prescription = DB::transaction(function () use ($data, $request) {
            $prescription = Prescription::create($data);

            if ($request->has('items')) {
                foreach ($request->input('items') as $item) {
                    $item['prescription_id'] = $prescription->id;
                    PrescriptionItem::create($item);
                }
            }

            return $prescription;
        });

        // Run the automatic matching engine in the background
        \App\Jobs\MatchPrescriptionWithInventoryJob::dispatch($prescription);

        return response()->json([
            'data' => $prescription->load(['patient', 'user', 'consultation', 'items']),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $prescription = Prescription::with(['patient', 'user', 'consultation', 'items', 'quoteRequests.offers'])
            ->findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $prescription->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $prescription]);
    }

    public function update(UpdatePrescriptionRequest $request, string $id): JsonResponse
    {
        $prescription = Prescription::findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $prescription->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $prescription->update($request->validated());

        // Ensure QuoteRequest is generated if it doesn't exist
        if (!\App\Models\QuoteRequest::where('prescription_id', $prescription->id)->exists()) {
            $latitude = null;
            $longitude = null;
            $cityId = null;

            if ($prescription->clinic_branch_id) {
                $clinicBranch = \App\Models\ClinicBranch::find($prescription->clinic_branch_id);
                if ($clinicBranch) {
                    $latitude = $clinicBranch->latitude;
                    $longitude = $clinicBranch->longitude;
                    $cityId = $clinicBranch->city_id;
                }
            } else {
                $doctor = \App\Models\User::find($prescription->user_id);
                if ($doctor) {
                    $latitude = $doctor->latitude;
                    $longitude = $doctor->longitude;
                    $cityId = $doctor->city_id;
                }
            }

            if ($latitude && $longitude) {
                \App\Models\QuoteRequest::create([
                    'prescription_id' => $prescription->id,
                    'patient_id' => $prescription->patient_id,
                    'city_id' => $cityId,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'search_radius_km' => 15,
                    'status' => 'OPEN',
                ]);
            }
        }

        return response()->json(['data' => $prescription->load(['patient', 'user', 'consultation', 'items'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $prescription = Prescription::findOrFail($id);

        $user = auth('user_api')->user();
        if ($user->role !== 'ADMIN' && $prescription->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $prescription->delete();

        return response()->json(null, 204);
    }

    // ponytail: 16-char random hex; upgrade to crypto-secure if QR scanning becomes widespread
    private function generatePublicToken(): string
    {
        do {
            $token = Str::random(16);
        } while (Prescription::where('public_token', $token)->exists());

        return $token;
    }
    public function reMatch(string $id): JsonResponse
    {
        $prescription = Prescription::findOrFail($id);

        // Security check could go here depending on auth user (patient vs doctor)
        
        $cacheKey = "rematch_prescription_{$id}";
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return response()->json(['message' => 'Solo puedes solicitar una nueva búsqueda automática cada 24 horas.'], 429);
        }

        \App\Jobs\MatchPrescriptionWithInventoryJob::dispatch($prescription);
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDay());

        return response()->json(['message' => 'Búsqueda iniciada en segundo plano. Te notificaremos si hay nuevos resultados.']);
    }
}
