# Phase 5: Patient API Access — Task Breakdown

## Migrations

- [ ] 5.1 `add_patient_account_id_to_consultations` — FK + index
- [ ] 5.2 `add_patient_account_id_to_invoices` — FK + index
- [ ] 5.3 `add_patient_account_id_to_medical_documents` — FK + index
- [ ] 5.4 `add_patient_account_id_and_index_to_notifications` — FK + index + unique constraint removal consideration

## Model Updates

- [ ] 5.5 `PatientAccount.php` — change `hasOne` to `hasMany(Patient::class)`
- [ ] 5.6 `Consultation.php` — add `patientAccount()` relationship + fillable + casts
- [ ] 5.7 `Invoice.php` — add `patientAccount()` relationship + fillable + casts
- [ ] 5.8 `MedicalDocument.php` — add `patientAccount()` relationship + fillable
- [ ] 5.9 `Notification.php` — add `patientAccount()` relationship + fillable + casts

## Controllers — Patient Portal

- [ ] 5.10 `PatientAppointmentController.php` — index, show
- [ ] 5.11 `PatientConsultationController.php` — index, show
- [ ] 5.12 `PatientPrescriptionController.php` — index, show
- [ ] 5.13 `PatientQuoteRequestController.php` — index, show, offers (nested)
- [ ] 5.14 `PatientLabResultController.php` — index, show
- [ ] 5.15 `PatientInvoiceController.php` — index, show, payments (nested)
- [ ] 5.16 `PatientNotificationController.php` — index, show, markAsRead, markAllAsRead, unreadCount
- [ ] 5.17 `PatientMedicalDocumentController.php` — index, show

## Controllers — Public Verification

- [ ] 5.18 `VerifyController.php` — verifyPrescription (public), verifyDocument (public)

## Form Requests

- [ ] 5.19 All Store/Update form requests for above controllers (mostly read-only for patient)

## Routes

- [ ] 5.20 Add all patient_api routes under `/patients/me`
- [ ] 5.21 Add public verify routes

## Documentation

- [ ] 5.22 Update `frontend-implementation-guide.md` — add patient portal section
- [ ] 5.23 Update `api_phase4_documentation.md` — note patient access routes

## Verification

- [ ] 5.24 Code review all new files
- [ ] 5.25 Verify all routes match spec
