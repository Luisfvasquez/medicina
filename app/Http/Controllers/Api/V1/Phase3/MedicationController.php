<?php

namespace App\Http\Controllers\Api\V1\Phase3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Medication\StoreMedicationRequest;
use App\Http\Requests\Api\V1\Medication\UpdateMedicationRequest;
use App\Models\Medication;
use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

class MedicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Medication::with('user')
            ->where(function ($q) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', auth('user_api')->id());
            });

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('active_principle', 'ILIKE', "%{$search}%")
                  ->orWhere('commercial_name', 'ILIKE', "%{$search}%");
            });
        }

        $medications = $query->latest()->paginate(20);

        return response()->json(['data' => $medications]);
    }

    public function store(StoreMedicationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth('user_api')->id();

        $medication = Medication::create($data);

        return response()->json(['data' => $medication->load('user')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $medication = Medication::with('user')->where('uuid', $id)->firstOrFail();

        if ($medication->user_id && $medication->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $medication]);
    }

    public function update(UpdateMedicationRequest $request, string $id): JsonResponse
    {
        $medication = Medication::where('uuid', $id)->firstOrFail();

        if ($medication->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $medication->update($request->validated());

        return response()->json(['data' => $medication->load('user')]);
    }

    public function topPrescribed(Request $request): JsonResponse
    {
        $doctorId = auth('user_api')->id();

        $topMeds = \App\Models\PrescriptionItem::select('medication_id', \DB::raw('count(*) as count'))
            ->whereHas('prescription', function ($q) use ($doctorId) {
                $q->where('user_id', $doctorId);
            })
            ->groupBy('medication_id')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        $results = [];
        $maxCount = 1;
        if ($topMeds->isNotEmpty()) {
            $maxCount = $topMeds->first()->count;
        }

        foreach ($topMeds as $item) {
            $med = Medication::find($item->medication_id);
            if ($med) {
                $results[] = [
                    'name' => $med->commercial_name ?: $med->active_principle,
                    'count' => (int) $item->count,
                    'percentage' => (int) round(($item->count / $maxCount) * 100),
                ];
            }
        }

        if (empty($results)) {
            $meds = Medication::whereNull('user_id')
                ->orWhere('user_id', $doctorId)
                ->limit(3)
                ->get();
            foreach ($meds as $idx => $med) {
                $results[] = [
                    'name' => $med->commercial_name ?: $med->active_principle,
                    'count' => 0,
                    'percentage' => $idx === 0 ? 80 : ($idx === 1 ? 50 : 30),
                ];
            }
        }

        return response()->json(['data' => $results]);
    }

    public function destroy(string $id): JsonResponse
    {
        $medication = Medication::where('uuid', $id)->firstOrFail();

        if ($medication->user_id !== auth('user_api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $medication->delete();

        return response()->json(null, 204);
    }
}
