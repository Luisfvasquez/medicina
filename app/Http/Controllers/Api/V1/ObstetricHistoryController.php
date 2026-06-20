<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ObstetricHistory\StoreObstetricHistoryRequest;
use App\Http\Requests\Api\V1\ObstetricHistory\UpdateObstetricHistoryRequest;
use App\Models\Patient;
use App\Models\ObstetricHistory;
use Illuminate\Http\JsonResponse;

class ObstetricHistoryController extends Controller
{
    public function show(string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $history = $patient->obstetricHistory;

        if (!$history) {
            return response()->json(['error' => 'Obstetric history not found'], 404);
        }

        return response()->json(['data' => $history]);
    }

    public function store(StoreObstetricHistoryRequest $request, string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);

        if ($patient->obstetricHistory) {
            return response()->json(['error' => 'Obstetric history already exists for this patient'], 409);
        }

        $history = ObstetricHistory::create(array_merge($request->validated(), [
            'patient_id' => $patient->id,
        ]));

        return response()->json(['data' => $history], 201);
    }

    public function update(UpdateObstetricHistoryRequest $request, string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $history = $patient->obstetricHistory;

        if (!$history) {
            return response()->json(['error' => 'Obstetric history not found'], 404);
        }

        $history->update($request->validated());

        return response()->json(['data' => $history->fresh()]);
    }
}
