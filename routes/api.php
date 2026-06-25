<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\Auth\PatientAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAuthController;
use App\Http\Controllers\Api\V1\ConsultationController;
use App\Http\Controllers\Api\V1\ConsultationLabRequestController;
use App\Http\Controllers\Api\V1\ConsultationVitalSignController;
use App\Http\Controllers\Api\V1\FollowUpController;
use App\Http\Controllers\Api\V1\FormTemplateController;
use App\Http\Controllers\Api\V1\LifestyleController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\MedicalBackgroundController;
use App\Http\Controllers\Api\V1\ObstetricHistoryController;
use App\Http\Controllers\Api\V1\PatientFamilyHistoryController;
use App\Http\Controllers\Api\V1\PatientSurgicalHistoryController;
use App\Http\Controllers\Api\V1\PatientVaccinationController;
use App\Http\Controllers\Api\V1\DocumentUploadController;
use App\Http\Controllers\Api\V1\Phase3\MedicalDocumentController;
use App\Http\Controllers\Api\V1\Phase3\MedicationController;
use App\Http\Controllers\Api\V1\Phase3\PrescriptionController;
use App\Http\Controllers\Api\V1\Phase3\PrescriptionTemplateController;
use App\Http\Controllers\Api\V1\Phase3\QuoteOfferController;
use App\Http\Controllers\Api\V1\Phase3\QuoteRequestController;
use App\Http\Controllers\Api\V1\Phase4\AuditLogController;
use App\Http\Controllers\Api\V1\Phase4\InvoiceController;
use App\Http\Controllers\Api\V1\Phase4\InvoiceItemController;
use App\Http\Controllers\Api\V1\Phase4\LabResultController;
use App\Http\Controllers\Api\V1\Phase4\NotificationController;
use App\Http\Controllers\Api\V1\Phase4\PaymentController;
use App\Http\Controllers\Api\V1\Phase4\PharmacyInventoryController;
use App\Http\Controllers\Api\V1\Phase4\VerificationDocumentController;
use App\Http\Controllers\Api\V1\Phase5\PatientAppointmentController;
use App\Http\Controllers\Api\V1\Phase5\PatientConsultationController;
use App\Http\Controllers\Api\V1\Phase5\PatientInvoiceController;
use App\Http\Controllers\Api\V1\Phase5\PatientLabResultController;
use App\Http\Controllers\Api\V1\Phase5\PatientMedicalDocumentController;
use App\Http\Controllers\Api\V1\Phase5\PatientNotificationController;
use App\Http\Controllers\Api\V1\Phase5\PatientPrescriptionController;
use App\Http\Controllers\Api\V1\Phase5\PatientQuoteRequestController;
use App\Http\Controllers\Api\V1\Phase5\PdfExportController;
use App\Http\Controllers\Api\V1\Phase5\VerifyController;
use App\Http\Controllers\Api\V1\PublicCatalogController;
use App\Http\Controllers\Api\V1\Scheduling\ScheduleController;
use App\Http\Controllers\Api\V1\Scheduling\ClinicScheduleController;
use App\Http\Controllers\Api\V1\SpecialtyController;
use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Support\Facades\Route;

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

    // Public Catalog (no auth required)
    Route::get('public/doctors', [PublicCatalogController::class, 'doctors']);
    Route::get('public/pharmacies', [PublicCatalogController::class, 'pharmacies']);
    Route::get('public/clinics', [PublicCatalogController::class, 'clinics']);
    Route::get('public/doctors/{doctor}/availability', [PublicCatalogController::class, 'doctorAvailability']);

    Route::middleware('auth:user_api')->group(function () {
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

        // Sync (offline-first bulk push/pull)
        // Sync (offline-first bulk push/pull)
        Route::post('sync', [SyncController::class, 'sync']);

        // Appointments - idempotent store
        Route::get('appointments', [AppointmentController::class, 'index']);
        Route::post('appointments', [AppointmentController::class, 'store'])->middleware('idempotent');
        Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
        Route::put('appointments/{appointment}', [AppointmentController::class, 'update']);
        Route::patch('appointments/{appointment}', [AppointmentController::class, 'update']);
        Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy']);

        // FormTemplates - idempotent store
        Route::get('form-templates', [FormTemplateController::class, 'index']);
        Route::post('form-templates', [FormTemplateController::class, 'store'])->middleware('idempotent');
        Route::get('form-templates/{form_template}', [FormTemplateController::class, 'show']);
        Route::put('form-templates/{form_template}', [FormTemplateController::class, 'update']);
        Route::patch('form-templates/{form_template}', [FormTemplateController::class, 'update']);
        Route::delete('form-templates/{form_template}', [FormTemplateController::class, 'destroy']);

        // Consultations - idempotent store
        Route::get('consultations', [ConsultationController::class, 'index']);
        Route::post('consultations', [ConsultationController::class, 'store'])->middleware('idempotent');
        Route::get('consultations/{consultation}', [ConsultationController::class, 'show']);
        Route::put('consultations/{consultation}', [ConsultationController::class, 'update']);
        Route::patch('consultations/{consultation}', [ConsultationController::class, 'update']);
        Route::delete('consultations/{consultation}', [ConsultationController::class, 'destroy']);

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

        // Phase 3: Medications / Vademécum
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
        Route::put('prescriptions/{prescription}', [PrescriptionController::class, 'update']);
        Route::patch('prescriptions/{prescription}', [PrescriptionController::class, 'update']);
        Route::delete('prescriptions/{prescription}', [PrescriptionController::class, 'destroy']);

        // Phase 3: Prescription Templates
        Route::get('prescription-templates', [PrescriptionTemplateController::class, 'index']);
        Route::post('prescription-templates', [PrescriptionTemplateController::class, 'store'])->middleware('idempotent');
        Route::get('prescription-templates/{prescription_template}', [PrescriptionTemplateController::class, 'show']);
        Route::put('prescription-templates/{prescription_template}', [PrescriptionTemplateController::class, 'update']);
        Route::patch('prescription-templates/{prescription_template}', [PrescriptionTemplateController::class, 'update']);
        Route::delete('prescription-templates/{prescription_template}', [PrescriptionTemplateController::class, 'destroy']);

        // Document upload (offline-first binary upload)
        Route::post('documents/upload', [DocumentUploadController::class, 'upload']);

        // Phase 3: Medical Documents
        Route::get('medical-documents', [MedicalDocumentController::class, 'index']);
        Route::post('medical-documents', [MedicalDocumentController::class, 'store'])->middleware('idempotent');
        Route::get('medical-documents/{medical_document}', [MedicalDocumentController::class, 'show']);
        Route::put('medical-documents/{medical_document}', [MedicalDocumentController::class, 'update']);
        Route::patch('medical-documents/{medical_document}', [MedicalDocumentController::class, 'update']);
        Route::delete('medical-documents/{medical_document}', [MedicalDocumentController::class, 'destroy']);

        // Phase 3: Quote Requests
        Route::get('quote-requests', [QuoteRequestController::class, 'index']);
        Route::post('quote-requests', [QuoteRequestController::class, 'store'])->middleware('idempotent');
        Route::get('quote-requests/{quote_request}', [QuoteRequestController::class, 'show']);
        Route::put('quote-requests/{quote_request}', [QuoteRequestController::class, 'update']);
        Route::patch('quote-requests/{quote_request}', [QuoteRequestController::class, 'update']);
        Route::delete('quote-requests/{quote_request}', [QuoteRequestController::class, 'destroy']);

        // Phase 3: Quote Offers (nested under quote-requests)
        Route::get('quote-requests/{quote_request}/offers', [QuoteOfferController::class, 'index']);
        Route::post('quote-requests/{quote_request}/offers', [QuoteOfferController::class, 'store'])->middleware('idempotent');
        Route::get('quote-requests/{quote_request}/offers/{offer}', [QuoteOfferController::class, 'show']);
        Route::put('quote-requests/{quote_request}/offers/{offer}', [QuoteOfferController::class, 'update']);
        Route::patch('quote-requests/{quote_request}/offers/{offer}', [QuoteOfferController::class, 'update']);
        Route::delete('quote-requests/{quote_request}/offers/{offer}', [QuoteOfferController::class, 'destroy']);

        // Phase 4: Notifications (user's own)
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/{notification}', [NotificationController::class, 'show']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);

        // Phase 4: Verification Documents
        Route::get('verification-documents', [VerificationDocumentController::class, 'index']);
        Route::post('verification-documents', [VerificationDocumentController::class, 'store'])->middleware('idempotent');
        Route::get('verification-documents/{verification_document}', [VerificationDocumentController::class, 'show']);
        Route::put('verification-documents/{verification_document}', [VerificationDocumentController::class, 'update']);
        Route::patch('verification-documents/{verification_document}', [VerificationDocumentController::class, 'update']);

        // Phase 4: Lab Results
        Route::get('lab-results', [LabResultController::class, 'index']);
        Route::post('lab-results', [LabResultController::class, 'store'])->middleware('idempotent');
        Route::get('lab-results/{lab_result}', [LabResultController::class, 'show']);
        Route::put('lab-results/{lab_result}', [LabResultController::class, 'update']);
        Route::patch('lab-results/{lab_result}', [LabResultController::class, 'update']);
        Route::post('lab-results/{lab_result}/review', [LabResultController::class, 'markAsReviewed']);

        // Phase 4: Pharmacy Inventory
        Route::get('pharmacy-inventories', [PharmacyInventoryController::class, 'index']);
        Route::post('pharmacy-inventories', [PharmacyInventoryController::class, 'store'])->middleware('idempotent');
        Route::get('pharmacy-inventories/{pharmacy_inventory}', [PharmacyInventoryController::class, 'show']);
        Route::put('pharmacy-inventories/{pharmacy_inventory}', [PharmacyInventoryController::class, 'update']);
        Route::patch('pharmacy-inventories/{pharmacy_inventory}', [PharmacyInventoryController::class, 'update']);
        Route::delete('pharmacy-inventories/{pharmacy_inventory}', [PharmacyInventoryController::class, 'destroy']);
        Route::get('pharmacy-inventories/alerts/low-stock', [PharmacyInventoryController::class, 'lowStockAlerts']);
        Route::get('pharmacy-inventories/alerts/expired', [PharmacyInventoryController::class, 'expired']);

        // Phase 4: Invoices
        Route::get('invoices', [InvoiceController::class, 'index']);
        Route::post('invoices', [InvoiceController::class, 'store'])->middleware('idempotent');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update']);
        Route::patch('invoices/{invoice}', [InvoiceController::class, 'update']);
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy']);
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);

        // Phase 4: Invoice Items (nested under invoices)
        Route::get('invoices/{invoice}/items', [InvoiceItemController::class, 'index']);
        Route::post('invoices/{invoice}/items', [InvoiceItemController::class, 'store'])->middleware('idempotent');
        Route::get('invoices/{invoice}/items/{item}', [InvoiceItemController::class, 'show']);
        Route::delete('invoices/{invoice}/items/{item}', [InvoiceItemController::class, 'destroy']);

        // Phase 4: Payments (nested under invoices)
        Route::get('invoices/{invoice}/payments', [PaymentController::class, 'index']);
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->middleware('idempotent');
        Route::get('invoices/{invoice}/payments/{payment}', [PaymentController::class, 'show']);
        Route::delete('invoices/{invoice}/payments/{payment}', [PaymentController::class, 'destroy']);

        // Phase 4: Audit Logs (admin only - no create/delete)
        Route::get('audit-logs', [AuditLogController::class, 'index']);
        Route::get('audit-logs/{audit_log}', [AuditLogController::class, 'show']);
        Route::get('audit-logs/patient/{patient_id}', [AuditLogController::class, 'patientHistory']);

        // PDF Exports
        Route::get('consultations/{consultation}/pdf', [PdfExportController::class, 'consultation']);
        Route::get('prescriptions/{prescription}/pdf', [PdfExportController::class, 'prescription']);
        Route::get('invoices/{invoice}/pdf', [PdfExportController::class, 'invoice']);
        Route::get('medical-documents/{medical_document}/pdf', [PdfExportController::class, 'medicalDocument']);
    });

    // Phase 5: Patient Portal (auth:patient_api)
    Route::prefix('patients/me')->middleware('auth:patient_api')->group(function () {
        Route::get('appointments', [PatientAppointmentController::class, 'index']);
        Route::get('appointments/{appointment}', [PatientAppointmentController::class, 'show']);

        Route::get('consultations', [PatientConsultationController::class, 'index']);
        Route::get('consultations/{consultation}', [PatientConsultationController::class, 'show']);

        Route::get('prescriptions', [PatientPrescriptionController::class, 'index']);
        Route::get('prescriptions/{prescription}', [PatientPrescriptionController::class, 'show']);

        Route::get('quote-requests', [PatientQuoteRequestController::class, 'index']);
        Route::get('quote-requests/{quote_request}', [PatientQuoteRequestController::class, 'show']);
        Route::get('quote-requests/{quote_request}/offers', [PatientQuoteRequestController::class, 'offers']);

        Route::get('lab-results', [PatientLabResultController::class, 'index']);
        Route::get('lab-results/{lab_result}', [PatientLabResultController::class, 'show']);

        Route::get('invoices', [PatientInvoiceController::class, 'index']);
        Route::get('invoices/{invoice}', [PatientInvoiceController::class, 'show']);
        Route::get('invoices/{invoice}/payments', [PatientInvoiceController::class, 'payments']);

        Route::get('notifications', [PatientNotificationController::class, 'index']);
        Route::get('notifications/{notification}', [PatientNotificationController::class, 'show']);
        Route::patch('notifications/{notification}/read', [PatientNotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [PatientNotificationController::class, 'markAllAsRead']);
        Route::get('notifications/unread-count', [PatientNotificationController::class, 'unreadCount']);

        Route::get('medical-documents', [PatientMedicalDocumentController::class, 'index']);
        Route::get('medical-documents/{medical_document}', [PatientMedicalDocumentController::class, 'show']);
    });

    // Phase 5: Public Verification (no auth)
    Route::prefix('verify')->group(function () {
        Route::get('prescription/{publicToken}', [VerifyController::class, 'verifyPrescription']);
        Route::get('document/{publicToken}', [VerifyController::class, 'verifyDocument']);
    });
});
