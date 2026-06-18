<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\PatientAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAuthController;

Route::prefix('v1/auth')->group(function () {
    // Patients
    Route::prefix('patients')->group(function () {
        Route::post('register', [PatientAuthController::class, 'register']);
        Route::post('login', [PatientAuthController::class, 'login']);

        Route::middleware('auth:patient_api')->group(function () {
            Route::post('logout', [PatientAuthController::class, 'logout']);
            Route::post('refresh', [PatientAuthController::class, 'refresh']);
            Route::get('me', [PatientAuthController::class, 'me']);
        });
    });

    // Users (Doctors, Providers, Admins)
    Route::prefix('users')->group(function () {
        Route::post('register/doctor', [UserAuthController::class, 'registerDoctor']);
        Route::post('register/provider', [UserAuthController::class, 'registerProvider']);
        Route::post('login', [UserAuthController::class, 'login']);

        Route::middleware('auth:user_api')->group(function () {
            Route::post('logout', [UserAuthController::class, 'logout']);
            Route::post('refresh', [UserAuthController::class, 'refresh']);
            Route::get('me', [UserAuthController::class, 'me']);
        });
    });
});
