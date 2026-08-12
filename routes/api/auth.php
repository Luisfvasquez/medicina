<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Auth\PatientAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAuthController;

Route::prefix('v1/auth')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('send-otp', [OtpController::class, 'send']);
        Route::post('verify-otp', [OtpController::class, 'verify']);
        Route::post('login-password', [AuthController::class, 'loginPassword']);
    });

    Route::middleware('auth:user_api,patient_api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    Route::prefix('users')->group(function () {
        Route::middleware(['idempotent', 'throttle:auth'])->group(function () {
            Route::post('register/doctor', [UserAuthController::class, 'registerDoctor']);
            Route::post('register/provider', [UserAuthController::class, 'registerProvider']);
            Route::post('login', [UserAuthController::class, 'login'])
                ->middleware('deprecated:Use POST /api/v1/auth/login-password');
        });

        Route::middleware('auth:user_api')->group(function () {
            Route::post('logout', [UserAuthController::class, 'logout'])
                ->middleware('deprecated:Use POST /api/v1/auth/logout');
            Route::post('refresh', [UserAuthController::class, 'refresh'])
                ->middleware('deprecated:Use POST /api/v1/auth/logout then login again');
            Route::get('me', [UserAuthController::class, 'me'])
                ->middleware('deprecated:Use GET /api/v1/auth/me');
            Route::patch('me', [UserAuthController::class, 'updateProfile']);
        });
    });

    Route::prefix('patients')->group(function () {
        Route::middleware(['idempotent', 'throttle:auth'])->group(function () {
            Route::post('register', [PatientAuthController::class, 'register']);
            Route::post('login', [PatientAuthController::class, 'login'])
                ->middleware('deprecated:Use POST /api/v1/auth/login-password');
        });

        Route::middleware('auth:patient_api')->group(function () {
            Route::post('logout', [PatientAuthController::class, 'logout'])
                ->middleware('deprecated:Use POST /api/v1/auth/logout');
            Route::post('refresh', [PatientAuthController::class, 'refresh'])
                ->middleware('deprecated:Use POST /api/v1/auth/logout then login again');
            Route::get('me', [PatientAuthController::class, 'me'])
                ->middleware('deprecated:Use GET /api/v1/auth/me');
            Route::patch('me', [PatientAuthController::class, 'updateProfile']);
        });
    });
});
