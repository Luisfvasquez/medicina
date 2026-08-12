<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Scheduling\ScheduleController;
use App\Http\Controllers\Api\V1\Scheduling\ClinicScheduleController;
use App\Http\Controllers\Api\V1\ServiceController;

Route::prefix('v1')->middleware('auth:user_api')->group(function () {
    // Schedules - Doctor's own schedules
    Route::get('schedules/my', [ScheduleController::class, 'myIndex']);
    Route::post('schedules/my', [ScheduleController::class, 'myStore']);
    Route::put('schedules/my/{id}', [ScheduleController::class, 'myUpdate']);
    Route::delete('schedules/my/{id}', [ScheduleController::class, 'myDestroy']);

    // Schedule Exceptions
    Route::get('schedule-exceptions/my', [ScheduleController::class, 'exceptionsIndex']);
    Route::post('schedule-exceptions/my', [ScheduleController::class, 'exceptionsStore']);
    Route::delete('schedule-exceptions/my/{id}', [ScheduleController::class, 'exceptionsDestroy']);

    // Clinic Schedules
    Route::get('clinic-schedules/{clinicBranch}', [ClinicScheduleController::class, 'show']);
    Route::post('clinic-schedules/{clinicBranch}', [ClinicScheduleController::class, 'store']);
    Route::delete('clinic-schedules/{clinicBranch}/{weekday}', [ClinicScheduleController::class, 'destroy']);

    // Services management
    Route::post('services/provider-services', [ServiceController::class, 'storeProviderService']);
    Route::put('services/provider-services/{uuid}', [ServiceController::class, 'updateProviderService']);
    Route::delete('services/provider-services/{uuid}', [ServiceController::class, 'destroyProviderService']);
});
