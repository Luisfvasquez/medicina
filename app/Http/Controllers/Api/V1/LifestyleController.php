<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Lifestyle\StoreLifestyleRequest;
use App\Http\Requests\Api\V1\Lifestyle\UpdateLifestyleRequest;
use App\Models\Patient;
use App\Models\Lifestyle;
use Illuminate\Http\JsonResponse;

class LifestyleController extends Controller
{
    public function show(string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $lifestyle = $patient->lifestyle;

        if (!$lifestyle) {
            return response()->json(['error' => 'Lifestyle not found'], 404);
        }

        return response()->json(['data' => $lifestyle]);
    }

    public function store(StoreLifestyleRequest $request, string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);

        if ($patient->lifestyle) {
            return response()->json(['error' => 'Lifestyle already exists for this patient'], 409);
        }

        $lifestyle = Lifestyle::create(array_merge($request->validated(), [
            'patient_id' => $patient->id,
        ]));

        return response()->json(['data' => $lifestyle], 201);
    }

    public function update(UpdateLifestyleRequest $request, string $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);
        $lifestyle = $patient->lifestyle;

        if (!$lifestyle) {
            return response()->json(['error' => 'Lifestyle not found'], 404);
        }

        $lifestyle->update($request->validated());

        return response()->json(['data' => $lifestyle->fresh()]);
    }
}
