<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;

class PatientNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $notifications = Notification::where('patient_account_id', $patientAccount->id)
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $notifications]);
    }

    public function show(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $notification = Notification::where('patient_account_id', $patientAccount->id)
            ->findOrFail($id);

        return response()->json(['data' => $notification]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $notification = Notification::where('patient_account_id', $patientAccount->id)
            ->findOrFail($id);

        $notification->update(['is_read' => true]);

        return response()->json(['data' => $notification]);
    }

    public function markAllAsRead(): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        Notification::where('patient_account_id', $patientAccount->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function unreadCount(): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $count = Notification::where('patient_account_id', $patientAccount->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['data' => ['count' => $count]]);
    }
}
