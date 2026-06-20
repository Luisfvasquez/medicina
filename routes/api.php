<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\PatientAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAuthController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\SpecialtyController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\FormTemplateController;
use App\Http\Controllers\Api\V1\ConsultationController;
use App\Http\Controllers\Api\V1\ConsultationVitalSignController;
use App\Http\Controllers\Api\V1\ConsultationLabRequestController;
use App\Http\Controllers\Api\V1\MedicalBackgroundController;
use App\Http\Controllers\Api\V1\LifestyleController;
use App\Http\Controllers\Api\V1\ObstetricHistoryController;
use App\Http\Controllers\Api\V1\PatientSurgicalHistoryController;
use App\Http\Controllers\Api\V1\PatientFamilyHistoryController;
use App\Http\Controllers\Api\V1\PatientVaccinationController;
use App\Http\Controllers\Api\V1\FollowUpController;

Route::prefix('v1/auth')->group(function () {
    Route::prefix('patients')->group(function () {
        Route::middleware('idempotent')->group(function () {
            Route::post('register', [PatientAuthController::class, 'register']);
            Route::post('login', [PatientAuthController::class, 'login']);
        });

        Route::middleware('auth:patient_api')->group(function () {
            Route::post('logout', [PatientAuthController::class, 'logout']);
            Route::post('refresh', [PatientAuthController::class, 'refresh']);
            Route::get('me', [PatientAuthController::class, 'me']);
        });
    });

    Route::prefix('users')->group(function () {
        Route::middleware('idempotent')->group(function () {
            Route::post('register/doctor', [UserAuthController::class, 'registerDoctor']);
            Route::post('register/provider', [UserAuthController::class, 'registerProvider']);
            Route::post('login', [UserAuthController::class, 'login']);
        });

        Route::middleware('auth:user_api')->group(function () {
            Route::post('logout', [UserAuthController::class, 'logout']);
            Route::post('refresh', [UserAuthController::class, 'refresh']);
            Route::get('me', [UserAuthController::class, 'me']);
        });
    });
});

Route::prefix('v1')->group(function () {
    Route::get('locations/cities', [LocationController::class, 'cities']);
    Route::get('specialties', [SpecialtyController::class, 'index']);

    Route::middleware('auth:user_api')->group(function () {
        Route::apiResource('appointments', AppointmentController::class);
        Route::apiResource('form-templates', FormTemplateController::class);
        Route::apiResource('consultations', ConsultationController::class);
        Route::apiResource('follow-ups', FollowUpController::class);

        Route::apiResource('consultations.vital-signs', ConsultationVitalSignController::class)->only(['store', 'show', 'update']);
        Route::apiResource('consultations.lab-requests', ConsultationLabRequestController::class)->only(['store', 'show', 'update']);

        Route::get('patients/{patient}/medical-background', [MedicalBackgroundController::class, 'show']);
        Route::put('patients/{patient}/medical-background', [MedicalBackgroundController::class, 'update']);
        Route::post('patients/{patient}/medical-background', [MedicalBackgroundController::class, 'store']);

        Route::get('patients/{patient}/lifestyle', [LifestyleController::class, 'show']);
        Route::put('patients/{patient}/lifestyle', [LifestyleController::class, 'update']);
        Route::post('patients/{patient}/lifestyle', [LifestyleController::class, 'store']);

        Route::get('patients/{patient}/obstetric-history', [ObstetricHistoryController::class, 'show']);
        Route::put('patients/{patient}/obstetric-history', [ObstetricHistoryController::class, 'update']);
        Route::post('patients/{patient}/obstetric-history', [ObstetricHistoryController::class, 'store']);

        Route::apiResource('patients.surgical-histories', PatientSurgicalHistoryController::class);
        Route::apiResource('patients.family-histories', PatientFamilyHistoryController::class);
        Route::apiResource('patients.vaccinations', PatientVaccinationController::class);
    });
});
