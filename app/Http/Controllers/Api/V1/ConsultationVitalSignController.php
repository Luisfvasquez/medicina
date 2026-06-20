<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\VitalSign\StoreVitalSignRequest;
use App\Http\Requests\Api\V1\VitalSign\UpdateVitalSignRequest;
use App\Models\Consultation;
use App\Models\VitalSign;
use Illuminate\Http\JsonResponse;

class ConsultationVitalSignController extends Controller
{
    public function store(StoreVitalSignRequest $request, string $consultationId): JsonResponse
    {
        $consultation = Consultation::findOrFail($consultationId);

        $user = auth('user_api')->user();
        if ($consultation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($consultation->vitalSign) {
            return response()->json(['error' => 'Vital sign already exists for this consultation'], 409);
        }

        $vitalSign = VitalSign::create(array_merge($request->validated(), [
            'consultation_id' => $consultation->id,
            'patient_id' => $consultation->patient_id,
        ]));

        return response()->json(['data' => $vitalSign], 201);
    }

    public function show(string $consultationId): JsonResponse
    {
        $consultation = Consultation::with('vitalSign')->findOrFail($consultationId);

        if (!$consultation->vitalSign) {
            return response()->json(['error' => 'Vital sign not found'], 404);
        }

        return response()->json(['data' => $consultation->vitalSign]);
    }

    public function update(UpdateVitalSignRequest $request, string $consultationId): JsonResponse
    {
        $consultation = Consultation::findOrFail($consultationId);

        if (!$consultation->vitalSign) {
            return response()->json(['error' => 'Vital sign not found'], 404);
        }

        $user = auth('user_api')->user();
        if ($consultation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $consultation->vitalSign->update($request->validated());

        return response()->json(['data' => $consultation->vitalSign->fresh()]);
    }
}
