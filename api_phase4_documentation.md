# LUCA Health OS — API Phase 4 Documentation

> **Operaciones, Facturación y Auditoría HIPAA**

Versión: 1.0
Fecha: 2026-06-22
Base URL: `/api/v1`

---

## Autenticación

Todos los endpoints requieren header de autenticación:

```
Authorization: Bearer {token}
Accept: application/json
```

**Guards disponibles:**
- `auth:user_api` — doctors, providers, staff, admins
- `auth:patient_api` — pacientes

**Roles disponibles:**
- `ADMIN` — acceso total, puede ver audit logs
- `DOCTOR` — acceso a sus datos y pacientes
- `PROVIDER` — farmacias/laboratorios
- `PATIENT` — pacientes

---

## Idempotency — REQUERIDO en todos los POST

**Header obligatorio:** `Idempotency-Key: {uuid}`

Sin este header → **400 Bad Request**.

- Cache de respuesta: 24 horas
- Si la key ya fue usada → retorna la respuesta original (200)
- Recomendación: generar UUIDv4 por cada intento de creación

---

# MÓDULO: NOTIFICACIONES

## Notificaciones In-App

### Endpoints

```
GET    /notifications                      # Listar mis notificaciones
GET    /notifications/{id}                 # Ver notificación
PATCH  /notifications/{id}/read            # Marcar como leída
POST   /notifications/read-all             # Marcar todas como leídas
GET    /notifications/unread-count         # Contador de no leídas
```

### GET /notifications

**Headers:** `Authorization`

Lista solo las notificaciones del usuario autenticado, ordenadas por fecha (más recientes primero).

**Respuesta 200:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid",
        "uuid": "uuid",
        "user_id": "uuid",
        "type": "NEW_QUOTE_REQUEST",
        "title": "Nueva cotización recibida",
        "message": "Farmacia San José envió una cotización para tu receta #1234",
        "is_read": false,
        "link": "/quotes/requests/abc123",
        "created_at": "2026-06-22T10:00:00Z"
      }
    ],
    "per_page": 20,
    "total": 45
  }
}
```

### Tipos de Notificación

| Type | Descripción |
|------|-------------|
| `SYSTEM` | Notificación del sistema |
| `NEW_QUOTE_REQUEST` | Nueva solicitud de cotización |
| `QUOTE_RECEIVED` | Una farmacia respondió a tu solicitud |
| `FOLLOW_UP_ALERT` | Recordatorio de seguimiento |
| `LAB_RESULT_READY` | Resultados de laboratorio disponibles |
| `INVOICE_RECEIVED` | Nueva factura recibida |

### POST /notifications/read-all

Marca todas las notificaciones no leídas como leídas.

**Headers:** `Authorization`, `Idempotency-Key`

**Body:** `{}` (vacío, idempotent)

**Respuesta 200:**
```json
{
  "data": {
    "marked_count": 12
  }
}
```

### PATCH /notifications/{id}/read

Marca una notificación específica como leída.

**Headers:** `Authorization`

**Respuesta 200:**
```json
{
  "data": {
    "id": "uuid",
    "is_read": true,
    ...
  }
}
```

### GET /notifications/unread-count

Retorna el número de notificaciones no leídas.

**Headers:** `Authorization`

**Respuesta 200:**
```json
{
  "data": {
    "count": 5
  }
}
```

---

# MÓDULO: VERIFICACIÓN KYC

## Documentos de Verificación

### Endpoints

```
GET    /verification-documents                # Listar mis documentos
POST   /verification-documents                # Subir documento (idempotent)
GET    /verification-documents/{id}           # Ver documento
PUT    /verification-documents/{id}           # Actualizar (solo status/comments admin)
PATCH  /verification-documents/{id}           # Actualizar parcialmente
```

### POST /verification-documents

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "type": "MEDICAL_LICENSE (required)",
  "file_url": "https://storage.example.com/docs/license.pdf (required, URL)"
}
```

**Valores válidos `type`:**

| Type | Descripción | Requerido para |
|------|-------------|----------------|
| `MEDICAL_LICENSE` | Licencia médica | Doctors |
| `NATIONAL_ID` | Cédula de identidad | Todos |
| `BUSINESS_RIF` | RIF de negocio | Providers (farmacias/labs) |

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "user_id": "uuid",
    "type": "MEDICAL_LICENSE",
    "file_url": "https://storage.example.com/docs/license.pdf",
    "status": "PENDING",
    "comments": null,
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

### Estados de Verificación

| Status | Descripción |
|--------|-------------|
| `PENDING` | En revisión |
| `APPROVED` | Aprobado |
| `REJECTED` | Rechazado (ver `comments` para razón) |

### PUT /verification-documents/{id} (Admin only)

Actualiza el estado y comentarios de un documento de verificación.

**Headers:** `Authorization` (ADMIN)

**Body:**
```json
{
  "status": "APPROVED (required)",
  "comments": "Documento verificado y aprobado (optional)"
}
```

**Respuesta 200:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "user_id": "uuid",
    "type": "MEDICAL_LICENSE",
    "file_url": "https://storage.example.com/docs/license.pdf",
    "status": "APPROVED",
    "comments": "Documento verificado y aprobado",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T11:00:00Z"
  }
}
```

### Permissions

- **Usuarios:** Solo pueden ver/subir sus propios documentos
- **Admins:** Pueden actualizar `status` y `comments` de cualquier documento

---

# MÓDULO: RESULTADOS DE LABORATORIO

## Resultados de Lab (LabResults)

### Endpoints

```
GET    /lab-results                          # Listar resultados
POST   /lab-results                         # Crear resultado (idempotent)
GET    /lab-results/{id}                    # Ver resultado
PUT    /lab-results/{id}                    # Actualizar
PATCH  /lab-results/{id}                    # Actualizar parcialmente
POST   /lab-results/{id}/review            # Marcar como revisado por médico
```

### POST /lab-results

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "lab_request_id": "uuid (required - orden de laboratorio asociada, 1:1)",
  "patient_id": "uuid (required)",
  "file_url": "https://storage.example.com/results/abc123.pdf (optional)",
  "result_json": {
    "hemoglobina": { "value": 14.5, "unit": "g/dL", "reference": "12-16" },
    "glucosa": { "value": 95, "unit": "mg/dL", "reference": "70-100" }
  },
  "notes": "Resultados dentro de parámetros normales (optional)",
  "status": "PENDING (optional, default: PENDING)",
  "performed_at": "2026-06-22T08:30:00Z (optional - fecha real del examen)"
}
```

**Sobre `result_json`:**
- Formato JSONB libre para resultados estructurados
- El laboratorio puede enviar PDF (`file_url`) O datos estructurados (`result_json`) o ambos
- `reference` indica el rango de referencia para interpretación

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "lab_request_id": "uuid",
    "patient_id": "uuid",
    "file_url": "https://storage.example.com/results/abc123.pdf",
    "result_json": {
      "hemoglobina": { "value": 14.5, "unit": "g/dL", "reference": "12-16" },
      "glucosa": { "value": 95, "unit": "mg/dL", "reference": "70-100" }
    },
    "notes": "Resultados dentro de parámetros normales",
    "reviewed_by": null,
    "reviewed_at": null,
    "status": "PENDING",
    "performed_at": "2026-06-22T08:30:00Z",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

### Estados de LabResult

| Status | Descripción |
|--------|-------------|
| `PENDING` | Pendiente de resultados |
| `COMPLETED` | Resultados recibidos y completados |
| `ABNORMAL` | Resultados fuera de rango normal |
| `CANCELLED` | Orden cancelada |

### POST /lab-results/{id}/review

Marca el resultado como revisado por el médico y cambia status a `COMPLETED`.

**Headers:** `Authorization` (doctor)

**Respuesta 200:**
```json
{
  "data": {
    "id": "uuid",
    "status": "COMPLETED",
    "reviewed_by": "uuid (doctor que revisó)",
    "reviewed_at": "2026-06-22T11:00:00Z",
    ...
  }
}
```

### HIPAA: Log de Auditoría

Cada acceso a `GET /lab-results` dispara automáticamente un `AuditLog` con:
- `action: VIEW`
- `resource_type: LabResult`
- `resource: {lab_result_id}`
- `patient_id: {patient_id}`

---

# MÓDULO: INVENTARIO DE FARMACIA

## Inventario de Farmacias

### Endpoints

```
GET    /pharmacy-inventories                      # Listar inventario
POST   /pharmacy-inventories                      # Agregar item (idempotent)
GET    /pharmacy-inventories/{id}                  # Ver item
PUT    /pharmacy-inventories/{id}                  # Actualizar
PATCH  /pharmacy-inventories/{id}                  # Actualizar parcialmente
DELETE /pharmacy-inventories/{id}                  # Eliminar item
GET    /pharmacy-inventories/alerts/low-stock      # Alertas de stock bajo
GET    /pharmacy-inventories/alerts/expired        # Alertas de productos vencidos
```

### GET /pharmacy-inventories

**Headers:** `Authorization`

**Query params:**
| Param | Descripción |
|-------|-------------|
| `low_stock` | `true` — solo items con stock bajo |
| `expired` | `true` — solo items vencidos |

**Respuesta 200:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid",
        "uuid": "uuid",
        "provider_id": "uuid",
        "medication_id": "uuid",
        "stock": 50,
        "min_stock_alert": 10,
        "batch_number": "LOTE-2026-001",
        "expiration_date": "2027-06-22",
        "unit_price": "12.50",
        "created_at": "2026-06-22T10:00:00Z",
        "updated_at": "2026-06-22T10:00:00Z",
        "medication": {
          "id": "uuid",
          "active_principle": "Paracetamol",
          "concentration": "500mg",
          "presentation": "TABLETA",
          "commercial_name": "Panadol"
        },
        "provider": {
          "id": "uuid",
          "commercial_name": "Farmacia San José"
        }
      }
    ]
  }
}
```

### POST /pharmacy-inventories

**Headers:** `Authorization` (provider), `Idempotency-Key`

**Body:**
```json
{
  "provider_id": "uuid (required - pharmacy profile)",
  "medication_id": "uuid (required)",
  "stock": 100 (required, min: 0),
  "min_stock_alert": 10 (optional, default: 10),
  "batch_number": "LOTE-2026-001 (optional)",
  "expiration_date": "2027-06-22 (optional)",
  "unit_price": 12.50 (optional, DECIMAL(10,2))
}
```

**Restricción unique:** `(provider_id, medication_id, batch_number)` — no puede haber lotes duplicados.

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "provider_id": "uuid",
    "medication_id": "uuid",
    "stock": 100,
    "min_stock_alert": 10,
    "batch_number": "LOTE-2026-001",
    "expiration_date": "2027-06-22",
    "unit_price": "12.50",
    ...
  }
}
```

### GET /pharmacy-inventories/alerts/low-stock

Retorna items donde `stock <= min_stock_alert`.

**Headers:** `Authorization`

**Respuesta 200:**
```json
{
  "data": [
    {
      "id": "uuid",
      "medication": { "active_principle": "Amoxicilina", "commercial_name": "Amoxil" },
      "stock": 5,
      "min_stock_alert": 10
    }
  ]
}
```

### Permissions

- **Providers:** Solo ven/modifican su propio inventario
- **Admins:** Ven todo el inventario de todos los providers

---

# MÓDULO: FACTURACIÓN

## Facturas (Invoices)

### Endpoints

```
GET    /invoices                                 # Listar facturas
POST   /invoices                                 # Crear factura (idempotent)
GET    /invoices/{id}                            # Ver factura
PUT    /invoices/{id}                            # Actualizar (solo DRAFT)
PATCH  /invoices/{id}                            # Actualizar parcialmente
DELETE /invoices/{id}                           # Eliminar (solo DRAFT, CASCADE items y payments)
POST   /invoices/{id}/send                       # Enviar factura al paciente
```

### POST /invoices

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "patient_id": "uuid (required)",
  "clinic_branch_id": "uuid (optional - sede donde se facturó)",
  "consultation_id": "uuid (optional)",
  "prescription_id": "uuid (optional)",
  "subtotal": 100.00 (optional - se calcula de items si no se provee)",
  "tax": 16.00 (optional, default: 0)",
  "discount": 0.00 (optional, default: 0)",
  "total": 116.00 (optional - se calcula si no se provee)",
  "currency": "USD (optional, default: USD)",
  "status": "DRAFT (optional, default: DRAFT)",
  "due_date": "2026-07-22 (optional)",
  "notes": "Primera consulta del mes (optional)",
  "items": [
    {
      "description": "Consulta médica general (required)",
      "quantity": 1 (optional, default: 1),
      "unit_price": 50.00 (optional, default: 0),
      "total": 50.00 (optional - se calcula: quantity * unit_price)
    },
    {
      "description": "Laboratorio clínico",
      "quantity": 1,
      "unit_price": 50.00,
      "total": 50.00
    }
  ]
}
```

**Cálculo automático de totales:**
- Si no se provee `subtotal` ni `total`: se calculan de `items`
- `total = subtotal + tax - discount`

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "user_id": "uuid",
    "patient_id": "uuid",
    "clinic_branch_id": "uuid",
    "consultation_id": "uuid",
    "prescription_id": "uuid",
    "subtotal": "100.00",
    "tax": "16.00",
    "discount": "0.00",
    "total": "116.00",
    "currency": "USD",
    "status": "DRAFT",
    "due_date": "2026-07-22",
    "notes": "Primera consulta del mes",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z",
    "items": [
      {
        "id": "uuid",
        "description": "Consulta médica general",
        "quantity": 1,
        "unit_price": "50.00",
        "total": "50.00"
      }
    ],
    "patient": { "id": "uuid", "name": "María García", ... },
    "user": { "id": "uuid", "name": "Dr. Juan Pérez", ... }
  }
}
```

### Estados de Invoice

| Status | Descripción | Acciones permitidas |
|--------|-------------|---------------------|
| `DRAFT` | Borrador | Editar, Eliminar, Enviar |
| `SENT` | Enviada al paciente | Registrar pagos |
| `PAID` | Pagada completamente | Ninguna |
| `PARTIALLY_PAID` | Pago parcial | Registrar más pagos |
| `OVERDUE` | Vencida | Registrar pagos |
| `CANCELLED` | Cancelada | Ninguna |

### DELETE /invoices/{id}

**Reglas:**
- Solo facturas en estado `DRAFT` pueden eliminarse
- Al eliminar se borran automáticamente `InvoiceItems` y `Payments` (CASCADE)
- Otros estados retornan `422`

### POST /invoices/{id}/send

Cambia estado de `DRAFT` a `SENT`.

**Headers:** `Authorization`

**Respuesta 200:**
```json
{
  "data": {
    "id": "uuid",
    "status": "SENT",
    ...
  }
}
```

---

## Items de Factura (Nested)

### Endpoints

```
GET    /invoices/{invoice}/items                 # Listar items
POST   /invoices/{invoice}/items                 # Agregar item (idempotent)
GET    /invoices/{invoice}/items/{item}          # Ver item
DELETE /invoices/{invoice}/items/{item}          # Eliminar item (solo si invoice DRAFT)
```

> **Nota:** Los items de factura **no tienen PUT/PATCH**. Para modificar un item existente, elimínalo (`DELETE`) y crea uno nuevo con los datos corregidos.

### POST /invoices/{invoice}/items

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "description": "Nueva consulta de control (required)",
  "quantity": 1 (optional, default: 1),
  "unit_price": 30.00 (optional, default: 0)
}
```

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "invoice_id": "uuid",
    "description": "Nueva consulta de control",
    "quantity": 1,
    "unit_price": "30.00",
    "total": "30.00",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

---

## Pagos (Nested)

### Endpoints

```
GET    /invoices/{invoice}/payments                 # Listar pagos
POST   /invoices/{invoice}/payments                 # Registrar pago (idempotent)
GET    /invoices/{invoice}/payments/{payment}       # Ver pago
DELETE /invoices/{invoice}/payments/{payment}       # Eliminar pago (solo admin)
```

> **Nota:** Los pagos **no tienen PUT/PATCH**. Para corregir un pago, elimínalo (`DELETE`) y crea uno nuevo. Solo admins pueden eliminar pagos.

### POST /invoices/{invoice}/payments

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "amount": 50.00 (required, min: 0.01),
  "method": "CARD (required)",
  "reference": "txn_abc123 (optional - ID de Stripe/Zelle)",
  "paid_at": "2026-06-22T14:30:00Z (optional, default: now)",
  "notes": "Pago con tarjeta de crédito (optional)"
}
```

**Valores válidos `method`:**

| Method | Descripción |
|--------|-------------|
| `CASH` | Efectivo |
| `CARD` | Tarjeta de débito/crédito |
| `TRANSFER` | Transferencia bancaria |
| `INSURANCE` | Seguro médico |
| `OTHER` | Otro método |

**Actualización automática de estado:**
- Si `totalPaid >= total` → Invoice status = `PAID`
- Si `totalPaid > 0` y `< total` → Invoice status = `PARTIALLY_PAID`

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "invoice_id": "uuid",
    "amount": "50.00",
    "method": "CARD",
    "reference": "txn_abc123",
    "paid_at": "2026-06-22T14:30:00Z",
    "notes": "Pago con tarjeta de crédito",
    "created_at": "2026-06-22T14:30:00Z",
    "updated_at": "2026-06-22T14:30:00Z"
  }
}
```

---

# MÓDULO: AUDITORÍA HIPAA

## Logs de Auditoría

> **CRÍTICO:** Los logs de auditoría son INMUTABLES. No existen endpoints de creación, modificación o eliminación.

### Endpoints (SOLO LECTURA)

```
GET    /audit-logs                           # Listar logs (admin only)
GET    /audit-logs/{id}                      # Ver log específico (admin only)
GET    /audit-logs/patient/{patient_id}      # Historial de un paciente (admin only)
```

### GET /audit-logs

**Headers:** `Authorization` (ADMIN only)

**Query params:**
| Param | Descripción |
|-------|-------------|
| `user_id` | Filtrar por usuario que realizó la acción |
| `patient_id` | Filtrar por paciente afectado |
| `action` | Filtrar por acción (VIEW, CREATE, UPDATE, DELETE) |
| `resource_type` | Filtrar por tipo de recurso (Consultation, Prescription, etc.) |
| `from` | Fecha inicio (ISO 8601) |
| `to` | Fecha fin (ISO 8601) |

**Respuesta 200:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid",
        "uuid": "uuid",
        "user_id": "uuid",
        "patient_id": "uuid",
        "action": "VIEW",
        "resource": "uuid-del-recurso",
        "resource_type": "Consultation",
        "details": null,
        "ip_address": "192.168.1.100",
        "user_agent": "Mozilla/5.0...",
        "created_at": "2026-06-22T10:00:00Z",
        "user": { "id": "uuid", "name": "Dr. Juan Pérez", "role": "DOCTOR" },
        "patient": { "id": "uuid", "name": "María García" }
      }
    ],
    "per_page": 50,
    "total": 1523
  }
}
```

### Acciones Auditadas

| Action | Descripción | Cuándo se registra |
|--------|-------------|-------------------|
| `VIEW` | Visualización | GET a recursos clínicos |
| `CREATE` | Creación | POST a recursos |
| `UPDATE` | Modificación | PUT/PATCH a recursos |
| `DELETE` | Eliminación | DELETE de recursos |
| `EXPORT` | Exportación | Descarga de reportes |
| `PRINT` | Impresión | Impresión de documentos |

### HIPAA: Implementación Automática

El backend automáticamente registra estas acciones:

1. **VIEW automático:** Todo `GET` a:
   - Consultations
   - Prescriptions
   - MedicalDocuments
   - LabResults
   - Patient records

2. **CREATE/UPDATE/DELETE:** Todos los endpoints POST/PUT/PATCH/DELETE

### Permissions

| Rol | Acceso a Audit Logs |
|-----|---------------------|
| `ADMIN` | Acceso total |
| `DOCTOR` | No tiene acceso |
| `PROVIDER` | No tiene acceso |
| `PATIENT` | No tiene acceso |

Intentar acceder con otro rol → **403 Forbidden**

---

# CÓDIGOS DE ERROR

| Código | Descripción |
|--------|-------------|
| 400 | Bad Request — falta `Idempotency-Key` en POST |
| 401 | Unauthorized — token inválido o expirado |
| 403 | Forbidden — no tienes permisos para este recurso |
| 404 | Not Found — recurso no existe |
| 422 | Validation Error — datos inválidos |
| 500 | Internal Server Error |

**Error 400 por falta de idempotency key:**
```json
{
  "message": "Idempotency Key is required"
}
```

**Error 403 (no autorizado):**
```json
{
  "error": "Unauthorized"
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

# RESUMEN DE RUTAS PHASE 4

| Recurso | Endpoints | Idempotent |
|---------|-----------|------------|
| Notifications | 5 (list, show, mark-read, mark-all-read, count) | No |
| VerificationDocuments | 5 (list, store, show, update, patch) | POST |
| LabResults | 6 (list, store, show, update, patch, review) | POST |
| PharmacyInventories | 8 (CRUD + 2 alerts) | POST |
| Invoices | 7 (CRUD + send) | POST |
| InvoiceItems | 4 (nested CRUD) | POST |
| Payments | 4 (nested CRUD) | POST |
| AuditLogs | 3 (read-only, admin only) | No |
| **TOTAL Phase 4** | **~42** | **6 POST** |

---

# NOTAS DE IMPLEMENTACIÓN

## Facturas y Workflow

```
1. Doctor crea consulta → POST /consultations
2. Si hay costo adicional → POST /invoices (status: DRAFT)
3. Agregar items → POST /invoices/{id}/items
4. Ajustar totales si es necesario
5. Enviar factura → POST /invoices/{id}/send (status: SENT)
6. Paciente paga → POST /invoices/{id}/payments
7. Si pago completo → status: PAID automáticamente
```

## Inventario y Marketplace

```
1. Farmacia recibe productos → POST /pharmacy-inventories
2. Items con stock bajo → GET /pharmacy-inventories/alerts/low-stock
3. Items próximos a vencer → GET /pharmacy-inventories/alerts/expired
4. Cuando llega una cotización → verificar disponibilidad en inventario
```

## HIPAA Compliance Checklist

- [ ] Todos los GET a recursos clínicos registran AuditLog
- [ ] Logs no pueden ser eliminados (no existe endpoint DELETE)
- [ ] user_id en AuditLog usa ON DELETE SET NULL
- [ ] Solo ADMIN puede ver AuditLogs
- [ ] Pacientes no pueden ver datos de otros pacientes

---

# ENDPOINTS PÚBLICOS (SIN AUTH)

| Endpoint | Descripción |
|----------|-------------|
| `GET /specialties` | Lista de especialidades médicas |
| `GET /locations/cities?state_id={uuid}` | Ciudades por estado |
