<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vaccination\StoreVaccinationRequest;
use App\Http\Requests\Api\V1\Vaccination\UpdateVaccinationRequest;
use App\Models\Patient;
use App\Models\Vaccination;
use Illuminate\Http\JsonResponse;

class PatientVaccinationController extends Controller
{
    public function index(string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $vaccinations = $patient->vaccinations()->latest()->paginate(20);

        return response()->json(['data' => $vaccinations]);
    }

    public function store(StoreVaccinationRequest $request, string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);

        $vaccination = Vaccination::create(array_merge($request->validated(), [
            'patient_id' => $patient->id,
        ]));

        return response()->json(['data' => $vaccination], 201);
    }

    public function show(string $patientId, string $id): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $vaccination = $patient->vaccinations()->findOrFail($id);

        return response()->json(['data' => $vaccination]);
    }

    public function update(UpdateVaccinationRequest $request, string $patientId, string $id): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $vaccination = $patient->vaccinations()->findOrFail($id);

        $vaccination->update($request->validated());

        return response()->json(['data' => $vaccination->fresh()]);
    }

    public function destroy(string $patientId, string $id): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $vaccination = $patient->vaccinations()->findOrFail($id);

        $vaccination->delete();

        return response()->json(null, 204);
    }
}
