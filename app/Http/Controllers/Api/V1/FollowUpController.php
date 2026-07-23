<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FollowUp\StoreFollowUpRequest;
use App\Http\Requests\Api\V1\FollowUp\UpdateFollowUpRequest;
use App\Models\FollowUp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('user_api')->user();
        $clinicBranchId = $request->query('clinic_branch_id');
        $consultationId = $request->query('consultation_id');

        $followUps = FollowUp::with(['patient', 'user', 'consultation.clinicBranch'])
            ->when($user->role === 'DOCTOR', fn($q) => $q->where('user_id', $user->id))
            ->when($user->role === 'PATIENT', fn($q) => $q->where('patient_id', $user->patient->id ?? null))
            ->when($consultationId, fn($q) => $q->whereHas('consultation', fn($c) => $c->where(is_numeric($consultationId) ? 'id' : 'uuid', $consultationId)))
            ->when($clinicBranchId, fn($q) => $q->whereHas('consultation', fn($c) => is_numeric($clinicBranchId) ? $c->where('clinic_branch_id', $clinicBranchId) : $c->whereHas('clinicBranch', fn($cb) => $cb->where('uuid', $clinicBranchId))))
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $followUps]);
    }

    public function store(StoreFollowUpRequest $request): JsonResponse
    {
        $user = auth('user_api')->user();

        $patient = \App\Models\Patient::where('uuid', $request->patient_uuid)->firstOrFail();
        $consultation = $request->consultation_uuid 
            ? \App\Models\Consultation::where('uuid', $request->consultation_uuid)->first() 
            : null;

        $followUp = FollowUp::create([
            'uuid' => $request->uuid ?? \Illuminate\Support\Str::uuid()->toString(),
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'consultation_id' => $consultation?->id,
            'scheduled_date' => $request->scheduled_date,
            'channel' => $request->channel ?? 'MANUAL_CALL',
            'message_template' => $request->message_template,
            'status' => $request->status ?? \App\Enums\FollowStatus::PENDING->value,
            'response' => $request->response,
        ]);

        return response()->json(['data' => $followUp->load(['patient', 'user', 'consultation'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $followUp = FollowUp::with(['patient', 'user', 'consultation'])
            ->where(is_numeric($id) ? 'id' : 'uuid', $id)
            ->firstOrFail();

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $followUp->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $followUp]);
    }

    public function update(UpdateFollowUpRequest $request, string $id): JsonResponse
    {
        $followUp = FollowUp::where(is_numeric($id) ? 'id' : 'uuid', $id)
            ->firstOrFail();

        $user = auth('user_api')->user();
        if ($user->role === 'DOCTOR' && $followUp->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $updateData = $request->validated();
        if ($request->has('patient_uuid')) {
            $updateData['patient_id'] = \App\Models\Patient::where('uuid', $request->patient_uuid)->firstOrFail()->id;
        }
        if ($request->has('consultation_uuid')) {
            $updateData['consultation_id'] = $request->consultation_uuid 
                ? \App\Models\Consultation::where('uuid', $request->consultation_uuid)->firstOrFail()->id
                : null;
        }

        $followUp->update($updateData);

        return response()->json(['data' => $followUp->fresh()->load(['patient', 'user', 'consultation'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $followUp = FollowUp::where(is_numeric($id) ? 'id' : 'uuid', $id)
            ->firstOrFail();

        $user = auth('user_api')->user();
        if ($user->role !== 'ADMIN' && $followUp->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $followUp->delete();

        return response()->json(null, 204);
    }
}
