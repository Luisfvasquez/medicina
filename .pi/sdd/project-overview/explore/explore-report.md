# SDD Explore — Proyecto LUCA Health OS

**Change**: project-overview
**Date**: 2026-06-30
**Agent**: sdd-explore

---

## Executive Summary

**LUCA Health OS** is a comprehensive medical practice management platform targeting Latin American healthcare providers. It is a Laravel 13.8 / PHP 8.3 REST API with multi-guard JWT authentication separating professional users (doctors, pharmacies, admins) from patients into two distinct auth ecosystems. The backend covers 6 development phases with **170+ API endpoints**, **46 controllers**, **35+ Eloquent models**, **21 PHP enums**, and **50+ database migrations** across domains including patient records (SOAP consultations), appointments/scheduling, prescriptions/vademécum, a B2B2C pharmacy marketplace, billing/invoicing, HIPAA audit logging, and KYC document verification. An offline-first sync endpoint (`POST /api/sync`) enables doctors to work without connectivity, using UUID-based client-generated IDs and last-write-wins conflict resolution. The **frontend is essentially unimplemented** — only a Vite + Tailwind CSS scaffold exists with PDF Blade templates. Testing covers ~5% of the codebase (sync and document upload only). The project has **excellent documentation** (12 spec documents covering API, schema, auth, sync, and frontend architecture) but needs UI implementation, expanded test coverage, and background job/notification infrastructure.

---

## Full Exploration Report

### 1. Project Overview

**LUCA Health OS** is a Health OS (medical practice management platform) for Latin American healthcare. Multi-tenant, multi-role system serving:

- **Doctors** — patient records, SOAP consultations, prescriptions, scheduling, dynamic forms
- **Patients** — appointment booking, own record viewing, prescription QR verification, pharmacy quotes
- **Providers** (Pharmacies/Laboratories) — B2B2C marketplace for medication quotes, inventory
- **Clinics** — multi-branch management with role-based access (owner/admin/doctor/receptionist)
- **Admins** — KYC verification, audit logs, system oversight

### 2. Architecture

#### Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel | ^13.8 |
| PHP | PHP | ^8.3 |
| Auth | tymon/jwt-auth | * (JWT) |
| Sanctum | laravel/sanctum | ^4.0 (likely unused) |
| PDF | barryvdh/laravel-dompdf | * |
| Frontend | Vite + Tailwind CSS | Vite ^8.0, Tailwind ^4.0 |
| Testing | Pest PHP | ^4.7 |
| Database | PostgreSQL (implied) | UUID, JSONB, Enums |

#### Key Patterns

- **Multi-Guard JWT**: `user_api` (doctors/providers/admins) + `patient_api` (patients) — completely separate auth ecosystems
- **RESTful API** at `/api/v1` with 170+ endpoints
- **Idempotency**: All POSTs require `Idempotency-Key` header (UUIDv4)
- **Offline-First Sync**: Unified `POST /api/sync` with push/pull, LWW conflict resolution
- **Soft Deletes** on key tables
- **UUID Public IDs** on all entities
- **PHP 8.1+ Backed Enums** (21 defined)
- **KYC Gate**: `EnsureKycIsApproved` middleware
- **Account Status Lifecycle**: ACTIVE → WARNED → SUSPENDED → BANNED

#### Folder Structure

```
app/
├── Enums/ (21 PHP backed enums)
├── Http/
│   ├── Controllers/Api/V1/ (46 controllers across Auth, Phase3-5, Scheduling)
│   ├── Middleware/ (4: CheckPatientStatus, CheckUserStatus, EnsureIdempotency, EnsureKycIsApproved)
│   └── Requests/ (Form request validators)
├── Models/ (35 Eloquent models)
├── Providers/AppServiceProvider.php
└── Traits/HasPublicUuid.php
database/migrations/ (50+ migrations)
resources/
├── js/app.js (minimal scaffold)
└── views/pdfs/ (4 Blade templates)
routes/api.php (all API routes)
tests/Feature/Api/V1/ (DocumentUploadTest, SyncTest)
```

### 3. Data Model

**35+ models** across 15 domains:

| Domain | Key Models |
|--------|-----------|
| Geography | Country → State → City (normalized) |
| Auth | PatientAccount, User (separate ecosystems) |
| Clinical | Patient, Consultation, VitalSign, LabRequest, LabResult |
| Appointments | Appointment, DoctorSchedule, ScheduleException, ClinicSchedule |
| Prescriptions | Prescription, PrescriptionItem, PrescriptionTemplate, Medication |
| Medical Records | MedicalBackground, SurgicalHistory, FamilyHistory, Lifestyle, ObstetricHistory, Vaccination |
| Forms | FormTemplate (JSONB dynamic forms) |
| Clinics | Clinic, ClinicBranch, ClinicBranchMember |
| Providers | ProviderProfile, ProviderBranch, PharmacyInventory |
| Marketplace | QuoteRequest, QuoteOffer |
| Documents | MedicalDocument, VerificationDocument |
| Follow-up | FollowUp |
| Notifications | Notification |
| Billing | Invoice, InvoiceItem, Payment |
| Audit | AuditLog (HIPAA) |

**21 Enums**: AccountStatus, AppointmentStatus, AuditAction, ClinicRole, ConsultationStatus, DocType, DocVerificationType, ExceptionType, FollowStatus, Gender, InvoiceStatus, LabResultStatus, NotifType, PaymentMethod, PlanType, ProviderType, QuoteStatus, RxStatus, UserRole, VerificationStatus, Weekday

### 4. API Surface

| Phase | Domain | Endpoints | Status |
|-------|--------|-----------|--------|
| Auth | Patient + User auth | ~12 | ✅ |
| Phase 1 | Public catalogs | ~6 | ✅ |
| Phase 2 | Clinical core | ~50 | ✅ |
| Phase 3 | Vademécum & Marketplace | ~35 | ✅ |
| Phase 4 | Operations & Compliance | ~35 | ✅ |
| Phase 5 | Patient Portal + Verification | ~20 | ✅ |
| Phase 6 | Scheduling | ~12 | ✅ |
| Sync/Upload/PDF | Offline sync, uploads, exports | ~8 | ✅ |

**Total: ~170+ API endpoints**

### 5. Frontend

- **Vite + Tailwind CSS 4** configured but **no SPA framework**
- `resources/js/app.js` — empty scaffold
- `resources/views/pdfs/` — 4 PDF templates (consultation, prescription, invoice, medical doc)
- Per frontend guide: planned offline-first with IndexedDB, JWT management, role-based routing

### 6. Authentication

- **JWT via tymon/jwt-auth** (two guards: `user_api`, `patient_api`)
- Custom claims in tokens (id, email, role, isActive for users; id, email, phone for patients)
- 1-hour token expiry with refresh endpoint
- KYC: doctors/providers upload license during registration; `EnsureKycIsApproved` blocks clinical routes until admin approval
- Account status middleware invalidates tokens for BANNED accounts

### 7. Offline Sync

- `POST /api/sync` — single push+pull endpoint
- Client-generated UUIDs, topological processing order
- LWW conflict resolution via `updated_at` comparison
- Max 500 records per pull, paginated via `has_more` + timestamp cursor
- Binary files: metadata in sync JSON, separate upload via `/documents/upload`
- Failed records reported individually (not full transaction rollback)

### 8. Known Gaps

| Area | Gap |
|------|-----|
| Frontend | 0% — no UI components |
| Tests | ~5% — only sync + upload tested |
| Audit auto-logging | No middleware creates audit entries |
| Notification dispatch | No event-driven creation |
| Payment gateway | No Stripe/PayPal integration |
| Queue workers | No background jobs |
| OTP auth | Spec'd but not implemented |
| Phase 5 FKs | `patient_account_id` may need migration |
| Seeders | No demo data |

### 9. Development State

- **Backend**: ~80% structurally complete — routes, controllers, models, migrations all exist for all 6 phases. Business logic depth varies per controller.
- **Frontend**: 0% — scaffold only
- **Testing**: ~5% — minimal coverage
- **Documentation**: Excellent — 12 comprehensive spec documents
