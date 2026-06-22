<?php

namespace App\Http\Controllers\Api\V1\Phase3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Prescription\StorePrescriptionRequest;
use App\Http\Requests\Api\V1\Prescription\UpdatePrescriptionRequest;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('user_api')->user();

        $prescriptions = Prescription::with(['patient', 'user', 'consultation', 'items'])
            ->when($user->role === 'DOCTOR', fn($q) => $q->where('user_id', $user->id))
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
}
