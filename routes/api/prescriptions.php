<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Phase3\MedicationController;
use App\Http\Controllers\Api\V1\Phase3\PrescriptionController;
use App\Http\Controllers\Api\V1\Phase3\PrescriptionTemplateController;
use App\Http\Controllers\Api\V1\Phase5\PdfExportController;

Route::prefix('v1')->middleware(['auth:user_api', 'user.status'])->group(function () {
    // Phase 3: Medications / Vademécum
    Route::get('medications/top-prescribed', [MedicationController::class, 'topPrescribed']);
    Route::get('medications/stats', [MedicationController::class, 'stats']);
    Route::get('medications', [MedicationController::class, 'index']);
    Route::post('medications', [MedicationController::class, 'store'])->middleware('idempotent');
    Route::get('medications/{medication}', [MedicationController::class, 'show']);
    Route::put('medications/{medication}', [MedicationController::class, 'update']);
    Route::patch('medications/{medication}', [MedicationController::class, 'update']);
    Route::delete('medications/{medication}', [MedicationController::class, 'destroy']);

    // Phase 3: Prescriptions
    Route::get('prescriptions', [PrescriptionController::class, 'index']);
    Route::post('prescriptions', [PrescriptionController::class, 'store'])->middleware('idempotent');
    Route::get('prescriptions/{prescription}', [PrescriptionController::class, 'show']);
    Route::post('prescriptions/{prescription}/rematch', [PrescriptionController::class, 'reMatch']);
    Route::put('prescriptions/{prescription}', [PrescriptionController::class, 'update']);
    Route::patch('prescriptions/{prescription}', [PrescriptionController::class, 'update']);
    Route::delete('prescriptions/{prescription}', [PrescriptionController::class, 'destroy']);
    Route::get('prescriptions/{prescription}/pdf', [PdfExportController::class, 'prescription']);

    // Phase 3: Prescription Templates
    Route::get('prescription-templates', [PrescriptionTemplateController::class, 'index']);
    Route::post('prescription-templates', [PrescriptionTemplateController::class, 'store'])->middleware('idempotent');
    Route::get('prescription-templates/{prescription_template}', [PrescriptionTemplateController::class, 'show']);
    Route::put('prescription-templates/{prescription_template}', [PrescriptionTemplateController::class, 'update']);
    Route::patch('prescription-templates/{prescription_template}', [PrescriptionTemplateController::class, 'update']);
    Route::delete('prescription-templates/{prescription_template}', [PrescriptionTemplateController::class, 'destroy']);
});
