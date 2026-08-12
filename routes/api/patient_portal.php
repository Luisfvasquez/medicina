<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Phase5\PatientDashboardController;
use App\Http\Controllers\Api\V1\PatientFormRequestController;
use App\Http\Controllers\Api\V1\Phase5\PatientAppointmentController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\Phase5\PatientConsultationController;
use App\Http\Controllers\Api\V1\Phase5\PatientPrescriptionController;
use App\Http\Controllers\Api\V1\Phase5\PatientQuoteRequestController;
use App\Http\Controllers\Api\V1\Phase5\PatientCheckoutController;
use App\Http\Controllers\Api\V1\Phase5\PatientLabResultController;
use App\Http\Controllers\Api\V1\Phase5\PatientInvoiceController;
use App\Http\Controllers\Api\V1\Phase5\PatientNotificationController;
use App\Http\Controllers\Api\V1\Phase5\PatientMedicalDocumentController;

Route::prefix('v1')->group(function () {
    // Phase 5: Patient Portal (auth:patient_api)
    Route::prefix('patients/me')->middleware(['auth:patient_api', 'patient.status'])->group(function () {
        Route::get('dashboard', [PatientDashboardController::class, 'index']);
        
        // Form Requests (auto-llenado de plantillas)
        Route::get('form-requests', [PatientFormRequestController::class, 'patientIndex']);
        Route::get('form-requests/{uuid}', [PatientFormRequestController::class, 'patientShow']);
        Route::post('form-requests/{uuid}/submit', [PatientFormRequestController::class, 'patientSubmit']);

        Route::get('appointments', [PatientAppointmentController::class, 'index']);
        Route::post('appointments', [AppointmentController::class, 'store'])->middleware('idempotent');
        Route::get('appointments/{appointment}', [PatientAppointmentController::class, 'show']);

        Route::get('consultations', [PatientConsultationController::class, 'index']);
        Route::get('consultations/{consultation}', [PatientConsultationController::class, 'show']);

        Route::get('prescriptions', [PatientPrescriptionController::class, 'index']);
        Route::get('prescriptions/{prescription}', [PatientPrescriptionController::class, 'show']);

        Route::get('quote-requests', [PatientQuoteRequestController::class, 'index']);
        Route::get('quote-requests/{quote_request}', [PatientQuoteRequestController::class, 'show']);
        Route::get('quote-requests/{quote_request}/offers', [PatientQuoteRequestController::class, 'offers']);
        Route::post('quote-offers/{offer_id}/checkout', [PatientCheckoutController::class, 'checkout']);

        Route::get('lab-results', [PatientLabResultController::class, 'index']);
        Route::get('lab-results/{lab_result}', [PatientLabResultController::class, 'show']);

        Route::get('invoices', [PatientInvoiceController::class, 'index']);
        Route::get('invoices/{invoice}', [PatientInvoiceController::class, 'show']);
        Route::get('invoices/{invoice}/payments', [PatientInvoiceController::class, 'payments']);
        Route::post('invoices/{invoice}/payments', [PatientInvoiceController::class, 'storePayment']);

        Route::get('notifications', [PatientNotificationController::class, 'index']);
        Route::get('notifications/unread-count', [PatientNotificationController::class, 'unreadCount']);
        Route::get('notifications/{notification}', [PatientNotificationController::class, 'show']);
        Route::patch('notifications/{notification}/read', [PatientNotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [PatientNotificationController::class, 'markAllAsRead']);

        Route::get('medical-documents', [PatientMedicalDocumentController::class, 'index']);
        Route::get('medical-documents/{medical_document}', [PatientMedicalDocumentController::class, 'show']);
    });
});
