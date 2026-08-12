<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Phase4\LabResultController as Phase4LabResultController;
use App\Http\Controllers\Api\V1\LabSettingsController;
use App\Http\Controllers\Api\V1\LabQuoteController;
use App\Http\Controllers\Api\V1\LabAppointmentController;
use App\Http\Controllers\Api\V1\LabResultController;
use App\Http\Controllers\Api\V1\ExternalLabOrderController;
use App\Http\Controllers\Api\V1\LabAnalyticsController;

Route::prefix('v1')->middleware(['auth:user_api', 'user.status'])->group(function () {
    // Phase 4: Lab Results
    Route::get('lab-results', [Phase4LabResultController::class, 'index']);
    Route::post('lab-results', [Phase4LabResultController::class, 'store'])->middleware('idempotent');
    Route::get('lab-results/{lab_result}', [Phase4LabResultController::class, 'show']);
    Route::put('lab-results/{lab_result}', [Phase4LabResultController::class, 'update']);
    Route::patch('lab-results/{lab_result}', [Phase4LabResultController::class, 'update']);
    Route::post('lab-results/{lab_result}/review', [Phase4LabResultController::class, 'markAsReviewed']);

    // ─── PHARMAKO / LABORATORY MODULE ROUTES ─────────────────
    Route::prefix('laboratory')->group(function () {
        Route::get('settings', [LabSettingsController::class, 'show']);
        Route::put('settings', [LabSettingsController::class, 'update']);

        Route::get('requests', [LabQuoteController::class, 'index']);
        Route::post('requests/{requestId}/quotes', [LabQuoteController::class, 'store']);
        Route::post('quotes/{offerId}/accept', [LabQuoteController::class, 'accept']);

        Route::post('appointments/book', [LabAppointmentController::class, 'book']);
        Route::get('appointments', [LabAppointmentController::class, 'index']);

        Route::get('results', [LabResultController::class, 'index']);
        Route::post('results', [LabResultController::class, 'store']);

        Route::post('external-orders', [ExternalLabOrderController::class, 'store']);
        Route::get('analytics/metrics', [LabAnalyticsController::class, 'getMetrics']);
    });
});
