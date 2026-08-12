<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\DocumentUploadController;
use App\Http\Controllers\Api\V1\StorageController;
use App\Http\Controllers\Api\V1\Phase3\MedicalDocumentController;
use App\Http\Controllers\Api\V1\Phase4\VerificationDocumentController;
use App\Http\Controllers\Api\V1\Phase5\PdfExportController;

Route::prefix('v1')->middleware(['auth:user_api', 'user.status'])->group(function () {
    // Document upload (offline-first binary upload)
    Route::post('documents/upload', [DocumentUploadController::class, 'upload']);
    
    // Generic Storage Upload (Invoices, etc)
    Route::post('storage/upload', [StorageController::class, 'upload']);

    // Phase 3: Medical Documents
    Route::get('medical-documents', [MedicalDocumentController::class, 'index']);
    Route::post('medical-documents', [MedicalDocumentController::class, 'store'])->middleware('idempotent');
    Route::get('medical-documents/{medical_document}', [MedicalDocumentController::class, 'show']);
    Route::put('medical-documents/{medical_document}', [MedicalDocumentController::class, 'update']);
    Route::patch('medical-documents/{medical_document}', [MedicalDocumentController::class, 'update']);
    Route::delete('medical-documents/{medical_document}', [MedicalDocumentController::class, 'destroy']);
    Route::get('medical-documents/{medical_document}/pdf', [PdfExportController::class, 'medicalDocument']);

    // Phase 4: Verification Documents
    Route::get('verification-documents', [VerificationDocumentController::class, 'index']);
    Route::post('verification-documents', [VerificationDocumentController::class, 'store'])->middleware('idempotent');
    Route::get('verification-documents/{verification_document}', [VerificationDocumentController::class, 'show']);
    Route::put('verification-documents/{verification_document}', [VerificationDocumentController::class, 'update']);
    Route::patch('verification-documents/{verification_document}', [VerificationDocumentController::class, 'update']);
    
    // PDF Exports for Consultation
    Route::get('consultations/{consultation}/pdf', [PdfExportController::class, 'consultation']);
});
