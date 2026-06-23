# LUCA Health OS — API Phase 2 Documentation

Versión: 1.0  
Fecha: 2026-06-22  
Base URL: `/api/v1`

---

## Autenticación

Todos los endpoints clínicos (no auth) requieren header de autenticación:

```
Authorization: Bearer {token}
Accept: application/json
```

**Guards disponibles:**
- `auth:user_api` — doctors, providers, staff
- `auth:patient_api` — pacientes

---

## Idempotency — REQUERIDO en todos los POST

**Header obligatorio:** `Idempotency-Key: {uuid}`

Sin este header → **400 Bad Request**.

- Cache de respuesta: 24 horas
- Si la key ya fue usada → retorna la respuesta original (200)
- Recomendación: generar UUIDv4 por cada intento de creación

---

## Endpoints Públicos

### Especialidades

```
GET /specialties
```

**Respuesta 200:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Cardiología",
      "description": "Diagnóstico y tratamiento de enfermedades del corazón"
    }
  ]
}
```

### Ubicaciones

```
GET /locations/cities?state_id={uuid}
```

**Query params:**
- `state_id` (opcional) — filtra por estado

**Respuesta 200:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Caracas",
      "state_id": "uuid",
      "state_name": "Distrito Capital"
    }
  ]
}
```

---

## Citas Médicas

```
GET    /appointments          # Listar
POST   /appointments          # Crear (idempotent)
GET    /appointments/{id}     # Ver
PUT    /appointments/{id}     # Actualizar
PATCH  /appointments/{id}     # Actualizar parcialmente
DELETE /appointments/{id}     # Eliminar
```

### POST /appointments

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "patient_id": "uuid (required)",
  "user_id": "uuid (required)",
  "clinic_branch_id": "uuid (optional)",
  "date": "2026-06-25",
  "time": "09:00",
  "type": "consultation",
  "notes": "Primera consulta de control"
}
```

**Posibles valores `type`:** cualquier string (ej: `consultation`, `procedure`, `follow-up`)

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "patient_id": "uuid",
    "user_id": "uuid",
    "clinic_branch_id": "uuid",
    "date": "2026-06-25",
    "time": "09:00:00",
    "type": "consultation",
    "notes": "Primera consulta de control",
    "status": "pending",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

### Estados de Cita

| Status | Descripción |
|--------|-------------|
| `pending` | Esperando confirmación |
| `in-progress` | En curso |
| `completed` | Finalizada |
| `cancelled` | Cancelada |

---

## Plantillas de Formularios

```
GET    /form-templates           # Listar
POST   /form-templates           # Crear (idempotent)
GET    /form-templates/{id}      # Ver
PUT    /form-templates/{id}      # Actualizar
PATCH  /form-templates/{id}      # Actualizar parcialmente
DELETE /form-templates/{id}      # Eliminar
```

### POST /form-templates

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "title": "Evaluación Cardiovascular",
  "schema_json": {
    "sections": [
      {
        "name": "Antecedentes",
        "fields": [
          {"type": "text", "label": "Presión arterial", "required": true},
          {"type": "select", "label": "Frecuencia cardiaca", "options": ["Normal", "Alta", "Baja"]}
        ]
      }
    ]
  },
  "user_id": "uuid (optional)",
  "specialty": "Cardiología (optional)"
}
```

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "title": "Evaluación Cardiovascular",
    "schema_json": { ... },
    "user_id": "uuid",
    "specialty": "Cardiología",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

---

## Consultas

```
GET    /consultations            # Listar
POST   /consultations            # Crear (idempotent)
GET    /consultations/{id}       # Ver
PUT    /consultations/{id}       # Actualizar
PATCH  /consultations/{id}       # Actualizar parcialmente
DELETE /consultations/{id}       # Eliminar
```

### POST /consultations

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "user_id": "uuid (required)",
  "patient_id": "uuid (required)",
  "appointment_id": "uuid (optional) - cita asociada",
  "clinic_branch_id": "uuid (optional)",
  "form_template_id": "uuid (optional)",
  "date": "2026-06-22 (required)",
  "reason": "Dolor torácico intermitente",
  "physical_exam": "Paciente alerta, ACP normal...",
  "diagnosis": "Posible angina inestable",
  "treatment_plan": "ECA + laboratorio...",
  "dynamic_data": { "custom_field": "valor" }
}
```

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "user_id": "uuid",
    "patient_id": "uuid",
    "appointment_id": "uuid",
    "clinic_branch_id": "uuid",
    "form_template_id": "uuid",
    "date": "2026-06-22",
    "reason": "Dolor torácico intermitente",
    "physical_exam": "Paciente alerta, ACP normal...",
    "diagnosis": "Posible angina inestable",
    "treatment_plan": "ECA + laboratorio...",
    "dynamic_data": { "custom_field": "valor" },
    "status": "pending",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

### Estados de Consulta

| Status | Descripción |
|--------|-------------|
| `pending` | Esperando atención |
| `in-progress` | En curso |
| `completed` | Finalizada |
| `cancelled` | Cancelada |

---

## Signos Vitales (Nested)

```
GET    /consultations/{consultation}/vital-signs    # Obtener (recurso singular)
POST   /consultations/{consultation}/vital-signs    # Crear/Actualizar (idempotent - upsert)
PUT    /consultations/{consultation}/vital-signs/{id}
PATCH  /consultations/{consultation}/vital-signs/{id}
```

> **Nota:** `GET /consultations/{consultation}/vital-signs` retorna **un solo objeto** (los signos vitales de esa consulta), no una lista. Si no existen, retorna 404.

### POST /consultations/{consultation}/vital-signs (Upsert)

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "weight": 70.5,
  "height": 1.75,
  "systolic_bp": 120,
  "diastolic_bp": 80,
  "heart_rate": 72,
  "respiratory_rate": 16,
  "temperature": 36.5,
  "oxygen_sat": 98,
  "date": "2026-06-22"
}
```

**Todos los campos son opcionales.**

> **Upsert:** Si la consulta ya tiene signos vitales, este endpoint los actualiza. Si no existen, los crea. Esto permite hacer `POST` con idempotency para crear o actualizar de forma segura.

**Respuesta 201/200:**
```json
{
  "data": {
    "id": "uuid",
    "consultation_id": "uuid",
    "weight": 70.5,
    "height": 1.75,
    "systolic_bp": 120,
    "diastolic_bp": 80,
    "heart_rate": 72,
    "respiratory_rate": 16,
    "temperature": 36.5,
    "oxygen_sat": 98,
    "date": "2026-06-22",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

**Respuesta 200** si ya existían y fueron actualizados (idem cuando la idempotency key ya fue usada).

---

## Solicitudes de Laboratorio (Nested)

```
GET    /consultations/{consultation}/lab-requests    # Obtener (recurso singular)
POST   /consultations/{consultation}/lab-requests    # Crear/Actualizar (idempotent - upsert)
PUT    /consultations/{consultation}/lab-requests/{id}
PATCH  /consultations/{consultation}/lab-requests/{id}
```

> **Nota:** `GET /consultations/{consultation}/lab-requests` retorna **un solo objeto** (la orden de laboratorio de esa consulta), no una lista. Si no existe, retorna 404.

### POST /consultations/{consultation}/lab-requests (Upsert)

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "exams_list": [
    "Hemograma completo",
    "Perfil lipídico",
    "Glucosa en ayunas"
  ],
  "instructions": "Ayuno de 12 horas previo",
  "is_completed": false
}
```

> **Upsert:** Comportamiento igual a VitalSigns — si la consulta ya tiene orden de laboratorio, se actualiza.

**Respuesta 201/200:**
```json
{
  "data": {
    "id": "uuid",
    "consultation_id": "uuid",
    "exams_list": ["Hemograma completo", "Perfil lipídico", "Glucosa en ayunas"],
    "instructions": "Ayuno de 12 horas previo",
    "is_completed": false,
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

---

## Seguimientos (Follow-Ups)

```
GET    /follow-ups           # Listar
POST   /follow-ups           # Crear (idempotent)
GET    /follow-ups/{id}      # Ver
PUT    /follow-ups/{id}      # Actualizar
PATCH  /follow-ups/{id}      # Actualizar parcialmente
DELETE /follow-ups/{id}      # Eliminar
```

### POST /follow-ups

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "user_id": "uuid (required)",
  "patient_id": "uuid (required)",
  "consultation_id": "uuid (optional)",
  "scheduled_date": "2026-07-01 (required)",
  "status": "PENDING",
  "response": "Respuesta del paciente (optional)"
}
```

**Valores de `status`:**

| Status | Descripción |
|--------|-------------|
| `PENDING` | Esperando respuesta |
| `SENT` | Enviado al paciente |
| `RESPONDED` | Paciente respondió |

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "user_id": "uuid",
    "patient_id": "uuid",
    "consultation_id": "uuid",
    "scheduled_date": "2026-07-01",
    "status": "PENDING",
    "response": null,
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

---

## Antecedentes Médicos del Paciente

```
GET    /patients/{patient}/medical-background    # Ver
POST   /patients/{patient}/medical-background    # Crear (idempotent)
PUT    /patients/{patient}/medical-background    # Actualizar
PATCH  /patients/{patient}/medical-background    # Actualizar parcialmente
```

### POST /patients/{patient}/medical-background

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "has_diabetes": true,
  "has_hypertension": false,
  "has_asthma": false,
  "other_conditions": "Hipotiroidismo",
  "past_hospitalizations": "Apendicectomía 2018"
}
```

**Todos los campos opcionales.**

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "patient_id": "uuid",
    "has_diabetes": true,
    "has_hypertension": false,
    "has_asthma": false,
    "other_conditions": "Hipotiroidismo",
    "past_hospitalizations": "Apendicectomía 2018",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

---

## Estilo de Vida del Paciente

```
GET    /patients/{patient}/lifestyle    # Ver
POST   /patients/{patient}/lifestyle    # Crear (idempotent)
PUT    /patients/{patient}/lifestyle    # Actualizar
PATCH  /patients/{patient}/lifestyle    # Actualizar parcialmente
```

### POST /patients/{patient}/lifestyle

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "smoking_status": "Nunca",
  "alcohol_consumption": "Ocasional",
  "activity_level": "Moderado",
  "diet_type": "Omnívoro"
}
```

**Todos los campos opcionales. Valores libres (strings hasta 50 chars).**

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "patient_id": "uuid",
    "smoking_status": "Nunca",
    "alcohol_consumption": "Ocasional",
    "activity_level": "Moderado",
    "diet_type": "Omnívoro",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

---

## Historia Obstétrica del Paciente

```
GET    /patients/{patient}/obstetric-history    # Ver
POST   /patients/{patient}/obstetric-history    # Crear (idempotent)
PUT    /patients/{patient}/obstetric-history    # Actualizar
PATCH  /patients/{patient}/obstetric-history    # Actualizar parcialmente
```

### POST /patients/{patient}/obstetric-history

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "last_period_date": "2026-06-01",
  "pregnancies": 2,
  "births": 2,
  "cesareans": 0,
  "abortions": 0,
  "contraceptive_method": "DIU"
}
```

**Todos los campos opcionales.**

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "patient_id": "uuid",
    "last_period_date": "2026-06-01",
    "pregnancies": 2,
    "births": 2,
    "cesareans": 0,
    "abortions": 0,
    "contraceptive_method": "DIU",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

---

## Historial Quirúrgico del Paciente

```
GET    /patients/{patient}/surgical-histories           # Listar
POST   /patients/{patient}/surgical-histories           # Crear (idempotent)
GET    /patients/{patient}/surgical-histories/{id}      # Ver
PUT    /patients/{patient}/surgical-histories/{id}       # Actualizar
PATCH  /patients/{patient}/surgical-histories/{id}       # Actualizar parcialmente
DELETE /patients/{patient}/surgical-histories/{id}       # Eliminar
```

### POST /patients/{patient}/surgical-histories

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "procedure": "Apendicectomía (required)",
  "date": "2018-03-15",
  "hospital": "Hospital Central",
  "notes": "Sin complicaciones"
}
```

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "patient_id": "uuid",
    "procedure": "Apendicectomía",
    "date": "2018-03-15",
    "hospital": "Hospital Central",
    "notes": "Sin complicaciones",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

---

## Historial Familiar del Paciente

```
GET    /patients/{patient}/family-histories           # Listar
POST   /patients/{patient}/family-histories           # Crear (idempotent)
GET    /patients/{patient}/family-histories/{id}      # Ver
PUT    /patients/{patient}/family-histories/{id}       # Actualizar
PATCH  /patients/{patient}/family-histories/{id}       # Actualizar parcialmente
DELETE /patients/{patient}/family-histories/{id}       # Eliminar
```

### POST /patients/{patient}/family-histories

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "condition": "Diabetes tipo 2 (required)",
  "relationship": "Madre",
  "note": "Diagnosticada a los 55 años"
}
```

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "patient_id": "uuid",
    "condition": "Diabetes tipo 2",
    "relationship": "Madre",
    "note": "Diagnosticada a los 55 años",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

---

## Vacunas del Paciente

```
GET    /patients/{patient}/vaccinations           # Listar
POST   /patients/{patient}/vaccinations           # Crear (idempotent)
GET    /patients/{patient}/vaccinations/{id}      # Ver
PUT    /patients/{patient}/vaccinations/{id}       # Actualizar
PATCH  /patients/{patient}/vaccinations/{id}       # Actualizar parcialmente
DELETE /patients/{patient}/vaccinations/{id}       # Eliminar
```

### POST /patients/{patient}/vaccinations

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "vaccine": "Influenza estacional (required)",
  "dose_number": 1 (required, min: 1),
  "date": "2026-04-15"
}
```

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "patient_id": "uuid",
    "vaccine": "Influenza estacional",
    "dose_number": 1,
    "date": "2026-04-15",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

---

## Códigos de Error

| Código | Descripción |
|--------|-------------|
| 400 | Bad Request — falta `Idempotency-Key` en POST |
| 401 | Unauthorized — token inválido o expirado |
| 403 | Forbidden — no tienes permisos |
| 404 | Not Found — recurso no existe |
| 422 | Validation Error — datos inválidos |
| 500 | Internal Server Error |

**Error 400 por falta de idempotency key:**
```json
{
  "message": "Idempotency Key is required"
}
```

**Error 422 (validación):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "patient_id": ["The patient id field is required."]
  }
}
```

---

## Resumen de Rutas

| Recurso | Endpoints | Idempotent |
|---------|-----------|------------|
| Appointments | 6 (GET list, POST, GET show, PUT, PATCH, DELETE) | POST |
| FormTemplates | 6 | POST |
| Consultations | 6 | POST |
| FollowUps | 6 | POST |
| VitalSigns | 4 (nested) | POST |
| LabRequests | 4 (nested) | POST |
| MedicalBackground | 4 (patient-scoped) | POST |
| Lifestyle | 4 (patient-scoped) | POST |
| ObstetricHistory | 4 (patient-scoped) | POST |
| SurgicalHistories | 6 (patient-scoped) | POST |
| FamilyHistories | 6 (patient-scoped) | POST |
| Vaccinations | 6 (patient-scoped) | POST |
| **TOTAL** | **63** | **14 POST** |
