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
use App\Http\Controllers\Api\V1\Phase3\MedicationController;
use App\Http\Controllers\Api\V1\Phase3\PrescriptionController;
use App\Http\Controllers\Api\V1\Phase3\PrescriptionTemplateController;
use App\Http\Controllers\Api\V1\Phase3\MedicalDocumentController;
use App\Http\Controllers\Api\V1\Phase3\QuoteRequestController;
use App\Http\Controllers\Api\V1\Phase3\QuoteOfferController;

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
    });
});
