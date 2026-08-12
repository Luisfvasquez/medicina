<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\DoctorDashboardController;
use App\Http\Controllers\Api\V1\FormTemplateController;
use App\Http\Controllers\Api\V1\PatientFormRequestController;
use App\Http\Controllers\Api\V1\ConsultationController;
use App\Http\Controllers\Api\V1\ServiceAttachmentController;
use App\Http\Controllers\Api\V1\FollowUpController;
use App\Http\Controllers\Api\V1\ConsultationVitalSignController;
use App\Http\Controllers\Api\V1\ConsultationLabRequestController;
use App\Http\Controllers\Api\V1\LabRequestController;

Route::prefix('v1')->middleware(['auth:user_api', 'user.status'])->group(function () {
    Route::get('doctor/dashboard', [DoctorDashboardController::class, 'index']);

    // FormTemplates - idempotent store
    Route::get('form-templates', [FormTemplateController::class, 'index']);
    Route::post('form-templates', [FormTemplateController::class, 'store'])->middleware('idempotent');
    Route::post('form-templates/share', [PatientFormRequestController::class, 'share']);
    Route::get('form-templates/{form_template}', [FormTemplateController::class, 'show']);
    Route::put('form-templates/{form_template}', [FormTemplateController::class, 'update']);
    Route::patch('form-templates/{form_template}', [FormTemplateController::class, 'update']);
    Route::delete('form-templates/{form_template}', [FormTemplateController::class, 'destroy']);

    // Consultations - idempotent store
    Route::post('consultations/service-attachments/upload', [ServiceAttachmentController::class, 'upload']);
    Route::get('consultations', [ConsultationController::class, 'index']);
    Route::post('consultations', [ConsultationController::class, 'store'])->middleware('idempotent');
    Route::get('consultations/{consultation}', [ConsultationController::class, 'show']);
    Route::put('consultations/{consultation}', [ConsultationController::class, 'update']);
    Route::patch('consultations/{consultation}', [ConsultationController::class, 'update']);
    Route::delete('consultations/{consultation}', [ConsultationController::class, 'destroy']);
    Route::get('consultations/{consultation}/services/{service_index}/download-attachments', [ConsultationController::class, 'downloadServiceAttachments']);

    // FollowUps - idempotent store
    Route::get('follow-ups', [FollowUpController::class, 'index']);
    Route::post('follow-ups', [FollowUpController::class, 'store'])->middleware('idempotent');
    Route::get('follow-ups/{follow_up}', [FollowUpController::class, 'show']);
    Route::put('follow-ups/{follow_up}', [FollowUpController::class, 'update']);
    Route::patch('follow-ups/{follow_up}', [FollowUpController::class, 'update']);
    Route::delete('follow-ups/{follow_up}', [FollowUpController::class, 'destroy']);

    // Nested: VitalSigns (consultation) - idempotent store
    Route::get('consultations/{consultation}/vital-signs', [ConsultationVitalSignController::class, 'show']);
    Route::post('consultations/{consultation}/vital-signs', [ConsultationVitalSignController::class, 'store'])->middleware('idempotent');
    Route::put('consultations/{consultation}/vital-signs/{vital_sign}', [ConsultationVitalSignController::class, 'update']);
    Route::patch('consultations/{consultation}/vital-signs/{vital_sign}', [ConsultationVitalSignController::class, 'update']);

    // Nested: LabRequests (consultation) - idempotent store
    Route::get('consultations/{consultation}/lab-requests', [ConsultationLabRequestController::class, 'show']);
    Route::post('consultations/{consultation}/lab-requests', [ConsultationLabRequestController::class, 'store'])->middleware('idempotent');
    Route::put('consultations/{consultation}/lab-requests/{lab_request}', [ConsultationLabRequestController::class, 'update']);
    Route::patch('consultations/{consultation}/lab-requests/{lab_request}', [ConsultationLabRequestController::class, 'update']);

    // Standalone: LabRequests CRUD - idempotent store
    Route::get('lab-requests', [LabRequestController::class, 'index']);
    Route::post('lab-requests', [LabRequestController::class, 'store'])->middleware('idempotent');
    Route::get('lab-requests/{lab_request}', [LabRequestController::class, 'show']);
    Route::put('lab-requests/{lab_request}', [LabRequestController::class, 'update']);
    Route::patch('lab-requests/{lab_request}', [LabRequestController::class, 'update']);
    Route::delete('lab-requests/{lab_request}', [LabRequestController::class, 'destroy']);
});
