# LUCA Health OS - Sistema de Agendamiento de Citas (Fase 6)

> **Documento de Diseño para el Agente Backend**
> Este documento detalla la arquitectura del sistema de agendamiento, incluyendo horarios de doctores, excepciones, validación de conflictos y gestión de disponibilidad.

---

## 1. El Script SQL

```sql
-- ==============================================================================
-- LUCA Health OS - Fase 6: Sistema de Agendamiento
-- ==============================================================================

-- 19. HORARIOS DE DOCTORES (Recurrentes por día de semana)
CREATE TYPE "Weekday" AS ENUM ('MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY');

CREATE TABLE "DoctorSchedule" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"),
    "weekday" "Weekday" NOT NULL,
    "startTime" TIME NOT NULL,           -- Ej: "08:00"
    "endTime" TIME NOT NULL,             -- Ej: "17:00"
    "appointmentDuration" INT NOT NULL DEFAULT 30,  -- Duración por paciente en minutos
    "maxPerSlot" INT NOT NULL DEFAULT 1,  -- Máx pacientes en mismo horario
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE ("userId", "weekday")
);

-- 20. EXCEPCIONES DE HORARIO (Vacaciones, días libres, horarios especiales)
CREATE TYPE "ExceptionType" AS ENUM ('VACATION', 'DAY_OFF', 'CUSTOM_HOURS');

CREATE TABLE "ScheduleException" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"),
    "exceptionDate" DATE NOT NULL,
    "exceptionType" "ExceptionType" NOT NULL,
    "customStartTime" TIME,              -- Solo si exceptionType = CUSTOM_HOURS
    "customEndTime" TIME,                -- Solo si exceptionType = CUSTOM_HOURS
    "reason" VARCHAR,                    -- Ej: "Día festivo", "Conferencia"
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE ("userId", "exceptionDate")
);

-- 21. HORARIOS DE CLÍNICAS (Por sucursal)
CREATE TABLE "ClinicSchedule" (
    "id" UUID PRIMARY KEY,
    "clinicBranchId" UUID NOT NULL REFERENCES "ClinicBranch"("id"),
    "weekday" "Weekday" NOT NULL,
    "startTime" TIME NOT NULL,
    "endTime" TIME NOT NULL,
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE ("clinicBranchId", "weekday")
);

-- 22. MODIFICACIÓN: Appointment con validación
-- Añadir constraint único para prevenir doble reserva
ALTER TABLE "Appointment" ADD COLUMN "slotTime" TIME;  -- TIME slot normalizado

ALTER TABLE "Appointment" ADD CONSTRAINT "unique_doctor_slot"
    UNIQUE ("userId", "date", "slotTime");
```

---

## 2. Diccionario de Tablas

### 19. DoctorSchedule - Horarios Recurrentes del Doctor

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | UUID | Identificador único |
| `userId` | UUID | FK → User (el doctor) |
| `weekday` | enum | Día de la semana (MONDAY-SUNDAY) |
| `startTime` | TIME | Hora de inicio (ej: "08:00") |
| `endTime` | TIME | Hora de fin (ej: "17:00") |
| `appointmentDuration` | INT | Minutos por paciente (default: 30) |
| `maxPerSlot` | INT | Máx pacientes en mismo horario (default: 1) |
| `isActive` | BOOLEAN | Si el horario está activo |
| `createdAt` | TIMESTAMP | Fecha de creación |
| `updatedAt` | TIMESTAMP | Fecha de actualización |

**Ejemplo de uso:**
```
Doctor: Dr. Juan Pérez
- MONDAY: 08:00-12:00 (30min/slot, máx 1)
- MONDAY: 14:00-18:00 (30min/slot, máx 1)
- TUESDAY: 08:00-12:00 (30min/slot, máx 1)
- WEDNESDAY: 08:00-12:00 (30min/slot, máx 1)
```

### 20. ScheduleException - Excepciones y Vacaciones

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | UUID | Identificador único |
| `userId` | UUID | FK → User (el doctor) |
| `exceptionDate` | DATE | Fecha de la excepción |
| `exceptionType` | enum | VACATION, DAY_OFF, CUSTOM_HOURS |
| `customStartTime` | TIME | Hora inicio (solo si CUSTOM_HOURS) |
| `customEndTime` | TIME | Hora fin (solo si CUSTOM_HOURS) |
| `reason` | VARCHAR | Razón (opcional) |
| `createdAt` | TIMESTAMP | Fecha de creación |
| `updatedAt` | TIMESTAMP | Fecha de actualización |

**Tipos de excepción:**
- `VACATION`: Día completo libre (ej: vacaciones)
- `DAY_OFF`: Día libre (ej: día de descanso semanal)
- `CUSTOM_HOURS`: Horario especial ese día (ej: sale más temprano)

### 21. ClinicSchedule - Horarios de Clínica por Sucursal

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | UUID | Identificador único |
| `clinicBranchId` | UUID | FK → ClinicBranch |
| `weekday` | enum | Día de la semana |
| `startTime` | TIME | Hora de inicio |
| `endTime` | TIME | Hora de fin |
| `isActive` | BOOLEAN | Si está activo |

**Nota:** Este horario define la disponibilidad general de la clínica. Los doctores pueden tener horarios más restrictivos pero NO más amplios que el horario de la clínica.

---

## 3. Lógica de Slots Disponibles

### Cálculo de Slots

```
Slots por día = (endTime - startTime) / appointmentDuration
Ejemplo: 08:00-17:00 con duración de 30min = 18 slots
```

### Slots Reservados vs Disponibles

```
Slots disponibles = Total slots - Citas ya reservadas en ese horario
```

### Validación de Conflictos (en Appointment::store)

```php
// Pseudocódigo de la validación
function validateAppointment($doctorId, $date, $time) {
    // 1. Verificar si hay excepción para ese día
    $exception = ScheduleException::where('userId', $doctorId)
        ->where('exceptionDate', $date)
        ->first();
    
    if ($exception && $exception->exceptionType === 'VACATION') {
        throw new Exception("Doctor no atiende este día");
    }
    
    if ($exception && $exception->exceptionType === 'DAY_OFF') {
        throw new Exception("Doctor no atiende este día");
    }
    
    // 2. Obtener horario del día
    $weekday = strtoupper(date('l', strtotime($date)));
    $schedule = DoctorSchedule::where('userId', $doctorId)
        ->where('weekday', $weekday)
        ->where('isActive', true)
        ->first();
    
    if (!$schedule) {
        throw new Exception("Doctor no tiene horario definido para este día");
    }
    
    // 3. Verificar que la hora esté dentro del rango
    $slotTime = strtotime($time);
    $startTime = strtotime($schedule->startTime);
    $endTime = strtotime($schedule->endTime);
    
    if ($slotTime < $startTime || $slotTime >= $endTime) {
        throw new Exception("Horario fuera del rango de atención");
    }
    
    // 4. Contar citas existentes en ese slot
    $existingAppointments = Appointment::where('userId', $doctorId)
        ->where('date', $date)
        ->where('slotTime', $time)
        ->whereNotIn('status', ['cancelled'])
        ->count();
    
    if ($existingAppointments >= $schedule->maxPerSlot) {
        throw new Exception("Este horario ya está lleno. Máximo {$schedule->maxPerSlot} paciente(s)");
    }
    
    return true;
}
```

---

## 4. Endpoints API

### Doctores - Gestión de Horarios

#### `GET /api/v1/schedules/my`
Obtener los horarios del doctor autenticado.

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid",
      "weekday": "MONDAY",
      "startTime": "08:00",
      "endTime": "12:00",
      "appointmentDuration": 30,
      "maxPerSlot": 1,
      "isActive": true
    }
  ]
}
```

#### `POST /api/v1/schedules/my`
Crear un horario para un día específico.

**Body:**
```json
{
  "weekday": "MONDAY",
  "startTime": "08:00",
  "endTime": "12:00",
  "appointmentDuration": 30,
  "maxPerSlot": 1
}
```

#### `PUT /api/v1/schedules/my/{id}`
Actualizar un horario existente.

#### `DELETE /api/v1/schedules/my/{id}`
Eliminar un horario.

---

### Doctores - Excepciones

#### `GET /api/v1/schedule-exceptions/my`
Listar excepciones del doctor.

**Query params:** `from_date`, `to_date`

#### `POST /api/v1/schedule-exceptions/my`
Crear una excepción.

**Body (VACATION):**
```json
{
  "exceptionDate": "2026-07-15",
  "exceptionType": "VACATION",
  "reason": "Vacaciones de verano"
}
```

**Body (CUSTOM_HOURS):**
```json
{
  "exceptionDate": "2026-07-20",
  "exceptionType": "CUSTOM_HOURS",
  "customStartTime": "08:00",
  "customEndTime": "12:00",
  "reason": "Solo mañana"
}
```

#### `DELETE /api/v1/schedule-exceptions/{id}`
Eliminar una excepción.

---

### Clínicas - Gestión de Horarios

#### `GET /api/v1/clinic-schedules/{clinicBranchId}`
Obtener horarios de una sucursal.

#### `POST /api/v1/clinic-schedules/{clinicBranchId}`
Crear/actualizar horarios de sucursal.

**Body:**
```json
{
  "schedules": [
    {"weekday": "MONDAY", "startTime": "07:00", "endTime": "19:00"},
    {"weekday": "TUESDAY", "startTime": "07:00", "endTime": "19:00"}
  ]
}
```

#### `DELETE /api/v1/clinic-schedules/{clinicBranchId}/{weekday}`
Eliminar horario de un día.

---

### Disponibilidad (Público - Sin Auth)

#### `GET /api/v1/public/doctors/{doctorId}/availability`
Consultar disponibilidad de un doctor para una fecha.

**Query params:**
| Param | Descripción |
|-------|-------------|
| `date` | Fecha (YYYY-MM-DD) |
| `city_id` | Filtrar por ciudad (opcional) |

**Response (200):**
```json
{
  "data": {
    "doctor_id": "uuid",
    "date": "2026-06-25",
    "weekday": "WEDNESDAY",
    "is_available": true,
    "schedule": {
      "start_time": "08:00",
      "end_time": "17:00",
      "appointment_duration": 30,
      "max_per_slot": 1
    },
    "slots": [
      {"time": "08:00", "available": true},
      {"time": "08:30", "available": false},
      {"time": "09:00", "available": true}
    ],
    "exception": null
  }
}
```

**Respuesta cuando hay excepción:**
```json
{
  "data": {
    "doctor_id": "uuid",
    "date": "2026-06-26",
    "weekday": "THURSDAY",
    "is_available": false,
    "exception": {
      "type": "VACATION",
      "reason": "Vacaciones de verano"
    }
  }
}
```

---

## 5. Modificación a AppointmentController

### Validación en Store

```php
public function store(StoreAppointmentRequest $request): JsonResponse
{
    $doctorId = $request->validated('user_id');
    $date = $request->validated('date');
    $time = $request->validated('time');

    // 1. Validar disponibilidad usando el service
    $availabilityService = app(AvailabilityService::class);
    $availabilityService->validateAppointment($doctorId, $date, $time);

    // 2. Crear la cita
    $appointment = Appointment::create([
        ...$request->validated(),
        'slotTime' => $time  // Normalizar la hora
    ]);

    return response()->json([
        'data' => $appointment->load(['patient', 'doctor', 'clinicBranch'])
    ], 201);
}
```

---

## 6. Directrices Arquitectónicas

### Orden de Evaluación

1. **Excepciones primero**: Si hay una excepción para el día, no importa el horario, no se puede agendar
2. **Horario base**: Verificar que el día tenga horario definido
3. **Rango de hora**: Verificar que la hora solicitada esté dentro del rango
4. **Capacidad del slot**: Verificar que no esté lleno (`maxPerSlot`)

### Slots Múltiples (maxPerSlot > 1)

Para doctores que atienden múltiples pacientes en el mismo horario (ej: check-ups rápidos):
- Un slot de 30 min con `maxPerSlot = 3` permite 3 pacientes
- La hora se guarda normalizada, el sistema solo limita por cantidad

### Clínica como Filtro Adicional

El horario del doctor puede ser más restrictivo que el de la clínica, pero nunca más amplio:
```
Horario clínica: 07:00-20:00
Horario doctor:  08:00-17:00 → VÁLIDO
Horario doctor:  06:00-12:00 → INVÁLIDO (fuera del horario de la clínica)
```

### Timezone

- Todos los horarios se guardan en UTC
- El frontend debe enviar/recibir en la zona horaria del usuario
- `appointmentDuration` se maneja en minutos, sin conversión

---

## 7. Estados de Cita Actualizados

```sql
ALTER TABLE "Appointment" ADD COLUMN "status" VARCHAR NOT NULL DEFAULT 'PENDING';
-- Estados: PENDING, CONFIRMED, IN_PROGRESS, COMPLETED, CANCELLED, NO_SHOW
```

---

## 8. Migración sugerida (Orden)

1. `create_doctor_schedules_table`
2. `create_schedule_exceptions_table`
3. `create_clinic_schedules_table`
4. `add_slot_time_to_appointments` (nullable, luego populate y not null)
5. `modify_appointment_status_enum`

---

## 9. Integración con Sync Offline-First

### 9.1 Clasificación de Entidades para Sync

| Entidad | Tipo Sync | Dirección | Dependencias |
|---------|----------|-----------|---------------|
| `doctor_schedules` | PUSH | Doctor → Server | `user_id` (doctor) |
| `schedule_exceptions` | PUSH | Doctor → Server | `user_id` (doctor) |
| `clinic_schedules` | PULL | Server → Doctor | `clinic_branch_id` |
| `appointments` | PUSH | Doctor → Server | `patient_uuid`, `user_id`, `clinic_branch_id` |

### 9.2 Cambios en SyncService

```php
// PUSH_ENTITIES_ORDERED - AGREGAR después de medications:
'doctor_schedules',
'schedule_exceptions',

// ENTITY_CONFIG - AGREGAR:
// (user_id se setea desde auth, no necesita FK uuid)
'doctor_schedules'       => [DoctorSchedule::class,    ['weekday', 'start_time', 'end_time', 'appointment_duration', 'max_per_slot', 'is_active']],
'schedule_exceptions'    => [ScheduleException::class, ['exception_date', 'exception_type', 'custom_start_time', 'custom_end_time', 'reason']],

// PULL_ONLY_ENTITIES - AGREGAR:
'clinic_schedules'  // Catálogo global, similar a clinic_branches
```

### 9.3 Conflictos Offline: Estrategia de Resolución

**Problema:** Si el paciente agenda offline y al sincronizar el slot ya fue tomado por otro usuario, el modelo LWW (Last Write Wins) actual no es suficiente.

**Estrategia de Resolución:**

```
1. El cliente envía la cita con:
   - doctor_uuid
   - date
   - time (slot)
   - client_timestamp (updated_at)

2. En el servidor, al hacer upsert:
   a. Verificar disponibilidad con AvailabilityService::validateAppointment()
   b. Si el slot está ocupado:
      - Buscar si la cita existente tiene timestamp MENOR al del cliente
        - SI: La del cliente es más nueva, reemplazar (override)
        - NO: Rechazar, devolver error específico
      - Si el slot está lleno (maxPerSlot alcanzado):
        - Rechazar con error "SLOT_FULL"
```

**Respuesta de Error para Sync:**

```json
{
  "push_results": {
    "appointments": {
      "success": [],
      "errors": [
        {
          "uuid": "client-uuid",
          "field": "slot",
          "code": "SLOT_FULL",
          "message": "Este horario ya no está disponible. Por favor selecciona otro."
        }
      ]
    }
  }
}
```

### 9.4 Cálculo de Disponibilidad en Offline

**El cliente NO puede calcular disponibilidad offline** porque depende de:
- Horarios del doctor (doctor_schedules)
- Excepciones (schedule_exceptions)
- Citas existentes de OTROS pacientes (appointments)

**Solución:** El cliente debe hacer `pull` de catálogos antes de mostrar disponibilidad.

```
Flujo offline:
1. USER online → Pull doctor_schedules, schedule_exceptions, appointments del doctor
2. USER offline → Consulta local (puede mostrar slots basados en pull anterior)
3. USER crea cita offline → Push appointment
4. Sync → Si SLOT_FULL, mostrar error y pedir re-selección
```

### 9.5 Estructura de Pull para Scheduling

```json
{
  "pull": {
    "doctor_schedules": [
      {
        "uuid": "...",
        "user_uuid": "...",
        "weekday": "MONDAY",
        "start_time": "08:00",
        "end_time": "12:00",
        "appointment_duration": 30,
        "max_per_slot": 1,
        "is_active": true,
        "updated_at": "2026-06-24T10:00:00Z"
      }
    ],
    "schedule_exceptions": [
      {
        "uuid": "...",
        "user_uuid": "...",
        "exception_date": "2026-07-15",
        "exception_type": "VACATION",
        "reason": "Vacaciones",
        "updated_at": "2026-06-24T10:00:00Z"
      }
    ],
    "clinic_schedules": [
      {
        "uuid": "...",
        "clinic_branch_id": 123,
        "weekday": "MONDAY",
        "start_time": "07:00",
        "end_time": "20:00",
        "is_active": true,
        "updated_at": "2026-06-24T10:00:00Z"
      }
    ]
  }
}
```

### 9.6 Validación de Horario en Sync (Appointment Push)

```php
// En SyncService, al hacer upsert de appointments:
private function upsertAppointmentWithSchedulingValidation(array $item, array $fkMaps, User $user): array
{
    $doctorId = $fkMaps['user_id'][$item['user_uuid']];
    $date = $item['date'];
    $time = $item['time'];

    // 1. Validar disponibilidad
    try {
        $availabilityService = app(AvailabilityService::class);
        $availabilityService->validateAppointment($doctorId, $date, $time);
    } catch (SlotNotAvailableException $e) {
        return [
            'uuid' => $item['uuid'],
            'field' => 'slot',
            'code' => $e->getCode(), // SLOT_FULL, DOCTOR_ON_VACATION, etc.
            'message' => $e->getMessage(),
        ];
    }

    // 2. Proceder con upsert normal...
}
```

### 9.7 Jerarquía de Validación de Conflictos

```
Sync Appointment Conflict Resolution:

1. EXCEPTION CHECK (schedule_exceptions)
   └─ Si existe exception_type=VACATION para date
      └─ Error: DOCTOR_ON_VACATION

2. SCHEDULE CHECK (doctor_schedules)
   └─ Si NO existe schedule para weekday
      └─ Error: NO_SCHEDULE_FOR_DAY

3. TIME RANGE CHECK
   └─ Si time < start_time OR time >= end_time
      └─ Error: OUTSIDE_SCHEDULE_HOURS

4. CAPACITY CHECK
   └─ Si count(appointments with same date,time,doctor) >= max_per_slot
      └─ Error: SLOT_FULL
      └─ Extra: Verificar timestamps para override
```

### 9.8 Excepciones Manejadas por Sync

| Escenario | Manejo en Sync |
|-----------|----------------|
| Doctor modifica horario offline, paciente ya tenía cita en slot eliminado | Validación rechaza, paciente recibe error en próximo sync |
| Doctor agrega vacaciones después de que paciente reservó offline | Validación rechaza en sync, paciente debe re-agendar |
| Dos pacientes reservan mismo slot offline simultáneamente | Primer sync en llegar al servidor gana, segundo recibe SLOT_FULL |
| Cliente con cita antigua hace sync (offline por días) | LWW normal por updated_at, pero validación de slot siempre aplica |

---

## 10. Consideraciones de Seguridad

### 10.1 Permisos por Rol

| Acción | DOCTOR | PROVIDER | ADMIN |
|--------|--------|----------|-------|
| CRUD sus doctor_schedules | ✅ | ❌ | ❌ |
| CRUD sus schedule_exceptions | ✅ | ❌ | ❌ |
| Ver doctor_schedules de otros | ❌ | ❌ | ✅ |
| CRUD clinic_schedules (su sucursal) | ✅ (si es miembro) | ❌ | ✅ |
| Ver public availability | ✅ | ✅ | ✅ |

### 10.2 Campos Expuestos en Disponibilidad Pública

```
GET /public/doctors/{id}/availability NO revela:
- max_per_slot (no exponer capacidad interna)
- schedule_id
- exceptions_id
```

---

## 11. Índices Recomendados

```sql
-- Para validación rápida de disponibilidad
CREATE INDEX idx_doctor_schedule_user_weekday ON doctor_schedules(user_id, weekday) WHERE is_active = true;
CREATE INDEX idx_schedule_exception_user_date ON schedule_exceptions(user_id, exception_date);
CREATE INDEX idx_appointment_doctor_date_time ON appointments(user_id, date, slot_time) WHERE status != 'CANCELLED';
CREATE INDEX idx_clinic_schedule_branch_weekday ON clinic_schedules(clinic_branch_id, weekday) WHERE is_active = true;
```
