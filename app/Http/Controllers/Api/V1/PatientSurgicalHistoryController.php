<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SurgicalHistory\StoreSurgicalHistoryRequest;
use App\Http\Requests\Api\V1\SurgicalHistory\UpdateSurgicalHistoryRequest;
use App\Models\Patient;
use App\Models\SurgicalHistory;
use Illuminate\Http\JsonResponse;

class PatientSurgicalHistoryController extends Controller
{
    public function index(string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $histories = $patient->surgicalHistories()->latest()->paginate(20);

        return response()->json(['data' => $histories]);
    }

    public function store(StoreSurgicalHistoryRequest $request, string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);

        $history = SurgicalHistory::create(array_merge($request->validated(), [
            'patient_id' => $patient->id,
        ]));

        return response()->json(['data' => $history], 201);
    }

    public function show(string $patientId, string $id): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $history = $patient->surgicalHistories()->findOrFail($id);

        return response()->json(['data' => $history]);
    }

    public function update(UpdateSurgicalHistoryRequest $request, string $patientId, string $id): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $history = $patient->surgicalHistories()->findOrFail($id);

        $history->update($request->validated());

        return response()->json(['data' => $history->fresh()]);
    }

    public function destroy(string $patientId, string $id): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $history = $patient->surgicalHistories()->findOrFail($id);

        $history->delete();

        return response()->json(null, 204);
    }
}
