<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\MedicalBackgroundController;
use App\Http\Controllers\Api\V1\LifestyleController;
use App\Http\Controllers\Api\V1\ObstetricHistoryController;
use App\Http\Controllers\Api\V1\PatientSurgicalHistoryController;
use App\Http\Controllers\Api\V1\PatientFamilyHistoryController;
use App\Http\Controllers\Api\V1\PatientVaccinationController;
use App\Http\Controllers\Api\V1\PatientController;

Route::prefix('v1')->middleware(['auth:user_api', 'user.status'])->group(function () {
    // Patient-scoped: MedicalBackground - idempotent store
    Route::get('patients/{patient}/medical-background', [MedicalBackgroundController::class, 'show']);
    Route::post('patients/{patient}/medical-background', [MedicalBackgroundController::class, 'store'])->middleware('idempotent');
    Route::put('patients/{patient}/medical-background', [MedicalBackgroundController::class, 'update']);
    Route::patch('patients/{patient}/medical-background', [MedicalBackgroundController::class, 'update']);

    // Patient-scoped: Lifestyle - idempotent store
    Route::get('patients/{patient}/lifestyle', [LifestyleController::class, 'show']);
    Route::post('patients/{patient}/lifestyle', [LifestyleController::class, 'store'])->middleware('idempotent');
    Route::put('patients/{patient}/lifestyle', [LifestyleController::class, 'update']);
    Route::patch('patients/{patient}/lifestyle', [LifestyleController::class, 'update']);

    // Patient-scoped: ObstetricHistory - idempotent store
    Route::get('patients/{patient}/obstetric-history', [ObstetricHistoryController::class, 'show']);
    Route::post('patients/{patient}/obstetric-history', [ObstetricHistoryController::class, 'store'])->middleware('idempotent');
    Route::put('patients/{patient}/obstetric-history', [ObstetricHistoryController::class, 'update']);
    Route::patch('patients/{patient}/obstetric-history', [ObstetricHistoryController::class, 'update']);

    // Patient-scoped: SurgicalHistories - idempotent store
    Route::get('patients/{patient}/surgical-histories', [PatientSurgicalHistoryController::class, 'index']);
    Route::post('patients/{patient}/surgical-histories', [PatientSurgicalHistoryController::class, 'store'])->middleware('idempotent');
    Route::get('patients/{patient}/surgical-histories/{surgical_history}', [PatientSurgicalHistoryController::class, 'show']);
    Route::put('patients/{patient}/surgical-histories/{surgical_history}', [PatientSurgicalHistoryController::class, 'update']);
    Route::patch('patients/{patient}/surgical-histories/{surgical_history}', [PatientSurgicalHistoryController::class, 'update']);
    Route::delete('patients/{patient}/surgical-histories/{surgical_history}', [PatientSurgicalHistoryController::class, 'destroy']);

    // Patient-scoped: FamilyHistories - idempotent store
    Route::get('patients/{patient}/family-histories', [PatientFamilyHistoryController::class, 'index']);
    Route::post('patients/{patient}/family-histories', [PatientFamilyHistoryController::class, 'store'])->middleware('idempotent');
    Route::get('patients/{patient}/family-histories/{family_history}', [PatientFamilyHistoryController::class, 'show']);
    Route::put('patients/{patient}/family-histories/{family_history}', [PatientFamilyHistoryController::class, 'update']);
    Route::patch('patients/{patient}/family-histories/{family_history}', [PatientFamilyHistoryController::class, 'update']);
    Route::delete('patients/{patient}/family-histories/{family_history}', [PatientFamilyHistoryController::class, 'destroy']);

    // Patient-scoped: Vaccinations - idempotent store
    Route::get('patients/{patient}/vaccinations', [PatientVaccinationController::class, 'index']);
    Route::post('patients/{patient}/vaccinations', [PatientVaccinationController::class, 'store'])->middleware('idempotent');
    Route::get('patients/{patient}/vaccinations/{vaccination}', [PatientVaccinationController::class, 'show']);
    Route::put('patients/{patient}/vaccinations/{vaccination}', [PatientVaccinationController::class, 'update']);
    Route::patch('patients/{patient}/vaccinations/{vaccination}', [PatientVaccinationController::class, 'update']);
    Route::delete('patients/{patient}/vaccinations/{vaccination}', [PatientVaccinationController::class, 'destroy']);

    // Patients CRUD
    Route::get('patients', [PatientController::class, 'index']);
    Route::post('patients', [PatientController::class, 'store'])->middleware('idempotent');
    Route::get('patients/{patient}', [PatientController::class, 'show']);
    Route::put('patients/{patient}', [PatientController::class, 'update']);
    Route::patch('patients/{patient}', [PatientController::class, 'update']);
    Route::delete('patients/{patient}', [PatientController::class, 'destroy']);
});
