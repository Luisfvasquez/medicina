<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Phase4\NotificationController;
use App\Http\Controllers\Api\V1\Phase4\AuditLogController;

Route::prefix('v1')->middleware(['auth:user_api', 'user.status'])->group(function () {
    // Phase 4: Notifications (user's own)
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('notifications/{notification}', [NotificationController::class, 'show']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Phase 4: Audit Logs (admin only - no create/delete)
    Route::get('audit-logs', [AuditLogController::class, 'index']);
    Route::get('audit-logs/{audit_log}', [AuditLogController::class, 'show']);
    Route::get('audit-logs/patient/{patient_id}', [AuditLogController::class, 'patientHistory']);
});

Route::get('/delete-null-medications', function () {
    $provider = \App\Models\ProviderProfile::where('uuid', '56ee72a4-f3a7-449a-aa1a-d93feb44d884')->first();
    if ($provider) {
        $deleted = \App\Models\PharmacyInventory::where('provider_id', $provider->id)
                    ->whereNull('medication_id')
                    ->delete();
        return "Deleted $deleted records for provider {$provider->id}.";
    }
    return "Provider not found.";
});
