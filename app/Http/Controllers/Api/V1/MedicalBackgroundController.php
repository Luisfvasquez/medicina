<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicalBackground\StoreMedicalBackgroundRequest;
use App\Http\Requests\Api\V1\MedicalBackground\UpdateMedicalBackgroundRequest;
use App\Models\MedicalBackground;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class MedicalBackgroundController extends Controller
{
    public function show(string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $background = $patient->medicalBackground;

        if (!$background) {
            return response()->json(['error' => 'Medical background not found'], 404);
        }

        return response()->json(['data' => $background]);
    }

    public function store(StoreMedicalBackgroundRequest $request, string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);

        if ($patient->medicalBackground) {
            return response()->json(['error' => 'Medical background already exists for this patient'], 409);
        }

        $background = MedicalBackground::create(array_merge($request->validated(), [
            'patient_id' => $patient->id,
        ]));

        return response()->json(['data' => $background], 201);
    }

    public function update(UpdateMedicalBackgroundRequest $request, string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $background = $patient->medicalBackground;

        if (!$background) {
            return response()->json(['error' => 'Medical background not found'], 404);
        }

        $background->update($request->validated());

        return response()->json(['data' => $background->fresh()]);
    }
}
