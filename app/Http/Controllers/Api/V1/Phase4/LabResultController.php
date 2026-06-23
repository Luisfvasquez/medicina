<?php

namespace App\Http\Controllers\Api\V1\Phase4;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LabResult\StoreLabResultRequest;
use App\Http\Requests\Api\V1\LabResult\UpdateLabResultRequest;
use App\Models\LabResult;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabResultController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('user_api')->user();
        $clinicBranchId = $request->query('clinic_branch_id');
        $consultationId = $request->query('consultation_id');

        $results = LabResult::with(['patient', 'labRequest.consultation.clinicBranch', 'reviewedBy'])
            ->when($user->role === 'DOCTOR', fn($q) => $q->whereHas('patient', fn($p) => $p->where('user_id', $user->id)))
            ->when($consultationId, fn($q) => $q->whereHas('labRequest', fn($lr) => $lr->where('consultation_id', $consultationId)))
            ->when($clinicBranchId, fn($q) => $q->whereHas('labRequest.consultation', fn($c) => $c->where('clinic_branch_id', $clinicBranchId)))
            ->latest()
            ->paginate(20);

        // HIPAA: Log VIEW action for accessing lab results
        foreach ($results as $result) {
            AuditLog::logView($user, 'LabResult', $result->id, $result->patient_id);
        }

        return response()->json(['data' => $results]);
    }

    public function show(string $id): JsonResponse
    {
        $user = auth('user_api')->user();
        $result = LabResult::with(['patient', 'labRequest', 'reviewedBy'])->findOrFail($id);

        // HIPAA: Log VIEW action
        AuditLog::logView($user, 'LabResult', $result->id, $result->patient_id);

        return response()->json(['data' => $result]);
    }

    public function store(StoreLabResultRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = LabResult::create($data);

        // HIPAA: Log CREATE action
        AuditLog::logCreate(auth('user_api')->user(), 'LabResult', $result->id, $data, $result->patient_id);

        return response()->json(['data' => $result->load(['patient', 'labRequest'])], 201);
    }

    public function update(UpdateLabResultRequest $request, string $id): JsonResponse
    {
        $user = auth('user_api')->user();
        $result = LabResult::findOrFail($id);
        $oldData = $result->toArray();

        $result->update($request->validated());

        // HIPAA: Log UPDATE action
        AuditLog::logUpdate($user, 'LabResult', $result->id, $oldData, $request->validated(), $result->patient_id);

        return response()->json(['data' => $result->load(['patient', 'labRequest', 'reviewedBy'])]);
    }

    public function markAsReviewed(string $id): JsonResponse
    {
        $user = auth('user_api')->user();
        $result = LabResult::findOrFail($id);

        $result->update([
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'status' => 'COMPLETED',
        ]);

        return response()->json(['data' => $result->load(['patient', 'reviewedBy'])]);
    }
}
