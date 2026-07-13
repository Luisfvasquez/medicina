<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LabRequest\StoreLabRequestRequest;
use App\Http\Requests\Api\V1\LabRequest\UpdateLabRequestRequest;
use App\Models\Consultation;
use App\Models\LabRequest;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('user_api')->user();
        $patientUuid = $request->query('patient_uuid');

        $query = LabRequest::with(['patient', 'consultation'])
            ->where('user_id', $user->id);

        if ($patientUuid) {
            $query->whereHas('patient', function ($q) use ($patientUuid) {
                $q->where('uuid', $patientUuid);
            });
        }

        $labRequests = $query->latest()->get();

        return response()->json(['data' => $labRequests]);
    }

    public function store(StoreLabRequestRequest $request): JsonResponse
    {
        $user = auth('user_api')->user();
        $data = $request->validated();

        $patient = Patient::where('uuid', $data['patient_uuid'])->firstOrFail();
        
        $consultationId = null;
        if (!empty($data['consultation_uuid'])) {
            $consultation = Consultation::where('uuid', $data['consultation_uuid'])->firstOrFail();
            $consultationId = $consultation->id;
        }

        $labRequest = LabRequest::create([
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'consultation_id' => $consultationId,
            'exams_list' => $data['exams_list'],
            'instructions' => $data['instructions'] ?? null,
            'is_completed' => $data['is_completed'] ?? false,
        ]);

        return response()->json([
            'data' => $labRequest->load(['patient', 'consultation']),
        ], 201);
    }

    public function show(string $uuid): JsonResponse
    {
        $user = auth('user_api')->user();
        
        $labRequest = LabRequest::with(['patient', 'consultation'])
            ->where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json(['data' => $labRequest]);
    }

    public function update(UpdateLabRequestRequest $request, string $uuid): JsonResponse
    {
        $user = auth('user_api')->user();
        
        $labRequest = LabRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $labRequest->update($request->validated());

        return response()->json([
            'data' => $labRequest->load(['patient', 'consultation']),
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $user = auth('user_api')->user();
        
        $labRequest = LabRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $labRequest->delete();

        return response()->json(null, 204);
    }
}
