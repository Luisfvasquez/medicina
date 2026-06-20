<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FamilyHistory\StoreFamilyHistoryRequest;
use App\Http\Requests\Api\V1\FamilyHistory\UpdateFamilyHistoryRequest;
use App\Models\Patient;
use App\Models\FamilyHistory;
use Illuminate\Http\JsonResponse;

class PatientFamilyHistoryController extends Controller
{
    public function index(string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $histories = $patient->familyHistories()->latest()->paginate(20);

        return response()->json(['data' => $histories]);
    }

    public function store(StoreFamilyHistoryRequest $request, string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);

        $history = FamilyHistory::create(array_merge($request->validated(), [
            'patient_id' => $patient->id,
        ]));

        return response()->json(['data' => $history], 201);
    }

    public function show(string $patientId, string $id): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $history = $patient->familyHistories()->findOrFail($id);

        return response()->json(['data' => $history]);
    }

    public function update(UpdateFamilyHistoryRequest $request, string $patientId, string $id): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $history = $patient->familyHistories()->findOrFail($id);

        $history->update($request->validated());

        return response()->json(['data' => $history->fresh()]);
    }

    public function destroy(string $patientId, string $id): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $history = $patient->familyHistories()->findOrFail($id);

        $history->delete();

        return response()->json(null, 204);
    }
}
