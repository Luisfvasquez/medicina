<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\SyncController;

Route::prefix('v1')->middleware('auth:user_api,patient_api')->group(function () {
    Route::post('sync', [SyncController::class, 'sync']);

    // Appointments - idempotent store
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::post('appointments', [AppointmentController::class, 'store'])->middleware('idempotent');
    Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
    Route::put('appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::patch('appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy']);
});
