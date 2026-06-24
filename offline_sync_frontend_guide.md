# Guía de Integración Offline-First — API LUCA

Este documento describe el flujo completo de sincronización offline para el frontend mobile (Flutter/React Native/etc).

---

## 1. Autenticación y JWT

### Login

```
POST /api/v1/auth/users/login
Content-Type: application/json

{
  "email": "doctor@luca.med",
  "password": "••••••••"
}
```

**Respuesta exitosa (200):**

```json
{
  "access_token": "eyJ...",
  "token_type": "bearer",
  "expires_in": 14400
}
```

- **`access_token`**: JWT para autenticar requests subsiguientes.
- **`expires_in`**: `14400` = 14,400 minutos = **10 días**. El token expira en 10 días.
- Almacenar el token en el storage seguro del dispositivo (no en plain localStorage).

### Refresh Token

Si el token expira mientras hay trabajo offline pendiente, al reconectar:

```
POST /api/v1/auth/users/refresh
Authorization: Bearer <access_token>
```

**Respuesta (200):**
```json
{
  "access_token": "eyJ...",
  "token_type": "bearer",
  "expires_in": 14400
}
```

> Si el refresh falla (token ya fuera de ventana de refresh), el servidor devuelve `401`. En ese caso, mostrar prompt de login. **Los datos locales en IndexedDB se conservan** — solo se pierde la sesión, no el trabajo offline.

### Logout

```
POST /api/v1/auth/users/logout
Authorization: Bearer <access_token>
```

---

## 2. Arquitectura Offline-First

### Principios

1. **Todo registro nuevo se crea con UUID generado en el dispositivo** — nunca se espera respuesta del servidor para crear un registro local.
2. **Todas las entidades usan UUID como identificador público** — el ID numérico interno del servidor es transparente para el cliente.
3. **El servidor nunca ignora un UUID** — si el cliente envía un UUID existente, se hace upsert (actualizar si es más nuevo).
4. **Un solo endpoint para sincronizar** — reduce la cantidad de requests en conexiones inestables.

### Cola de trabajo offline

Cuando el usuario está offline, las operaciones se encolan localmente (IndexedDB). Al reconectar, se ejecuta la sync.

---

## 3. Sincronización: `POST /api/sync`

### Endpoint

```
POST /api/v1/sync
Authorization: Bearer <access_token>
Content-Type: application/json
```

### 3.1 Request — Estructura General

```json
{
  "last_sync_timestamp": "2026-06-20T08:00:00.000Z",
  "push": {
    "patients": [ ... ],
    "appointments": [ ... ],
    "consultations": [ ... ],
    "medical_backgrounds": [ ... ],
    "prescriptions": [ ... ],
    "vital_signs": [ ... ],
    ...
  }
}
```

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `last_sync_timestamp` | ISO 8601 string (nullable) | Timestamp de la última sync exitosa. `null` en la primera sync. |
| `push` | object | Mapa de entidad → array de registros a enviar al servidor |
| `push.<entidad>` | array | Lista de registros de esa entidad |

### 3.2 Estructura de un Registro (push)

Cada registro en el push tiene esta forma común:

```json
{
  "uuid": "4afc62c3-982c-47bb-a2d9-e93ffdf6a4ab",
  "updated_at": "2026-06-23T21:40:00.000Z",
  ...campos específicos de la entidad...
}
```

| Campo | Requerido | Descripción |
|-------|-----------|-------------|
| `uuid` | **Sí** | UUID generado por el dispositivo. Identifica el registro de forma idempotente. |
| `updated_at` | **Sí** | Timestamp de última modificación en el dispositivo (ms). Se usa para LWW. |
| Campos específicos | — | Los campos dependen de la entidad (descritos más abajo). |

### 3.3 Orden de Entidades en Push

El frontend debe enviar las entidades **en este orden** (para que las dependencias se resuelvan correctamente):

```
1. patients
2. appointments
3. consultations
4. medical_backgrounds
5. lifestyles
6. obstetric_histories
7. surgical_histories
8. family_histories
9. vaccinations
10. vital_signs
11. lab_requests
12. prescriptions
13. prescription_items
14. follow_ups
15. lab_results
16. invoices
17. invoice_items
18. payments
19. quote_requests
20. quote_offers
21. notifications
```

> Si una entidad depende de otra (ej. `consultation` depende de `patient` via `patient_uuid`), la entidad dependencia debe ir **antes**. El servidor procesa en este orden y guarda parcialmente si hay errores de FK (no aborta toda la sync).

### 3.4 Campos por Entidad

#### `patients`

```json
{
  "uuid": "4afc62c3-982c-47bb-a2d9-e93ffdf6a4ab",
  "updated_at": "2026-06-23T21:40:00.000Z",
  "first_name": "María",
  "last_name": "González",
  "national_id": "V-30123456",
  "birth_date": "1990-05-15",
  "gender": "female",
  "email": "maria@gmail.com",
  "phone": "+584241234567",
  "address": "Calle 5, Urbanización Los Palos",
  "city_id": 1,
  "blood_type": "O+",
  "allergies": "Penicilina",
  "chronic_conditions": "Asma leve",
  "private_notes": "Paciente nerviosa con procedimientos",
  "emergency_contact_name": "Carlos González",
  "emergency_contact_phone": "+584241234568"
}
```

| Campo | Tipo | Notas |
|-------|------|-------|
| `city_id` | integer | FK a cities. Si la ciudad no existe en el servidor → error con `field: "city_id"` |

#### `appointments`

```json
{
  "uuid": "727fe296-80bf-a4bf-84b9-e93ffdf6a4bc",
  "updated_at": "2026-06-23T21:10:00.000Z",
  "patient_uuid": "4afc62c3-982c-47bb-a2d9-e93ffdf6a4ab",
  "date": "2026-06-24",
  "time": "09:00",
  "type": "CONSULTATION",
  "status": "CONFIRMADA",
  "notes": "Primera consulta de control"
}
```

| Campo | Tipo | Notas |
|-------|------|-------|
| `patient_uuid` | string | FK a patients. Si el paciente no existe → error `field: "patient_uuid"` |
| `clinic_branch_id` | integer | FK a clinic_branches. Opcional offline |

#### `consultations`

```json
{
  "uuid": "e93ffdf6-982c-4afc-62c3-a2d9e93ffdf6",
  "updated_at": "2026-06-23T21:45:00.000Z",
  "patient_uuid": "4afc62c3-982c-47bb-a2d9-e93ffdf6a4ab",
  "appointment_uuid": "727fe296-80bf-a4bf-84b9-e93ffdf6a4bc",
  "clinic_branch_id": 1,
  "form_template_id": 1,
  "date": "2026-06-23T10:30:00",
  "status": "COMPLETED",
  "reason": "Dolor de cabeza persistente",
  "physical_exam": "Paciente alerta, hidratada",
  "diagnosis": "Cefalea tensional",
  "treatment_plan": "Ibuprofeno 400mg c/8h por 5 días",
  "dynamic_data": { "bpm": 72, "perimetro_craneal": 55 }
}
```

#### `medical_backgrounds`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "patient_uuid": "4afc62c3-982c-47bb-a2d9-e93ffdf6a4ab",
  "has_diabetes": false,
  "has_hypertension": true,
  "has_asthma": false,
  "other_conditions": "Hipotiroidismo",
  "past_hospitalizations": "Apendicectomía 2018"
}
```

#### `lifestyles`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "patient_uuid": "...",
  "smoking_status": "never",
  "alcohol_consumption": "occasional",
  "activity_level": "moderate",
  "diet_type": "balanced"
}
```

#### `obstetric_histories`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "patient_uuid": "...",
  "last_period_date": "2026-06-01",
  "pregnancies": 2,
  "births": 2,
  "cesareans": 0,
  "abortions": 0,
  "contraceptive_method": "DIU"
}
```

#### `surgical_histories`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "patient_uuid": "...",
  "procedure": "Apendicectomía",
  "date": "2018-03-15",
  "hospital": "Hospital Central",
  "notes": "Sin complicaciones"
}
```

#### `family_histories`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "patient_uuid": "...",
  "condition": "Diabetes mellitus tipo 2",
  "relationship": "Madre",
  "note": "Diagnóstico a los 55 años"
}
```

#### `vaccinations`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "patient_uuid": "...",
  "vaccine": "Influenza estacional",
  "dose_number": 1,
  "date": "2026-04-10"
}
```

#### `vital_signs`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "consultation_uuid": "e93ffdf6-982c-4afc-62c3-a2d9e93ffdf6",
  "weight": 68.5,
  "height": 165.0,
  "systolic_bp": 120,
  "diastolic_bp": 80,
  "heart_rate": 72,
  "temperature": 36.5,
  "oxygen_sat": 98,
  "date": "2026-06-23T10:35:00"
}
```

#### `lab_requests`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "consultation_uuid": "e93ffdf6-982c-4afc-62c3-a2d9e93ffdf6",
  "exams_list": ["Hemograma completo", "Glucosa", "Perfil lipídico"],
  "instructions": "En ayunas de 8 horas",
  "is_completed": false
}
```

#### `prescriptions`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "patient_uuid": "...",
  "consultation_uuid": "...",
  "clinic_branch_id": 1,
  "date": "2026-06-23",
  "expiration_date": "2026-12-23",
  "notes": "Mantener hidratación",
  "status": "ACTIVE"
}
```

#### `prescription_items`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "prescription_uuid": "...",
  "medication_id": 1,
  "dose": "50mg",
  "frequency": "c/12h",
  "duration": "7 días",
  "quantity": 14,
  "notes": "Tomar con alimentos"
}
```

#### `follow_ups`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "patient_uuid": "...",
  "consultation_uuid": "...",
  "scheduled_date": "2026-07-23",
  "status": "PENDING",
  "response": null
}
```

#### `lab_results`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "lab_request_uuid": "...",
  "patient_id": 1,
  "file_url": "https://...",
  "result_json": { "hemoglobina": 14.2, "hematocrito": 42 },
  "notes": "Resultados dentro de parámetros normales",
  "status": "PENDING_REVIEW",
  "performed_at": "2026-06-24T08:00:00"
}
```

#### `invoices`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "patient_uuid": "...",
  "consultation_uuid": "...",
  "prescription_id": null,
  "clinic_branch_id": 1,
  "subtotal": 50.00,
  "tax": 5.00,
  "discount": 0.00,
  "total": 55.00,
  "currency": "USD",
  "status": "PENDING",
  "due_date": "2026-07-23",
  "notes": "Pago en dos cuotas"
}
```

#### `invoice_items`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "invoice_uuid": "...",
  "description": "Consulta médica general",
  "quantity": 1,
  "unit_price": 50.00,
  "total": 50.00
}
```

#### `payments`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "invoice_uuid": "...",
  "amount": 27.50,
  "method": "CASH",
  "reference": null,
  "paid_at": "2026-06-23T11:00:00",
  "notes": "Primera cuota"
}
```

#### `quote_requests`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "prescription_uuid": "...",
  "patient_uuid": "...",
  "city_id": 1,
  "status": "PENDING"
}
```

#### `quote_offers`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "quote_request_uuid": "...",
  "provider_id": 1,
  "price": 120.00,
  "currency": "USD",
  "availability": "2026-06-28",
  "comments": "Incluye entrega a domicilio"
}
```

#### `notifications`

```json
{
  "uuid": "...",
  "updated_at": "...",
  "user_id": 1,
  "type": "APPOINTMENT_REMINDER",
  "title": "Cita mañana",
  "message": "Tiene cita mañana a las 9:00 AM",
  "is_read": false,
  "link": "/appointments/727fe296..."
}
```

---

## 4. Respuesta del Servidor

```json
{
  "sync_timestamp": "2026-06-23T22:05:00.000Z",
  "has_more": false,
  "push_results": {
    "patients": {
      "success": ["4afc62c3-982c-47bb-a2d9-e93ffdf6a4ab"],
      "errors": []
    },
    "consultations": {
      "success": ["e93ffdf6-982c-4afc-62c3-a2d9e93ffdf6"],
      "errors": [
        {
          "uuid": "...",
          "field": "patient_uuid",
          "message": "La ciudad seleccionada no existe en el sistema."
        }
      ]
    }
  },
  "pull": {
    "patients": [ ... ],
    "appointments": [ ... ],
    "consultations": [ ... ],
    "medical_backgrounds": [ ... ],
    "lifestyles": [ ... ],
    "obstetric_histories": [ ... ],
    "surgical_histories": [ ... ],
    "family_histories": [ ... ],
    "vaccinations": [ ... ],
    "vital_signs": [ ... ],
    "lab_requests": [ ... ],
    "prescriptions": [ ... ],
    "prescription_items": [ ... ],
    "follow_ups": [ ... ],
    "lab_results": [ ... ],
    "invoices": [ ... ],
    "invoice_items": [ ... ],
    "payments": [ ... ],
    "quote_requests": [ ... ],
    "quote_offers": [ ... ],
    "notifications": [ ... ],
    "cities": [ ... ],
    "specialties": [ ... ],
    "medications": [ ... ],
    "form_templates": [ ... ],
    "clinic_branches": [ ... ]
  }
}
```

### Interpretación de `push_results`

| Campo | Descripción |
|-------|-------------|
| `success[]` | UUIDs procesados exitosamente (insertados o actualizados) |
| `errors[]` | Registros que fallaron. El frontend debe mostrarlos al usuario para corrección manual. |

### Errores de Relación (FK)

Cuando un registro falla por una FK inválida (ej. `city_id` referecia una ciudad borrada):

```json
{
  "uuid": "...",
  "field": "city_id",
  "message": "La ciudad seleccionada no existe en el sistema."
}
```

El frontend debe:
1. Marcar el registro localmente con estado de error.
2. Resaltar visualmente el campo `field` en la UI.
3. Permitir al usuario corregir y re-syncar.

### Interpretación de `pull`

El `pull` contiene **todos los registros del servidor modificados desde `last_sync_timestamp`**, incluyendo:
- Registros creados/modificados por otros doctores
- Registros eliminados (mediante `softDeletes` — venir con `deleted_at` no null indica eliminado)

El cliente debe hacer **merge** de estos registros con su base local usando **Last-Write-Wins** (comparar `updated_at`).

### Paginación con `has_more`

Si `has_more: true`, hay más de 500 registros para bajar. El cliente debe hacer llamadas consecutivas:

```json
{
  "sync_timestamp": "2026-06-23T22:05:00.000Z",
  "has_more": true,
  ...
}
```

> Usar el `sync_timestamp` de la respuesta como `last_sync_timestamp` de la siguiente llamada.

---

## 5. Catálogos (Solo Lectura)

Los siguientes endpoints son **catálogos globales** — no dependen del doctor logueado:

```
GET /api/v1/locations/cities        → cities
GET /api/v1/specialties             → specialties
```

Estos catálogos **también se incluyen en el `pull`** del `/api/sync` automaticamente, por lo que no necesitás consultarlos por separado en cada sync.

Para la **primera sync** (last_sync_timestamp = null), el servidor devuelve todos los catálogos en el pull.

---

## 6. Documentos Binarios (Fotos, PDFs)

Los archivos binarios NO van en el sync JSON. Siga este flujo:

### Paso 1: Registrar metadata en `/api/sync`

```json
{
  "push": {
    "medical_documents": [
      {
        "uuid": "f47ac10b-58cc-4372-a567-0e02b2c3d479",
        "updated_at": "2026-06-23T22:00:00.000Z",
        "patient_uuid": "4afc62c3-...",
        "type": "pdf",
        "content": null,
        "public_token": "gen-after-sync",
        "pending_upload": true
      }
    ]
  }
}
```

El servidor crea el registro con `pending_upload: true` y genera el `public_token`.

### Paso 2: Subir el binario por separado

```
POST /api/v1/documents/upload
Authorization: Bearer <access_token>
Content-Type: multipart/form-data

uuid: f47ac10b-58cc-4372-a567-0e02b2c3d479
file: [archivo PDF, JPG, PNG] (max 10MB)
```

**Respuesta (200):**
```json
{
  "message": "File uploaded successfully",
  "uuid": "f47ac10b-58cc-4372-a567-0e02b2c3d479"
}
```

### Paso 3: Limpiar local

Una vez recibido el `200 OK`, el cliente:
1. Actualiza el registro local: `pending_upload = false`
2. Borra el binario del storage local para liberar espacio

### Notas
- Tipos permitidos: `jpg`, `jpeg`, `png`, `pdf`
- Tamaño máximo: 10MB
- Si el paso 2 falla, se puede reintentar. El archivo local se conserva.

---

## 7. Flujo Completo Offline → Online

```
┌─────────────────────────────────────────────┐
│              DISPOSITIVO MÓVIL              │
│                                             │
│  1. Usuario crea/edita registro offline     │
│     → Se guarda en IndexedDB local          │
│     → Se encola en "dirty queue"           │
│                                             │
│  2. Conexión disponible                     │
│     → POST /api/sync                        │
│     ← 200 OK + push_results + pull          │
│                                             │
│  3. Procesar push_results:                  │
│     success → marcar como synced            │
│     errors  → mostrar al usuario para       │
│               corrección                     │
│                                             │
│  4. Procesar pull:                          │
│     → Merge con base local (LWW)            │
│     → pull.has_more → re-sync               │
│                                             │
│  5. Para binarios pending_upload:           │
│     → POST /api/documents/upload           │
│     → Marcar local como uploaded           │
│     → Borrar binario local                  │
└─────────────────────────────────────────────┘
```

---

## 8. Conflictos y Last-Write-Wins

### Política: Last-Write-Wins (LWW) por registro

- Si el servidor tiene un registro con `updated_at` **más reciente** que el que el cliente envía, el servidor **ignora el cambio del cliente**.
- El servidor lo reporta en `push_results.errors` con un mensaje de conflicto.
- El cliente debería actualizar su copia local con los datos del servidor (que vienen en el `pull`).

**Ejemplo de conflicto reportado:**

```json
{
  "uuid": "4afc62c3-...",
  "field": "_conflict",
  "message": "El servidor tiene una versión más reciente. Sus cambios fueron descartados."
}
```

> El campo `_conflict` es un indicador especial para mostrar al usuario que hubo un conflicto de edición simultánea.

---

## 9. Notas Importantes

### `public_token` en prescriptions y medical_documents

El servidor genera automáticamente el `public_token` cuando se crea el registro. El cliente debe:
- Enviar `public_token: null` o omitirlo en el push
- Usar el valor que viene en el `pull` del servidor para futuras consultas

### Campos obligatorios vs opcionales

Todos los campos son opcionales en el push EXCEPTO `uuid` y `updated_at`. El servidor guarda registros parciales si es necesario.

### Primera sync (last_sync_timestamp = null)

En la primera sync, enviar `last_sync_timestamp: null`. El servidor devuelve TODOS los registros del usuario en el `pull` (catálogos + datos propios), limitado a 500 por entidad con `has_more`.

### timezone

Todos los timestamps son **UTC** (`Z` suffix). EI frontend debe convertir a la hora local del dispositivo al mostrar.

---

## 10. Códigos de Error HTTP

| Código | Significado | Acción |
|--------|-------------|--------|
| `200` | Sync exitosa | Procesar `push_results` y `pull` |
| `401` | Token inválido/expirado | Intentar refresh. Si falla → prompt login |
| `422` | Payload malformado | Revisar estructura JSON |
| `500` | Error interno del servidor | Reintentar luego |
| `503` | Servidor no disponible | Reintentar cuando haya conexión |

---

## 11. Checklist de Implementación

- [ ] Generar UUID v4 para cada registro nuevo en el dispositivo
- [ ] Guardar `last_sync_timestamp` en storage persistente tras cada sync exitosa
- [ ] Mantener cola de registros "dirty" para sync
- [ ] Ordenar entidades en push según el orden topológico
- [ ] Procesar `push_results.success` → marcar como synced
- [ ] Procesar `push_results.errors` → mostrar al usuario, no descartar
- [ ] Merge del `pull` con base local usando LWW por `updated_at`
- [ ] Manejar `has_more: true` → hacer sync adicional
- [ ] Para binarios: metadata en sync → upload en segundo plano
- [ ] Almacenar token JWT en lugar seguro del dispositivo
- [ ] Implementar lógica de refresh token al reconectar
- [ ] Guardar catálogos en IndexedDB para uso offline
