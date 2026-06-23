# LUCA Health OS — Frontend Implementation Guide

> **Guía de Implementación para el Equipo Frontend**
> Complementa la documentación de API (api_phase2, api_phase3, api_phase4, api_auth).
> Lee primero las API docs para conocer estructuras de datos, enums y respuestas.

Versión: 1.0
Fecha: 2026-06-22

---

## TABLA DE CONTENIDOS

1. [Auth y Manejo de Tokens](#1-autenticación-y-manejo-de-tokens)
2. [Paginación y Filtros](#2-paginación-y-filtros)
3. [Matrix de Permisos](#3-matrix-de-permisos)
4. [Estrategia de Archivos (Upload)](#4-estrategia-de-archivos-upload)
5. [Polling y Tiempo Real](#5-polling-y-tiempo-real)
6. [Búsqueda](#6-búsqueda)
7. [Flujos Completos por Rol](#7-flujos-completos-por-rol)
8. [Edge Cases y Reglas de Negocio](#8-edge-cases-y-reglas-de-negocio)
9. [Manejo de Errores](#9-manejo-de-errores)
10. [Referencia Rápida de Rutas](#10-referencia-rápida-de-rutas)

---

## 1. AUTENTICACIÓN Y MANEJO DE TOKENS

### 1.1 Flujo de Login (respuesta exacta)

**Patient (WhatsApp/Portal Paciente):**
```
POST /api/v1/auth/patients/login
Content-Type: application/json
Idempotency-Key: {uuid}

Body: { "email": "...", "password": "..." }
```

**Respuesta 200:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

**Doctor/Provider/Admin:**
```
POST /api/v1/auth/users/login
Content-Type: application/json
Idempotency-Key: {uuid}

Body: { "email": "...", "password": "..." }
```

**Respuesta 200:** Misma estructura — `access_token`, `token_type`, `expires_in`.

### 1.2 Estructura del Token JWT (Custom Claims)

Los tokens incluyen claims personalizados para evitar consultas extra a la BD:

**Para User (Doctor/Provider/Admin):**
- `id` — UUID del usuario
- `email` — correo
- `role` — `DOCTOR`, `PROVIDER`, o `ADMIN`
- `isActive` — `true/false`

**Para PatientAccount:**
- `id` — UUID de la cuenta
- `email` — correo
- `phone` — teléfono

**Cómo obtener el rol desde el token (sin llamada extra):**
```javascript
// Decodificar JWT (sin verificar, solo extraer payload)
const payload = JSON.parse(atob(token.split('.')[1]));
const role = payload.role; // 'DOCTOR' | 'PROVIDER' | 'ADMIN'
```

### 1.3 Refresh de Token

```
POST /api/v1/auth/patients/refresh   (patient)
POST /api/v1/auth/users/refresh       (doctor/provider/admin)
Authorization: Bearer {token}
```

**Respuesta 200:** Nuevo token con `expires_in: 3600`.

**Estrategia recomendada:** Refrescar 5 minutos antes del expiry. Guardar `expires_in` al hacer login.

### 1.4 Logout

```
POST /api/v1/auth/patients/logout   (patient)
POST /api/v1/auth/users/logout       (doctor/provider/admin)
Authorization: Bearer {token}
```

Invalida el token en el servidor. Limpiar storage local después.

### 1.5 Redirección post-login por rol

```javascript
const role = getRoleFromToken(token);

switch (role) {
  case 'ADMIN':    redirect('/admin/dashboard'); break;
  case 'DOCTOR':   redirect('/doctor/agenda'); break;
  case 'PROVIDER':  redirect('/provider/inventory'); break;
  // patient usa patient_api guard
  default:          redirect('/patient/home');
}
```

### 1.6 Middleware de KYC (Doctores y Proveedores)

Doctores y proveedores nuevos tienen `isActive = true` pero no están verificados. Un middleware `EnsureKycIsApproved` bloquea el acceso clínico:

```
HTTP 403
{
  "message": "Su documentación se encuentra en revisión. Acceso restringido."
}
```

**UI:** Mostrar pantalla "Verificación en proceso" con instrucciones.

### 1.7 Cuenta Suspendida

Si `isActive = false` (admin suspendió al usuario):

```
HTTP 401
{
  "message": "Cuenta suspendida."
}
```

**UI:** Mostrar "Tu cuenta ha sido suspendida. Contacta a soporte."

### 1.8 Registro de Doctor (multipart/form-data)

El registro de doctor y proveedor usa `Content-Type: multipart/form-data`, NO `application/json`.

```
POST /api/v1/auth/users/register/doctor
Content-Type: multipart/form-data
Idempotency-Key: {uuid}

FormData:
  full_name: "Dr. Juan Pérez"
  email: "juan@doctor.com"
  password: "Password123"
  specialty_uuids[]: "uuid-specialty-1"
  specialty_uuids[]: "uuid-specialty-2"
  medical_license: (file) license.pdf
```

---

## 2. PAGINACIÓN Y FILTROS

### 2.1 Estructura Estándar de Paginación

Todas las listas usan paginación cursor-less (page-based):

```json
{
  "data": {
    "current_page": 1,
    "data": [...],
    "first_page_url": "...",
    "from": 1,
    "last_page": 10,
    "last_page_url": "...",
    "next_page_url": "...",
    "path": "...",
    "per_page": 20,
    "prev_page_url": null,
    "to": 20,
    "total": 195
  }
}
```

**Page size por defecto:** 20 items por página.

### 2.2 Cómo cambiar page size

El API no documenta un parámetro `page_size`. Asumir que no existe y usar el default de 20. Si el backend lo soporta, sería `?per_page=50`.

### 2.3 Filtros conocidos por recurso

| Recurso | Filtros disponibles |
|---------|---------------------|
| `GET /medications` | `?q=parac` (búsqueda por principio activo o nombre comercial) |
| `GET /pharmacy-inventories` | `?low_stock=true`, `?expired=true` |
| `GET /audit-logs` | `?user_id=`, `?patient_id=`, `?action=`, `?resource_type=`, `?from=`, `?to=` |
| `GET /invoices` | `?status=DRAFT` (asumido, no documentado explícitamente) |
| `GET /locations/cities` | `?state_id={uuid}` (opcional, filtra por estado) |

### 2.4 Búsqueda de medicamentos

```
GET /api/v1/medications?q=para
```

Busca en `active_principle` Y `commercial_name`. Case-insensitive.

---

## 3. MATRIX DE PERMISOS

### 3.1 Roles y sus alcances

| Rol | Alcance |
|-----|---------|
| `ADMIN` | Todo el sistema. Puede ver audit logs. Puede aprobar/rechazar KYC. |
| `DOCTOR` | Solo sus propias consultas, pacientes, recetas. No ve datos de otros doctores. |
| `PROVIDER` | Solo su propio inventario, sus propias ofertas de cotización. |
| `PATIENT` | **SOLO endpoints de autenticación.** No tiene rutas médicas propias. |

### 3.2 Pacientes: Accesso a sus propios datos

> **HUECO ARQUITECTÓNICO CRÍTICO:** Los pacientes NO tienen rutas API bajo `auth:patient_api` para acceder a sus datos médicos (recetas, citas, facturas, resultados). Solo tienen `/auth/patients/*`.

Esto significa que la app de pacientes probablemente requiere un backend separado o una rediseño donde el patient accede via `user_api` con un rol `PATIENT`. Hasta que se implemente, el flujo de paciente debe pasar por el dashboard del doctor (el doctor comparte enlaces).

**Lo que el paciente SÍ puede hacer (auth):**
- Login/logout/refresh
- Ver su perfil (`GET /auth/patients/me`)

**Lo que el paciente NO puede hacer (aún no implementado):**
- Ver sus recetas
- Ver sus citas
- Ver sus facturas
- Ver resultados de laboratorio

### 3.3 Doctores: Scope de sus datos

Los endpoints `GET /consultations`, `GET /prescriptions`, `GET /appointments` **devuelven solo los del doctor autenticado**. El backend filtra por `user_id` del token.

### 3.4 Proveedores: Scope de sus datos

`GET /pharmacy-inventories` devuelve solo el inventario del provider autenticado (filtro por `provider_id` del usuario). `GET /quote-requests` y `GET /quote-offers`同理.

### 3.5 Admin: Acceso total

Admin puede acceder a todo, incluyendo `GET /audit-logs` que está bloqueado para todos los demás.

---

## 4. ESTRATEGIA DE ARCHIVOS (UPLOAD)

### 4.1 El API NO tiene endpoint de upload

El API de LUCA **no provee un endpoint para subir archivos**. El campo `file_url` es un string URL externo. El frontend debe:

1. Subir el archivo a un storage externo (S3, Cloudinary, Firebase Storage, etc.)
2. Obtener la URL pública/del storage
3. Enviar esa URL al campo correspondiente (`file_url`)

### 4.2 Límites de archivos (KYC)

| Campo | Tipos aceptados | Tamaño máximo |
|-------|-----------------|---------------|
| `medical_license` (registro doctor) | pdf, jpg, png | 10MB |
| `business_document` (registro provider) | pdf, jpg, png | 10MB |
| `file_url` (verification-documents) | pdf, jpg, png | 10MB (asumido) |

### 4.3 Flujo de upload KYC

```
1. Frontend: seleccionar archivo
2. Frontend: validar tipo y tamaño localmente
3. Frontend: subir a S3/Cloudinary/Storage (presigned URL o upload directo)
4. Frontend: obtener URL del archivo
5. Frontend: POST /api/v1/verification-documents con { type, file_url: "https://..." }
```

---

## 5. POLLING Y TIEMPO REAL

### 5.1 No hay WebSockets

El API no documenta WebSockets ni Server-Sent Events. Toda comunicación es request-response.

### 5.2 Notificaciones: estrategia de polling

**Recomendación:** Poll cada 30 segundos cuando la app está en foreground.

```
GET /api/v1/notifications/unread-count
Authorization: Bearer {token}

// Respuesta: { "data": { "count": 5 } }

if (count > currentCount) {
  // Hay nuevas notificaciones
  GET /api/v1/notifications
}
```

### 5.3 Marketplace: nuevas cotizaciones

Cuando un paciente tiene una quote-request abierta, las farmacias envían ofertas. El paciente debe poll:

```
GET /api/v1/quote-requests/{id}/offers
Authorization: Bearer {token}  (patient_api)

Recomendación: poll cada 60 segundos mientras la quote-request tenga status OPEN.
```

### 5.4 Resultados de laboratorio

```
GET /api/v1/lab-results
```

Cuando el status cambia de `PENDING` a `COMPLETED` o `ABNORMAL`, el paciente recibe notificación. Poll `unread-count` + fetch notifications.

---

## 6. BÚSQUEDA

### 6.1 Medicamentos (autocompletado)

```
GET /api/v1/medications?q=parac
```

Para el autocompletado del campo "medicamento" en la receta:
- Disparar búsqueda después de 2 caracteres mínimo
- Debounce de 300ms
- Case-insensitive
- Busca en `active_principle` y `commercial_name`

### 6.2 Ciudades por estado

```
GET /api/v1/locations/cities
GET /api/v1/locations/cities?state_id={uuid}
```

Filtra la lista de ciudades para populate de dropdowns.

---

## 7. FLUJOS COMPLETOS POR ROL

### 7.1 Flujo: Doctor crea receta con QR

```
1. Doctor está en consulta → POST /consultations (crea consulta)
2. Doctor prescribe medicamentos:
   - Búsqueda: GET /medications?q=amox
   - Selección de medicamentos
3. Doctor crea receta:
   POST /prescriptions
   Headers: Authorization + Idempotency-Key
   Body: { patient_id, date, expiration_date, items: [{medication_id, dose, frequency, duration, quantity}] }
   → Respuesta incluye public_token (16 chars, ej: "a1b2c3d4e5f6g7h8")
4. Frontend genera código QR con el public_token
5. Paciente imprime receta con QR → Farmacia escanea
```

### 7.2 Flujo: Farmacia recibe pedido por QR

> **Nota:** No existe aún `GET /verify/{public_token}` en el API. Este endpoint debe ser construido o existe bajo otro nombre. Hasta entonces, la farmacia podría verificar así:

```
1. Farmacia busca la receta por public_token:
   GET /prescriptions (filtrar por public_token localmente, o endpoint futuro)
2. Si existe y status = ACTIVE → mostrar detalles
3. Farmacia envía cotización:
   POST /quote-requests/{id}/offers
   Headers: Authorization (provider) + Idempotency-Key
   Body: { price: 25.50, availability: "Entrega inmediata" }
4. Paciente recibe notificación de cotización
```

### 7.3 Flujo: Paciente solicita cotización y compara

```
1. Paciente tiene receta activa
2. Paciente inicia solicitud:
   POST /quote-requests
   Headers: Authorization (patient) + Idempotency-Key
   Body: { prescription_id, patient_id, city_id }
   → status: OPEN
3. Farmacias de esa ciudad ven la solicitud
4. Farmacias envían ofertas
5. Paciente ve ofertas:
   GET /quote-requests/{id}/offers
6. Paciente elige una → comunicación fuera de banda (WhatsApp, teléfono)
```

### 7.4 Flujo: Doctor recibe resultados de laboratorio

```
1. Durante consulta, doctor ordena estudios:
   POST /consultations/{consultation}/lab-requests
   Body: { exams_list: ["Hemograma", "Glucosa"], instructions: "Ayuno 12h" }
2. Laboratorio recibe orden (flujo externo, no en API)
3. Laboratorio sube resultados:
   POST /lab-results
   Headers: Authorization (provider) + Idempotency-Key
   Body: { lab_request_id, patient_id, result_json: {...}, file_url: "..." }
   → status: PENDING
4. Doctor es notificado (notification type: LAB_RESULT_READY)
5. Doctor revisa:
   GET /lab-results/{id}
6. Doctor marca como revisado:
   POST /lab-results/{id}/review
   → status: COMPLETED, reviewed_by, reviewed_at
```

### 7.5 Flujo: Facturación completa

```
1. Doctor termina consulta
2. Secretary/admin crea factura:
   POST /invoices
   Headers: Authorization + Idempotency-Key
   Body: { patient_id, consultation_id, items: [{description, quantity, unit_price}] }
   → status: DRAFT

3. Se agregan más items si es necesario:
   POST /invoices/{id}/items (puede repetirse)

4. Se envía factura:
   POST /invoices/{id}/send
   → status: SENT

5. Paciente recibe notificación (INVOICE_RECEIVED)

6. Paciente paga (puede ser en cashier, transferencia, etc.):
   POST /invoices/{id}/payments
   Headers: Authorization + Idempotency-Key
   Body: { amount: 100.00, method: "TRANSFER", reference: " txn_abc" }

7. Si payment >= total:
   → status: PAID automáticamente

8. Si payment < total:
   → status: PARTIALLY_PAID

9. Secretary puede ver estado actualizado en tiempo real
```

### 7.6 Flujo: KYC (Doctor registra y espera aprobación)

```
1. Doctor se registra:
   POST /auth/users/register/doctor (multipart)
   → Token devuelto, puede hacer login

2. Pero acceso clínico bloqueado:
   GET /consultations → HTTP 403 "Su documentación está en revisión"

3. Admin recibe notificación (flujo manual)

4. Admin revisa documento:
   PUT /verification-documents/{id}
   Body: { status: "APPROVED", comments: "Licencia verificada" }

5. Doctor ahora puede acceder:
   GET /consultations → 200 OK
```

---

## 8. EDGE CASES Y REGLAS DE NEGOCIO

### 8.1 Pago mayor al total de factura

**¿Qué pasa?** El API no tiene protección contra sobrepago.

**Recomendación frontend:** Validar en UI que `amount <= (total - totalPaid)` antes de enviar. Mostrar el saldo pendiente.

```javascript
const pending = invoice.total - invoice.totalPaid;
if (amount > pending) {
  // Warn user: "El monto excede el saldo pendiente de {pending}"
}
```

### 8.2 Eliminar medicamento usado en receta

Si se elimina un `Medication` que está en `PrescriptionItem` activa:
- La FK en `PrescriptionItem` probablemente sea `ON DELETE RESTRICT` (no permite eliminar)
- Si es `SET NULL`, el item mostraría `medication_id: null`
- **Recomendación:** En UI, deshabilitar el botón eliminar si el medicamento tiene recetas activas

### 8.3 Múltiples resultados para la misma orden de lab

**Restricción:** `lab_request_id` tiene constraint `UNIQUE` en `LabResult`. Relación 1:1.

**Si el laboratorio intenta crear segundo resultado:**
- El segundo POST retorna error 422 (violación de unicidad)
- **UI:** Mostrar "Esta orden ya tiene resultados cargados"

### 8.4 Expiración de recetas

El status de `Prescription` cambia a `EXPIRED` cuando `expiration_date < now()`.
- La farmacia al escanear el QR debe verificar: si `status = EXPIRED` → mostrar "Receta vencida"
- La dispensación queda a responsabilidad de la farmacia (el sistema no lo impide, solo informa)

### 8.5 Eliminación de facturas

- Solo facturas en `DRAFT` pueden eliminarse
-其他 estados → HTTP 422
- Al eliminar, `InvoiceItem` y `Payment` se borran en cascada automáticamente
- **UI:** Solo mostrar botón eliminar si `status === 'DRAFT'`

### 8.6 Estados deInvoice y transiciones válidas

```
DRAFT → cualquier estado editable
     → SENT (POST /send)
     → DELETE (si es DRAFT)

SENT → PAID (cuando totalPaid >= total)
     → PARTIALLY_PAID (cuando 0 < totalPaid < total)
     → OVERDUE (si due_date < now && totalPaid < total)
     → CANCELLED (admin)

PARTIALLY_PAID → PAID (más pagos)
               → CANCELLED

PAID → (inmutable)

OVERDUE → PAID (cuando se paga completo)
        → CANCELLED
```

### 8.7 Inventario: stock en cero

- Items con `stock = 0` siguen existiendo (no se eliminan automáticamente)
- `GET /pharmacy-inventories?low_stock=true` muestra items con `stock <= min_stock_alert`
- **UI:** Mostrar badge "Agotado" cuando `stock === 0`

### 8.8 Duplicidad de lotes

Constraint unique: `(provider_id, medication_id, batch_number)`.

Si se intenta agregar un item con mismo provider + medication + batch:
- HTTP 422 con error de validación
- **UI:** Si el pharmacy ya tiene ese medicamento de ese lote, sugerir actualizar stock existente en lugar de crear nuevo.

---

## 9. MANEJO DE ERRORES

### 9.1 Códigos de error del API

| Código | Significado | Acción frontend |
|--------|------------|----------------|
| 400 | Falta `Idempotency-Key` en POST | "Por favor intenta de nuevo" + generar nueva key |
| 401 | Token inválido o expirado | Auto-refrescar token. Si falla, redirigir a login |
| 401 | "Cuenta suspendida" | Mostrar pantalla de cuenta suspendida |
| 403 | Sin permisos | "No tienes acceso a este recurso" + contextual |
| 403 | KYC pendiente | Pantalla "Verificación en revisión" |
| 404 | Recurso no existe | "No encontrado" genérico |
| 422 | Validación fallida | Mapear errores al campo corresponding del form |
| 500 | Error interno | "Algo salió mal. Intenta más tarde." |

### 9.2 Mapeo de errores 422 a campos de formulario

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "patient_id": ["The patient id field is required."]
  }
}
```

```javascript
// Ejemplo de mapeo
Object.entries(errors).forEach(([field, messages]) => {
  const input = document.querySelector(`[name="${field}"]`);
  if (input) {
    input.setCustomError(messages[0]);
    input.showError();
  }
});
```

### 9.3 Estrategia de Retry con Idempotency Key

```
1. Generar UUIDv4 para cada intento de POST
2. Guardar UUID en storage (localStorage/sessionStorage)
3. Si la red falla:
   - Reintentar con el MISMO UUID
   - Server retorna respuesta cacheada (200) si ya procesó
4. Si el usuario cambia de pantalla:
   - El UUID persiste, el retry sigue siendo seguro
5. NO generar nuevo UUID por cada retry de red
```

### 9.4 Auto-refresh de token en 401

```javascript
// Interceptor de Axios/fetch
async function apiCallWithRefresh(url, options) {
  const response = await fetch(url, options);

  if (response.status === 401) {
    // Intentar refresh
    const refreshResponse = await post('/auth/refresh');
    if (refreshResponse.ok) {
      saveToken(refreshResponse.data.access_token);
      // Reintentar con nuevo token
      options.headers.Authorization = `Bearer ${newToken}`;
      return fetch(url, options);
    } else {
      // Refresh falló → logout
      redirectToLogin();
    }
  }

  return response;
}
```

---

## 10. REFERENCIA RÁPIDA DE RUTAS

### 10.1 Tabla resumen

| Módulo | Recurso | Endpoints | Idempotent | Rol |
|--------|---------|-----------|------------|-----|
| Auth | Patient | 5 (register, login, logout, refresh, me) | Sí (register, login) | patient |
| Auth | User | 5 (register doctor/provider, login, logout, refresh, me) | Sí (register, login) | user |
| Agenda | Appointments | 6 (CRUD) | POST | doctor |
| Agenda | FormTemplates | 6 (CRUD) | POST | doctor |
| Clínica | Consultations | 6 (CRUD) | POST | doctor |
| Clínica | VitalSigns | 4 (nested) | POST | doctor |
| Clínica | LabRequests | 4 (nested) | POST | doctor |
| Seguimiento | FollowUps | 6 (CRUD) | POST | doctor |
| Paciente | MedicalBackground | 4 (patient-scoped) | POST | doctor |
| Paciente | Lifestyle | 4 (patient-scoped) | POST | doctor |
| Paciente | ObstetricHistory | 4 (patient-scoped) | POST | doctor |
| Paciente | SurgicalHistories | 6 (CRUD, patient-scoped) | POST | doctor |
| Paciente | FamilyHistories | 6 (CRUD, patient-scoped) | POST | doctor |
| Paciente | Vaccinations | 6 (CRUD, patient-scoped) | POST | doctor |
| Phase 3 | Medications | 6 (CRUD) | POST | doctor |
| Phase 3 | Prescriptions | 6 (CRUD) | POST | doctor |
| Phase 3 | PrescriptionTemplates | 6 (CRUD) | POST | doctor |
| Phase 3 | MedicalDocuments | 6 (CRUD) | POST | doctor |
| Phase 3 | QuoteRequests | 6 (CRUD) | POST | patient |
| Phase 3 | QuoteOffers | 6 (nested) | POST | provider |
| Phase 4 | Notifications | 5 | No | user |
| Phase 4 | VerificationDocuments | 5 | POST | user |
| Phase 4 | LabResults | 6 | POST | provider |
| Phase 4 | PharmacyInventories | 8 | POST | provider |
| Phase 4 | Invoices | 7 | POST | doctor |
| Phase 4 | InvoiceItems | 4 (nested) | POST | doctor |
| Phase 4 | Payments | 4 (nested) | POST | doctor |
| Phase 4 | AuditLogs | 3 (read-only) | No | **admin only** |

### 10.2 Endpoints públicos (sin auth)

```
GET /api/v1/specialties                          — Lista especialidades
GET /api/v1/locations/cities                     — Lista ciudades
GET /api/v1/locations/cities?state_id={uuid}   — Ciudades por estado
```

### 10.3 Rutas implementadas (previas)

- `GET /consultations/{id}/pdf` — Exportar consulta a PDF
- `GET /prescriptions/{id}/pdf` — Exportar receta a PDF
- `GET /invoices/{id}/pdf` — Exportar factura a PDF
- `GET /medical-documents/{id}/pdf` — Exportar documento médico a PDF

---

## 11 Patient Portal (Phase 5)

### 11.1 Auth — Patient API

El patient portal usa el mismo flujo JWT que doctor/provider pero con `auth:patient_api`:

```javascript
// Login
POST /api/v1/auth/patients/login
Body: { "email": "...", "password": "..." }
Response: { "data": { "access_token": "...", "token_type": "bearer", "expires_in": 3600 } }

// Refresh
POST /api/v1/auth/patients/refresh
Headers: Authorization: Bearer {token}

// Logout
POST /api/v1/auth/patients/logout
Headers: Authorization: Bearer {token}

// Usuario actual
GET /api/v1/auth/patients/me
Headers: Authorization: Bearer {token}
```

### 11.2 Patient Portal Endpoints

**Base path:** `/api/v1/patients/me`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/appointments` | Lista de citas del paciente |
| GET | `/appointments/{id}` | Detalle de cita |
| GET | `/consultations` | Lista de consultas |
| GET | `/consultations/{id}` | Detalle de consulta |
| GET | `/prescriptions` | Lista de recetas |
| GET | `/prescriptions/{id}` | Detalle de receta |
| GET | `/quote-requests` | Lista de solicitudes de cotización |
| GET | `/quote-requests/{id}` | Detalle de solicitud |
| GET | `/quote-requests/{id}/offers` | Ofertas de una solicitud |
| GET | `/lab-results` | Lista de resultados de laboratorio |
| GET | `/lab-results/{id}` | Detalle de resultado |
| GET | `/invoices` | Lista de facturas |
| GET | `/invoices/{id}` | Detalle de factura |
| GET | `/invoices/{id}/payments` | Pagos de una factura |
| GET | `/notifications` | Lista de notificaciones |
| GET | `/notifications/{id}` | Detalle de notificación |
| PATCH | `/notifications/{id}/read` | Marcar como leída |
| POST | `/notifications/read-all` | Marcar todas como leídas |
| GET | `/notifications/unread-count` | Count de no leídas |
| GET | `/medical-documents` | Lista de documentos médicos |
| GET | `/medical-documents/{id}` | Detalle de documento |

### 11.3 Public Verification (sin auth)

Para verificar recetas/documentos por QR:

```
GET /api/v1/verify/prescription/{publicToken}
GET /api/v1/verify/document/{publicToken}
```

Response ejemplo:
```json
{
  "data": {
    "type": "prescription",
    "valid": true,
    "prescription": {
      "id": "uuid",
      "date": "2026-06-23T10:00:00Z",
      "status": "active",
      "doctor": { "name": "Dr. ...", "professional_id": "...", "specialty": "..." },
      "patient": { "name": "...", "national_id": "..." },
      "clinic": "Nombre Clínica",
      "items": [...]
    }
  }
}
```

### 11.4 Permisos del Patient Portal

El patient portal solo permite **lectura** (no write). El paciente puede:
- Ver sus citas, consultas, recetas, resultados, facturas
- Ver ofertas de farmacias para sus recetas
- Marcar notificaciones como leídas
- Ver sus documentos médicos

---

## Anexo: Notas de la Guía

- **Última actualización:** 2026-06-23
- **Versión del API:** v1
- **Versión del backend:** Phase 1-4 completo, Phase 5 completo
- **Issues conocidos:**
  - WebSockets no implementados — polling para notificaciones y cotizaciones
  - Sobrepago de facturas no validado en backend
