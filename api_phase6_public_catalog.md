# Documentación de API: Catálogo Público (Fase 6)

Este documento detalla los endpoints públicos del **Catálogo LUCA Health OS**, diseñados para que los usuarios puedan visualizar la oferta de servicios médicos disponibles sin necesidad de autenticación.

**Base URL:** `https://api.lucahealth.os/api/v1`

---

## Consideraciones Generales

1. **Sin autenticación**: Estos endpoints son públicos y no requieren tokens JWT.
2. **Solo datos públicos**: La información mostrado excluye datos sensibles (emails personales, contraseñas, etc.).
3. **Solo entidades verificadas**: Doctores y farmacias solo aparecen si tienen `is_verified = true`.
4. **Filtros opcionales**: Los parámetros de query son case-insensitive y combinables.

---

## 1. Catálogo de Doctores

### `GET /public/doctors`

Lista de doctores verificados con sus especialidades.

**Parámetros de Query:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `city_id` | integer | Filtrar por ID de ciudad |
| `specialty_id` | integer | Filtrar por ID de especialidad |

**Ejemplo de request:**
```http
GET /api/v1/public/doctors?city_id=1&specialty_id=2
```

**Respuesta exitosa (200):**
```json
{
  "data": [
    {
      "id": "d8f7c6b5-...",
      "full_name": "Dr. Juan Pérez",
      "specialties": [
        {"id": 1, "name": "Medicina General"},
        {"id": 2, "name": "Pediatría"}
      ],
      "city": {
        "id": 1,
        "name": "Caracas"
      },
      "logo_url": "https://storage.lucahealth.os/logos/dr-juan-perez.jpg",
      "is_verified": true
    }
  ]
}
```

**Campos de cada doctor:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | string (UUID) | Identificador único público |
| `full_name` | string | Nombre completo del doctor |
| `specialties` | array | Lista de especialidades |
| `city` | object/null | Ciudad donde opera |
| `logo_url` | string/null | URL del logo profesional |
| `is_verified` | boolean | Siempre `true` (solo verificados) |

---

## 2. Catálogo de Farmacias

### `GET /public/pharmacies`

Lista de farmacias verificadas con sus sucursales.

**Parámetros de Query:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `city_id` | integer | Filtrar por ID de ciudad |

**Ejemplo de request:**
```http
GET /api/v1/public/pharmacies?city_id=1
```

**Respuesta exitosa (200):**
```json
{
  "data": [
    {
      "id": "a1b2c3d4-...",
      "commercial_name": "Farmacia San Juan",
      "rif": "J-12345678-9",
      "address": "Av. Principal, Torre médica, PB",
      "phone": "+582121234567",
      "is_open": true,
      "is_verified": true,
      "logo_url": "https://storage.lucahealth.os/logos/farmacia-san-juan.png",
      "city": {
        "id": 1,
        "name": "Caracas"
      },
      "branches": [
        {
          "id": "e5f6g7h8-...",
          "name": "Sucursal Este",
          "address": "Cc. Galerías, Nivel 2",
          "phone": "+582142345678",
          "is_open": true,
          "latitude": 10.4806,
          "longitude": -66.9036,
          "google_maps_url": "https://maps.google.com/?q=..."
        }
      ]
    }
  ]
}
```

**Campos de la farmacia:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | string (UUID) | Identificador único público |
| `commercial_name` | string | Nombre comercial |
| `rif` | string | Registro de Identificación Fiscal |
| `address` | string | Dirección de la sede principal |
| `phone` | string | Teléfono de contacto |
| `is_open` | boolean | Si está operando actualmente |
| `is_verified` | boolean | Siempre `true` (solo verificados) |
| `logo_url` | string/null | URL del logo |
| `city` | object/null | Ciudad de la sede principal |
| `branches` | array | Lista de sucursales |

**Campos de cada sucursal:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | string (UUID) | Identificador único |
| `name` | string | Nombre de la sucursal |
| `address` | string | Dirección completa |
| `phone` | string | Teléfono de la sucursal |
| `is_open` | boolean | Si está operando actualmente |
| `latitude` | decimal | Coordenada de latitud |
| `longitude` | decimal | Coordenada de longitud |
| `google_maps_url` | string/null | Link directo a Google Maps |

---

## 3. Catálogo de Clínicas

### `GET /public/clinics`

Lista de clínicas con sus sucursales y doctores asociados.

**Parámetros de Query:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `city_id` | integer | Filtrar por ID de ciudad |

**Ejemplo de request:**
```http
GET /api/v1/public/clinics?city_id=1
```

**Respuesta exitosa (200):**
```json
{
  "data": [
    {
      "id": "c1d2e3f4-...",
      "name": "Clínica Central",
      "rif": "J-87654321-0",
      "logo_url": "https://storage.lucahealth.os/logos/clinica-central.png",
      "website": "https://www.clinicacentral.com",
      "branches": [
        {
          "id": "h5i6j7k8-...",
          "name": "Sede Principal",
          "address": "Av. Libertador, Torre Corporativa",
          "phone": "+582125555555",
          "is_main_branch": true,
          "latitude": 10.5000,
          "longitude": -66.9100,
          "google_maps_url": "https://maps.google.com/?q=...",
          "city": {
            "id": 1,
            "name": "Caracas"
          },
          "doctors": [
            {
              "id": "d8f7c6b5-...",
              "full_name": "Dr. Roberto Mendoza",
              "logo_url": "https://storage.lucahealth.os/logos/dr-mendoza.jpg",
              "department": "Cardiología",
              "office_number": "301"
            }
          ]
        }
      ]
    }
  ]
}
```

**Campos de la clínica:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | string (UUID) | Identificador único público |
| `name` | string | Nombre de la clínica |
| `rif` | string | Registro de Identificación Fiscal |
| `logo_url` | string/null | URL del logo institucional |
| `website` | string/null | Sitio web oficial |
| `branches` | array | Lista de sucursales |

**Campos de cada sucursal:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | string (UUID) | Identificador único |
| `name` | string | Nombre de la sucursal |
| `address` | string | Dirección completa |
| `phone` | string | Teléfono de contacto |
| `is_main_branch` | boolean | Si es la sede principal |
| `latitude` | decimal | Coordenada de latitud |
| `longitude` | decimal | Coordenada de longitud |
| `google_maps_url` | string/null | Link directo a Google Maps |
| `city` | object/null | Ciudad de la sucursal |
| `doctors` | array | Doctores que atienden en esta sucursal |

**Campos de cada doctor en la sucursal:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | string (UUID) | Identificador único público |
| `full_name` | string | Nombre completo |
| `logo_url` | string/null | URL del logo profesional |
| `department` | string/null | Departamento o área de especialización |
| `office_number` | string/null | Número de consultorio |

---

## 4. Catálogo de Disponibilidad de Doctor

### `GET /public/doctors/{doctorId}/availability`

Consulta la disponibilidad de un doctor verificado para una fecha específica. **No requiere autenticación.**

**Parámetros de URL:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `doctorId` | string (UUID) | Identificador único del doctor |

**Parámetros de Query:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `date` | string (YYYY-MM-DD) | **Requerido.** Fecha para consultar disponibilidad |

**Ejemplo de request:**
```http
GET /api/v1/public/doctors/d8f7c6b5-a1b2-c3d4-e5f6-1234567890ab/availability?date=2026-06-25
```

**Respuesta exitosa (200) - Disponible:**
```json
{
  "data": {
    "doctor_id": "d8f7c6b5-a1b2-c3d4-e5f6-1234567890ab",
    "date": "2026-06-25",
    "weekday": "THURSDAY",
    "is_available": true,
    "schedule": {
      "start_time": "08:00",
      "end_time": "17:00",
      "appointment_duration": 30,
      "max_per_slot": 1
    },
    "slots": [
      {"time": "08:00", "available": true},
      {"time": "08:30", "available": true},
      {"time": "09:00", "available": false}
    ],
    "exception": null
  }
}
```

**Respuesta cuando hay excepción (día libre):**
```json
{
  "data": {
    "doctor_id": "d8f7c6b5-a1b2-c3d4-e5f6-1234567890ab",
    "date": "2026-06-26",
    "weekday": "FRIDAY",
    "is_available": false,
    "schedule": null,
    "slots": [],
    "exception": {
      "type": "VACATION",
      "reason": "Vacaciones de verano"
    }
  }
}
```

**Respuesta cuando no hay horario definido:**
```json
{
  "data": {
    "doctor_id": "d8f7c6b5-a1b2-c3d4-e5f6-1234567890ab",
    "date": "2026-06-27",
    "weekday": "SATURDAY",
    "is_available": false,
    "schedule": null,
    "slots": [],
    "exception": null
  }
}
```

**Códigos de error:**

| Código | Significado |
|--------|-------------|
| `200` | Solicitud exitosa |
| `404` | Doctor no encontrado o no verificado |
| `422` | Parámetros inválidos (date requerido, formato incorrecto) |
| `500` | Error interno del servidor |

---

## Notas de Implementación

1. **Geolocalización**: Los campos `latitude`, `longitude` y `google_maps_url` permiten integraciones con mapas (Google Maps, Mapbox, Apple Maps).
2. **Filtros combinables**: Los parámetros de query pueden usarse juntos, por ejemplo: `?city_id=1&specialty_id=2`.
3. **Doctores sin sucursal**: Los doctores pueden aparecer en el catálogo sin estar asociados a una clínica específica.
4. **Sucursales sin doctores**: Las sucursales pueden existir sin doctores asociados temporalmente.
5. **Disponibilidad**: El endpoint de disponibilidad solo devuelve doctores con `is_verified = true` y `is_active = true`.
