<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LabRequest\StoreLabRequestRequest;
use App\Http\Requests\Api\V1\LabRequest\UpdateLabRequestRequest;
use App\Models\Consultation;
use App\Models\LabRequest;
use Illuminate\Http\JsonResponse;

class ConsultationLabRequestController extends Controller
{
    public function store(StoreLabRequestRequest $request, string $consultationId): JsonResponse
    {
        $consultation = Consultation::findOrFail($consultationId);

        $user = auth('user_api')->user();
        if ($consultation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $labRequest = LabRequest::create(array_merge($request->validated(), [
            'consultation_id' => $consultation->id,
        ]));

        return response()->json(['data' => $labRequest], 201);
    }

    public function show(string $consultationId): JsonResponse
    {
        $consultation = Consultation::with('labRequests')->findOrFail($consultationId);

        return response()->json(['data' => $consultation->labRequests]);
    }

    public function update(UpdateLabRequestRequest $request, string $consultationId, string $labRequestId): JsonResponse
    {
        $consultation = Consultation::findOrFail($consultationId);

        $user = auth('user_api')->user();
        if ($consultation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $labRequest = LabRequest::where('uuid', $labRequestId)
            ->where('consultation_id', $consultation->id)
            ->firstOrFail();

        $labRequest->update($request->validated());

        return response()->json(['data' => $labRequest->fresh()]);
    }
}
