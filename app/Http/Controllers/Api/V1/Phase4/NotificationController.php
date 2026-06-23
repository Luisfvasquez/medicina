<?php

namespace App\Http\Controllers\Api\V1\Phase4;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('user_api')->user();

        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $notifications]);
    }

    public function show(string $id): JsonResponse
    {
        $notification = Notification::where('user_id', auth('user_api')->id())
            ->findOrFail($id);

        return response()->json(['data' => $notification]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $notification = Notification::where('user_id', auth('user_api')->id())
            ->findOrFail($id);

        $notification->update(['is_read' => true]);

        return response()->json(['data' => $notification]);
    }

    public function markAllAsRead(): JsonResponse
    {
        Notification::where('user_id', auth('user_api')->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', auth('user_api')->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['data' => ['count' => $count]]);
    }
}
