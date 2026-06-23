# LUCA Health OS — API Phase 3 Documentation

> **Vademécum, Recetas y Marketplace B2B2C**

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

## Vademécum (Medicamentos)

### Endpoints

```
GET    /medications              # Listar medicamentos
POST   /medications              # Crear (idempotent)
GET    /medications/{id}         # Ver
PUT    /medications/{id}         # Actualizar
PATCH  /medications/{id}         # Actualizar parcialmente
DELETE /medications/{id}         # Eliminar
```

### GET /medications

Lista medicamentos del Vademécum global (user_id = NULL) + medicamentos privados del doctor autenticado.

**Headers:** `Authorization`

**Respuesta 200:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid",
        "uuid": "uuid",
        "user_id": null,
        "active_principle": "Paracetamol",
        "concentration": "500mg",
        "presentation": "TABLETA",
        "administration_route": "ORAL",
        "commercial_name": "Panadol",
        "requires_prescription": true,
        "contraindications": "Hipersensibilidad al paracetamol",
        "is_active": true,
        "created_at": "2026-06-22T10:00:00Z",
        "updated_at": "2026-06-22T10:00:00Z"
      }
    ],
    "per_page": 20,
    "total": 150
  }
}
```

### POST /medications

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "user_id": "uuid (nullable - null = Vademécum Global, NOT NULL = privado del doctor)",
  "active_principle": "Paracetamol (required)",
  "concentration": "500mg (required)",
  "presentation": "TABLETA (required)",
  "administration_route": "ORAL (required)",
  "commercial_name": "Panadol (optional)",
  "requires_prescription": true,
  "contraindications": "Hipersensibilidad (optional)",
  "is_active": true
}
```

**Valores válidos `presentation`:**
| Valor | Descripción |
|-------|-------------|
| `CAPSULA` | Cápsula |
| `TABLETA` | Tableta |
| `JARABE` | Jarabe |
| `GOTAS` | Gotas |
| `AMPOLLA` | Ampolla |
| `CREMA` | Crema |

**Valores válidos `administration_route`:**
| Valor | Descripción |
|-------|-------------|
| `ORAL` | Oral |
| `INTRAVENOSA` | Intravenosa |
| `TOPICA` | Tópica |
| `INTRAMUSCULAR` | Intramuscular |
| `SUBCUTANEA` | Subcutánea |
| `RECTAL` | Rectal |
| `INHALATORIA` | Inhalatoria |
| `SUBLINGUAL` | Sublingual |
| `TRANSDERMICA` | Transdérmica |

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "user_id": null,
    "active_principle": "Paracetamol",
    "concentration": "500mg",
    "presentation": "TABLETA",
    "administration_route": "ORAL",
    "commercial_name": "Panadol",
    "requires_prescription": true,
    "contraindications": "Hipersensibilidad",
    "is_active": true,
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

### Permissions

- **Vademécum Global (user_id=null):** Solo admins pueden crear/modificar
- **Medicamentos Privados:** Solo el doctor dueño puede modificar/eliminar

---

## Recetas (Prescriptions)

### Endpoints

```
GET    /prescriptions              # Listar recetas
POST   /prescriptions              # Crear (idempotent)
GET    /prescriptions/{id}         # Ver
PUT    /prescriptions/{id}         # Actualizar
PATCH  /prescriptions/{id}         # Actualizar parcialmente
DELETE /prescriptions/{id}         # Eliminar
```

### POST /prescriptions

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "user_id": "uuid (required - doctor que receta)",
  "patient_id": "uuid (required)",
  "consultation_id": "uuid (optional - consulta asociada, 1:1)",
  "clinic_branch_id": "uuid (optional - sede física donde se recetó)",
  "date": "2026-06-22 (required)",
  "expiration_date": "2026-07-22 (required)",
  "notes": "Tomar con alimentos (optional)",
  "status": "ACTIVE (optional, default: ACTIVE)",
  "items": [
    {
      "medication_id": "uuid (optional - FK a medications, null = texto libre)",
      "dose": "1 tableta (optional)",
      "frequency": "Cada 8 horas (optional)",
      "duration": "7 días (optional)",
      "quantity": 2 (optional, default: 1)",
      "notes": "Después de comer (optional)"
    }
  ]
}
```

**Notas sobre `items`:**
- `medication_id` es opcional (retrocompatibilidad con texto libre)
- Si se provee `medication_id`, el sistema usa el medicamento del Vademécum
- El campo `quantity` indica cuántas cajas debe comprar el paciente

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "user_id": "uuid",
    "patient_id": "uuid",
    "consultation_id": "uuid",
    "clinic_branch_id": "uuid",
    "date": "2026-06-22T10:00:00Z",
    "expiration_date": "2026-07-22T10:00:00Z",
    "notes": "Tomar con alimentos",
    "public_token": "a1b2c3d4e5f6g7h8",
    "status": "ACTIVE",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z",
    "items": [
      {
        "id": "uuid",
        "medication_id": "uuid",
        "dose": "1 tableta",
        "frequency": "Cada 8 horas",
        "duration": "7 días",
        "quantity": 2,
        "notes": "Después de comer"
      }
    ],
    "patient": { ... },
    "user": { ... }
  }
}
```

### El campo `public_token`

Token único de 16 caracteres generado automáticamente para cada receta.

**Uso:** Se incrusta en el código QR impreso de la receta.

Las farmacias lo usan para:
1. Verificar autenticidad de la receta
2. Acceder a los detalles de medicamentos recetados
3. Crear cotizaciones basadas en la receta

### Estados de Receta

| Status | Descripción |
|--------|-------------|
| `ACTIVE` | Vigente, puede ser dispensada |
| `CANCELLED` | Cancelada por el médico |
| `EXPIRED` | Vencida (pasó expiration_date) |

---

## Plantillas de Recetas

### Endpoints

```
GET    /prescription-templates           # Listar plantillas
POST   /prescription-templates           # Crear (idempotent)
GET    /prescription-templates/{id}      # Ver
PUT    /prescription-templates/{id}      # Actualizar
PATCH  /prescription-templates/{id}      # Actualizar parcialmente
DELETE /prescription-templates/{id}      # Eliminar
```

### POST /prescription-templates

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "title": "Post-operatorio Apendicitis (required)",
  "items": [
    {
      "medication_id": "uuid (optional)",
      "dose": "1 cápsula (optional)",
      "frequency": "Cada 12 horas (optional)",
      "duration": "7 días (optional)",
      "notes": "Con comida (optional)"
    }
  ]
}
```

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "user_id": "uuid",
    "title": "Post-operatorio Apendicitis",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z",
    "items": [
      {
        "id": "uuid",
        "medication_id": "uuid",
        "dose": "1 cápsula",
        "frequency": "Cada 12 horas",
        "duration": "7 días",
        "notes": "Con comida"
      }
    ]
  }
}
```

### Permissions

Solo el doctor que creó la plantilla puede verla, modificarla o eliminarla.

---

## Documentos Médicos Legales

### Endpoints

```
GET    /medical-documents           # Listar documentos
POST   /medical-documents           # Crear (idempotent)
GET    /medical-documents/{id}      # Ver
PUT    /medical-documents/{id}      # Actualizar
PATCH  /medical-documents/{id}      # Actualizar parcialmente
DELETE /medical-documents/{id}      # Eliminar
```

### POST /medical-documents

**Headers:** `Authorization`, `Idempotency-Key`

**Body:**
```json
{
  "patient_id": "uuid (required)",
  "clinic_branch_id": "uuid (optional - sede donde se emitió)",
  "type": "CERTIFICATE (required)",
  "content": "Contenido del documento médico legal (required)"
}
```

**Valores válidos `type`:**

| Tipo | Descripción | Uso típico |
|------|-------------|-----------|
| `CERTIFICATE` | Certificados médicos | Reposos laborales |
| `REFERRAL` | Referencias | Referencia a otro especialista |
| `REPORT` | Informes médicos | Informes de alta |

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "user_id": "uuid",
    "patient_id": "uuid",
    "clinic_branch_id": "uuid",
    "type": "CERTIFICATE",
    "content": "Contenido del documento...",
    "public_token": "x1y2z3w4v5u6t7s8",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z"
  }
}
```

### El campo `public_token`

Similar a las recetas, los documentos médicos tienen un `public_token` para:
- **Reposos laborales:** El empleador puede verificar autenticidad escaneando el QR
- **Referencias:** El especialista receptor puede validar el documento

---

## Marketplace B2B2C — Solicitudes de Cotización

### Flujo del Marketplace

```
1. Paciente recibe receta → Solicita cotización
2. Farmacias en esa ciudad ven la solicitud → Envían ofertas
3. Paciente compara precios → Elige dónde comprar
```

### Endpoints

```
GET    /quote-requests              # Listar solicitudes
POST   /quote-requests              # Crear (idempotent)
GET    /quote-requests/{id}         # Ver
PUT    /quote-requests/{id}         # Actualizar
PATCH  /quote-requests/{id}         # Actualizar parcialmente
DELETE /quote-requests/{id}         # Eliminar
```

### POST /quote-requests

**Headers:** `Authorization` (patient_api), `Idempotency-Key`

**Body:**
```json
{
  "prescription_id": "uuid (required)",
  "patient_id": "uuid (required)",
  "city_id": "uuid (required - ciudad donde busca farmacias)"
}
```

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "prescription_id": "uuid",
    "patient_id": "uuid",
    "city_id": "uuid",
    "status": "OPEN",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z",
    "prescription": {
      "id": "uuid",
      "items": [...],
      "patient": {...}
    },
    "offers": []
  }
}
```

### Estados de QuoteRequest

| Status | Descripción |
|--------|-------------|
| `OPEN` | Recibiendo ofertas de farmacias |
| `CLOSED` | Finalizada (comprada o vencida) |

---

## Marketplace B2B2C — Ofertas de Cotización

### Endpoints

```
GET    /quote-requests/{id}/offers           # Listar ofertas
POST   /quote-requests/{id}/offers           # Crear (idempotent)
GET    /quote-requests/{id}/offers/{oid}      # Ver
PUT    /quote-requests/{id}/offers/{oid}      # Actualizar
PATCH  /quote-requests/{id}/offers/{oid}      # Actualizar parcialmente
DELETE /quote-requests/{id}/offers/{oid}      # Eliminar
```

### POST /quote-requests/{id}/offers

**Headers:** `Authorization` (provider - pharmacy/lab), `Idempotency-Key`

**Body:**
```json
{
  "provider_id": "uuid (required - ID del perfil de la farmacia/lab)",
  "price": 25.50 (required - DECIMAL(10,2), NUNCA float)",
  "currency": "USD (optional, default: USD)",
  "availability": "Entrega inmediata (optional)",
  "comments": "Disponible en todas las presentaciones (optional)"
}
```

**Importante:** El campo `price` es `DECIMAL(10,2)` para precisión monetaria.

**Respuesta 201:**
```json
{
  "data": {
    "id": "uuid",
    "uuid": "uuid",
    "quote_request_id": "uuid",
    "provider_id": "uuid",
    "price": "25.50",
    "currency": "USD",
    "availability": "Entrega inmediata",
    "comments": "Disponible en todas las presentaciones",
    "created_at": "2026-06-22T10:00:00Z",
    "updated_at": "2026-06-22T10:00:00Z",
    "provider_profile": {
      "id": "uuid",
      "commercial_name": "Farmacia San José",
      "address": "Av. Principal #123",
      "phone": "0212-1234567",
      "is_verified": true
    }
  }
}
```

---

## Perfiles de Proveedores (Farmacias/Labs)

### Modelo de Datos

El perfil del proveedor ya existe en Phase 1, con estos campos adicionales de Phase 3:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `address` | VARCHAR | Dirección de la farmacia |
| `phone` | VARCHAR | Teléfono de contacto |
| `city_id` | UUID FK | Ciudad normalizada (para búsqueda) |
| `is_open` | BOOLEAN | Si acepta cotizaciones (switch) |

### Búsqueda de Proveedores

Para buscar farmacias por ciudad:

```
GET /locations/cities?state_id={uuid}  # Ver Phase 1
```

Los proveedores disponibles se filtran por `city_id` + `is_open = true`.

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

## Resumen de Rutas Phase 3

| Recurso | Endpoints | Idempotent |
|---------|-----------|------------|
| Medications | 6 (GET list, POST, GET show, PUT, PATCH, DELETE) | POST |
| Prescriptions | 6 | POST |
| PrescriptionTemplates | 6 | POST |
| MedicalDocuments | 6 | POST |
| QuoteRequests | 6 | POST |
| QuoteOffers | 6 (nested) | POST |
| **TOTAL Phase 3** | **36** | **6 POST** |

---

## Notas de Implementación para Frontend

### Generación de QR para Recetas

El `public_token` de la receta debe mostrarse como código QR:

1. **Datos del QR:** El `public_token` de 16 caracteres
2. **Tamaño mínimo:** 200x200 píxeles
3. **Redundancia:** Nivel M (15% de recuperación de datos)

### Flujo Completo de una Receta

```
1. Doctor crea receta → POST /prescriptions
2. Sistema genera public_token automáticamente
3. Frontend muestra receta con QR del public_token
4. Paciente va a la farmacia → Escanea QR
5. Farmacia verifica → GET /verify/{public_token} (futuro)
6. Farmacia crea cotización → POST /quote-requests/{id}/offers
7. Paciente ve ofertas → Elige la mejor
```

### Validación de Medicamentos

Para autocompletar medicamentos en la receta:

```
GET /medications?q=para  → Busca "paracetamol", "paracetamol+codeína", etc.
```

El search es case-insensitive y busca en:
- `active_principle`
- `commercial_name`
