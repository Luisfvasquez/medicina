<?php

namespace App\Http\Controllers\Api\V1\Phase4;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    // HIPAA: No POST - logs are created automatically
    // HIPAA: No DELETE - logs are immutable

    public function index(): JsonResponse
    {
        $user = auth('user_api')->user();

        // Only ADMIN can view all audit logs
        if ($user->role !== 'ADMIN') {
            return response()->json(['error' => 'Unauthorized - Admin access required'], 403);
        }

        $query = AuditLog::with(['user', 'patient']);

        // Filter by user
        if (request('user_id')) {
            $query->where('user_id', request('user_id'));
        }

        // Filter by patient
        if (request('patient_id')) {
            $query->where('patient_id', request('patient_id'));
        }

        // Filter by action
        if (request('action')) {
            $query->where('action', request('action'));
        }

        // Filter by resource type
        if (request('resource_type')) {
            $query->where('resource_type', request('resource_type'));
        }

        // Filter by date range
        if (request('from')) {
            $query->where('created_at', '>=', request('from'));
        }
        if (request('to')) {
            $query->where('created_at', '<=', request('to'));
        }

        $logs = $query->latest()->paginate(50);

        return response()->json(['data' => $logs]);
    }

    public function show(string $id): JsonResponse
    {
        $user = auth('user_api')->user();

        if ($user->role !== 'ADMIN') {
            return response()->json(['error' => 'Unauthorized - Admin access required'], 403);
        }

        $log = AuditLog::with(['user', 'patient'])->findOrFail($id);

        return response()->json(['data' => $log]);
    }

    public function patientHistory(string $patientId): JsonResponse
    {
        $user = auth('user_api')->user();

        if ($user->role !== 'ADMIN') {
            return response()->json(['error' => 'Unauthorized - Admin access required'], 403);
        }

        $logs = AuditLog::with(['user'])
            ->where('patient_id', $patientId)
            ->latest()
            ->paginate(50);

        return response()->json(['data' => $logs]);
    }
}
