# LUCA Health OS — Phase 5: Patient API Access

> **Patient Portal — Self-Service Data Access**

Versión: 1.0
Fecha: 2026-06-23
Change: patient-api-access

---

## 1. Concept & Vision

El paciente (PatientAccount) necesita acceso directo a SUS datos clínicos a través de la app. Actualmente solo tiene endpoints de autenticación. Phase 5 le da acceso completo a sus consultas, recetas, ofertas de farmacias, resultados de laboratorio, facturas y notificaciones — todo bajo `auth:patient_api`.

La experiencia: el paciente abre su app, ve sus próximas citas, sus recetas activas con el QR, las cotizaciones de farmacias que respondieron a sus pedidos, sus resultados de laboratorio, y sus facturas — todo en un portal unificado.

---

## 2. Arquitectura de Acceso

### 2.1 Modelo de datos actual

```
PatientAccount (login JWT)
  └── hasOne Patient (ficha médica del doctor, optional link)
        └── belongsTo User (doctor)
```

**Problema**: Un PatientAccount puede tener MÚLTIPLES Patient records (uno por cada doctor que lo atiende). Navegar PatientAccount → Patient → clinical data es posible pero requiere joins adicionales.

### 2.2 Decisión arquitectónica: FK directa

Se agrega `patient_account_id` como FK directa en los recursos clínicos del paciente. Esto permite acceso directo sin joins.

**Tablas a modificar:**
- `consultations` — agregar `patient_account_id`
- `prescriptions` — ya tiene `patient_id` que tiene `patient_account_id` (navegable)
- `invoices` — agregar `patient_account_id`
- `lab_results` — ya tiene `patient_id` (navegable)
- `medical_documents` — agregar `patient_account_id`
- `notifications` — YA tiene `user_id` (el paciente recibe notificaciones)

**No requieren cambios de schema:**
- `Prescription` → navega `patient.patientAccount`
- `LabResult` → navega `patient.patientAccount`
- `Notification` → belongsTo `User` (doctor), el paciente ve las SUYAS propias

### 2.3 Guard: patient_api

Los nuevos endpoints vivirán bajo `auth:patient_api`. El token del paciente tiene claims de `PatientAccount` (id, email, phone).

---

## 3. Rutas y Endpoints

### 3.1 Autenticación (ya existe)
```
POST   /auth/patients/register        ✅
POST   /auth/patients/login          ✅
POST   /auth/patients/logout          ✅
POST   /auth/patients/refresh        ✅
GET    /auth/patients/me             ✅
```

### 3.2 NUEVOS — Patient Portal

```
# Mis Citas
GET    /patients/me/appointments              # Citas del paciente
GET    /patients/me/appointments/{id}

# Mis Consultas (nuevo)
GET    /patients/me/consultations             # Solo sus consultas
GET    /patients/me/consultations/{id}

# Mis Recetas (nuevo)
GET    /patients/me/prescriptions            # Solo sus recetas
GET    /patients/me/prescriptions/{id}

# Mis Ofertas de Cotización (nuevo)
GET    /patients/me/quote-requests           # Sus solicitudes
GET    /patients/me/quote-requests/{id}
GET    /patients/me/quote-requests/{id}/offers  # Ofertas de farmacias

# Mis Resultados de Laboratorio (nuevo)
GET    /patients/me/lab-results              # Solo sus resultados
GET    /patients/me/lab-results/{id}

# Mis Facturas (nuevo)
GET    /patients/me/invoices                # Solo sus facturas
GET    /patients/me/invoices/{id}
GET    /patients/me/invoices/{id}/payments  # Pagos de una factura

# Mis Documentos Médicos (nuevo)
GET    /patients/me/medical-documents       # Solo sus documentos
GET    /patients/me/medical-documents/{id}

# Mis Notificaciones (nuevo)
GET    /patients/me/notifications
GET    /patients/me/notifications/{id}
PATCH  /patients/me/notifications/{id}/read
POST   /patients/me/notifications/read-all
GET    /patients/me/notifications/unread-count
```

**Total endpoints nuevos: ~24**

---

## 4. Especificación de Endpoints

### 4.1 GET /patients/me/appointments

Lista todas las citas del paciente autenticado.

**Headers:** `Authorization: Bearer {token}` (patient_api)

**Lógica:** El backend busca todos los `Appointment` donde `patient.patient_account_id = authenticated_patient_account_id`.

**Respuesta 200:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid",
        "uuid": "uuid",
        "patient_id": "uuid",
        "doctor": {
          "id": "uuid",
          "full_name": "Dr. Juan Pérez",
          "specialty": "Cardiología"
        },
        "clinic_branch": {
          "id": "uuid",
          "name": "Sede Caracas Norte",
          "address": "Av. Principal #123"
        },
        "date": "2026-06-25",
        "time": "09:00:00",
        "type": "consultation",
        "status": "pending",
        "notes": "Primera consulta de control",
        "created_at": "2026-06-22T10:00:00Z"
      }
    ],
    "per_page": 20,
    "total": 5
  }
}
```

### 4.2 GET /patients/me/consultations

Lista todas las consultas del paciente.

**Headers:** `Authorization: Bearer {token}` (patient_api)

**Respuesta 200:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid",
        "uuid": "uuid",
        "doctor": {
          "id": "uuid",
          "full_name": "Dr. Juan Pérez",
          "specialty": "Cardiología"
        },
        "clinic_branch": {
          "id": "uuid",
          "name": "Sede Caracas Norte"
        },
        "date": "2026-06-22",
        "reason": "Dolor torácico",
        "diagnosis": "Posible angina",
        "status": "completed",
        "created_at": "2026-06-22T10:00:00Z"
      }
    ]
  }
}
```

### 4.3 GET /patients/me/prescriptions

Lista todas las recetas del paciente (ativas y vencidas).

**Headers:** `Authorization: Bearer {token}` (patient_api)

**Respuesta 200:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid",
        "uuid": "uuid",
        "public_token": "a1b2c3d4e5f6g7h8",
        "doctor": {
          "id": "uuid",
          "full_name": "Dr. Juan Pérez"
        },
        "date": "2026-06-22",
        "expiration_date": "2026-07-22",
        "status": "ACTIVE",
        "items": [
          {
            "medication": {
              "id": "uuid",
              "active_principle": "Paracetamol",
              "concentration": "500mg",
              "presentation": "TABLETA"
            },
            "dose": "1 tableta",
            "frequency": "Cada 8 horas",
            "duration": "7 días",
            "quantity": 2
          }
        ],
        "created_at": "2026-06-22T10:00:00Z"
      }
    ]
  }
}
```

### 4.4 GET /patients/me/quote-requests/{id}/offers

Lista las ofertas de farmacias para una solicitud de cotización del paciente.

**Headers:** `Authorization: Bearer {token}` (patient_api)

**Respuesta 200:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "status": "OPEN",
    "prescription": {
      "id": "uuid",
      "public_token": "a1b2c3d4e5f6g7h8",
      "items": [...]
    },
    "offers": [
      {
        "id": "uuid",
        "provider": {
          "id": "uuid",
          "commercial_name": "Farmacia San José",
          "address": "Av. Principal #123",
          "phone": "0212-1234567",
          "is_verified": true,
          "rating": 4.8
        },
        "price": "25.50",
        "currency": "USD",
        "availability": "Entrega inmediata",
        "comments": "Disponible en todas las presentaciones",
        "created_at": "2026-06-22T14:00:00Z"
      }
    ],
    "created_at": "2026-06-22T10:00:00Z"
  }
}
```

### 4.5 GET /patients/me/lab-results

Lista todos los resultados de laboratorio del paciente.

**Headers:** `Authorization: Bearer {token}` (patient_api)

**Respuesta 200:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid",
        "uuid": "uuid",
        "lab_request": {
          "id": "uuid",
          "exams_list": ["Hemograma", "Glucosa"]
        },
        "doctor": {
          "id": "uuid",
          "full_name": "Dr. Juan Pérez"
        },
        "status": "COMPLETED",
        "result_json": {
          "hemoglobina": { "value": 14.5, "unit": "g/dL", "reference": "12-16" }
        },
        "file_url": "https://storage.example.com/results/abc123.pdf",
        "performed_at": "2026-06-20T08:30:00Z",
        "reviewed_at": "2026-06-22T11:00:00Z",
        "created_at": "2026-06-22T10:00:00Z"
      }
    ]
  }
}
```

### 4.6 GET /patients/me/invoices

Lista todas las facturas del paciente.

**Headers:** `Authorization: Bearer {token}` (patient_api)

**Respuesta 200:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid",
        "uuid": "uuid",
        "doctor": {
          "id": "uuid",
          "full_name": "Dr. Juan Pérez"
        },
        "clinic_branch": {
          "id": "uuid",
          "name": "Sede Caracas Norte"
        },
        "subtotal": "100.00",
        "tax": "16.00",
        "discount": "0.00",
        "total": "116.00",
        "currency": "USD",
        "status": "PAID",
        "due_date": "2026-07-22",
        "total_paid": "116.00",
        "created_at": "2026-06-22T10:00:00Z"
      }
    ]
  }
}
```

### 4.7 GET /patients/me/notifications

Lista las notificaciones del paciente.

**Headers:** `Authorization: Bearer {token}` (patient_api)

> **Nota:** Las notificaciones del paciente se generan con `user_id = doctor_user_id`. El paciente ve las notificaciones dirigidas a ÉL (type: QUOTE_RECEIVED, LAB_RESULT_READY, INVOICE_RECEIVED, FOLLOW_UP_ALERT).

**Respuesta 200:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid",
        "uuid": "uuid",
        "type": "QUOTE_RECEIVED",
        "title": "Nueva cotización recibida",
        "message": "Farmacia San José envió una cotización para tu receta",
        "is_read": false,
        "link": "/patient/quotes/requests/abc123",
        "created_at": "2026-06-22T14:00:00Z"
      }
    ],
    "per_page": 20,
    "total": 5
  }
}
```

### 4.8 GET /patients/me/medical-documents

Lista los documentos médicos del paciente (certificados, referencias, informes).

**Headers:** `Authorization: Bearer {token}` (patient_api)

**Respuesta 200:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid",
        "uuid": "uuid",
        "public_token": "x1y2z3w4v5u6t7s8",
        "doctor": {
          "id": "uuid",
          "full_name": "Dr. Juan Pérez"
        },
        "clinic_branch": {
          "id": "uuid",
          "name": "Sede Caracas Norte"
        },
        "type": "CERTIFICATE",
        "content": "Certifica que el paciente...",
        "created_at": "2026-06-22T10:00:00Z"
      }
    ]
  }
}
```

---

## 5. Modelo de Datos — Cambios de Schema

### 5.1 Migration: consultations — agregar patient_account_id

```php
Schema::table('consultations', function (Blueprint $table) {
    $table->uuid('patient_account_id')
          ->nullable()
          ->after('patient_id')
          ->references('id')
          ->on('patient_accounts');

    // Index para búsqueda rápida
    $table->index('patient_account_id');
});
```

### 5.2 Migration: invoices — agregar patient_account_id

```php
Schema::table('invoices', function (Blueprint $table) {
    $table->uuid('patient_account_id')
          ->nullable()
          ->after('patient_id')
          ->references('id')
          ->on('patient_accounts');

    $table->index('patient_account_id');
});
```

### 5.3 Migration: medical_documents — agregar patient_account_id

```php
Schema::table('medical_documents', function (Blueprint $table) {
    $table->uuid('patient_account_id')
          ->nullable()
          ->after('patient_id')
          ->references('id')
          ->on('patient_accounts');

    $table->index('patient_account_id');
});
```

### 5.4 Prescription — Acceso via Patient

`Prescription` ya tiene `patient_id` (Patient). Para acceder via PatientAccount:

```php
// En PatientAccount model — cambiar hasOne a hasMany
public function patients()
{
    return $this->hasMany(Patient::class);
}

// Prescription::whereHas('patient.patientAccount', ...)
// O directo si se populó patient_account_id en prescription (futuro)
```

---

## 6. Permisos y Scope

| Recurso | Patient ve | Filtro |
|---------|-----------|--------|
| Appointments | Las suyas | `patient.patient_account_id = auth_id` |
| Consultations | Las suyas | `patient_account_id = auth_id` |
| Prescriptions | Las suyas | `patient.patient_account_id = auth_id` |
| QuoteRequests | Las suyas | `patient_account_id = auth_id` |
| QuoteOffers | De sus QuoteRequests | JOIN QuoteRequest |
| LabResults | Los suyos | `patient.patient_account_id = auth_id` |
| Invoices | Las suyas | `patient_account_id = auth_id` |
| MedicalDocuments | Los suyos | `patient_account_id = auth_id` |
| Notifications | Las suyas | `user_id = auth_user_id` (doctor's user_id que le notifica) |

---

## 7. Decisiones de Diseño

### 7.1 PatientAccount.hasOne vs hasMany Patient

El schema actual dice `hasOne`. Pero un paciente puede tener MULTIPLES Patient records (uno por doctor). Se cambiará a `hasMany` para Phase 5.

### 7.2 Notificaciones al paciente

Las notificaciones actualmente tienen `user_id` que apunta al User (doctor). Cuando el doctor genera una acción que notifica al paciente, el sistema crea una notificación con `user_id` del doctor destinatario.

El paciente accede a SUS notificaciones filtrando por `user_id` del paciente (que está en el token patient_api).

**Espera**: hay un problema arquitectónico. Las notificaciones tienen `user_id` (User/Doctor), no `patient_account_id`. Un paciente no tiene User, tiene PatientAccount.

**Solución**: Se crea un flujo donde cuando doctor quiere notificar al paciente:
1. Doctor llama un servicio de notificaciones
2. El servicio crea Notification con `user_id = NULL` y `patient_account_id = target_patient_account_id`
3. El paciente filtra por `patient_account_id`

Esto requiere cambiar la tabla notifications:

```php
Schema::table('notifications', function (Blueprint $table) {
    $table->uuid('patient_account_id')
          ->nullable()
          ->after('user_id')
          ->references('id')
          ->on('patient_accounts');

    $table->index(['patient_account_id', 'is_read']);
});
```

### 7.3 PatientAccount token claims

El token del PatientAccount ya tiene: `id`, `email`, `phone`. No tiene `patient_account_id` explícito, pero `id` ES el `patient_account_id`.

---

## 8. Endpoints de Verificación (QR) — Pendiente

Estos endpoints ya fueron identificados como faltantes en la guía de implementación frontend:

```
GET /verify/prescription/{public_token}    # Farmacia verifica receta
GET /verify/document/{public_token}        # Empleador verifica certificado
```

Estos son endpoints PÚBLICOS (sin auth), solo reciben el public_token y retornan los datos verificables de la receta/documento.

**Se implementan en Phase 5 como endpoints públicos:**
- `GET /verify/prescription/{publicToken}` → retorna datos de la receta (doctor, medicamentos, fecha, status)
- `GET /verify/document/{publicToken}` → retorna datos del documento (tipo, contenido, doctor, fecha)

---

## 9. Resumen de Cambios

### Archivos nuevos (Controllers)
- `app/Http/Controllers/Api/V1/Patient/PatientAppointmentController.php`
- `app/Http/Controllers/Api/V1/Patient/PatientConsultationController.php`
- `app/Http/Controllers/Api/V1/Patient/PatientPrescriptionController.php`
- `app/Http/Controllers/Api/V1/Patient/PatientQuoteRequestController.php`
- `app/Http/Controllers/Api/V1/Patient/PatientLabResultController.php`
- `app/Http/Controllers/Api/V1/Patient/PatientInvoiceController.php`
- `app/Http/Controllers/Api/V1/Patient/PatientNotificationController.php`
- `app/Http/Controllers/Api/V1/Patient/PatientMedicalDocumentController.php`
- `app/Http/Controllers/Api/V1/Public/VerifyController.php`

### Migrations
- `add_patient_account_id_to_consultations`
- `add_patient_account_id_to_invoices`
- `add_patient_account_id_to_medical_documents`
- `add_patient_account_id_and_index_to_notifications`

### Models a modificar
- `PatientAccount.php` — cambiar `hasOne` a `hasMany('Patient')`
- `Consultation.php` — agregar `patientAccount()` relationship
- `Invoice.php` — agregar `patientAccount()` relationship
- `MedicalDocument.php` — agregar `patientAccount()` relationship
- `Notification.php` — agregar `patientAccount()` relationship

### Routes a agregar
- `Route::middleware('auth:patient_api')->group('/patients/me', ...)`

### Endpoints públicos nuevos
- `GET /verify/prescription/{publicToken}`
- `GET /verify/document/{publicToken}`

---

## 10. FUERA DE SCOPE

- App móvil nativa (esto es solo API backend)
- Notificaciones push (solo in-app en Phase 5)
- Historial de pagos externo (ver facturas)
- Integración con pasarelas de pago reales
