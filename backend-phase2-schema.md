# LUCA Health OS - Plan Arquitectónico: Motor Clínico (Fase 2)

> **Documento de Diseño para el Agente Backend**
> Este documento detalla la estructura y directrices arquitectónicas para la Fase 2, enfocada en la Agenda (Citas), las Consultas Clínicas (SOAP) y los Antecedentes Médicos. Todo está adaptado a la arquitectura de Sedes (`ClinicBranch`) implementada en la Fase 1.

---

## 1. El Script SQL (Plan de Estructura)

Este es el blueprint SQL que el agente de backend debe implementar. Define los tipos de datos exactos y las relaciones (Foreign Keys) obligatorias para garantizar la integridad relacional.

```sql
-- ==============================================================================
-- LUCA Health OS - Fase 2: Motor Clínico y Agenda
-- ==============================================================================

-- 8. AGENDA Y CONSULTAS
CREATE TABLE "Appointment" (
    "id" UUID PRIMARY KEY,
    "patientId" UUID NOT NULL REFERENCES "Patient"("id"),
    "doctorId" UUID NOT NULL REFERENCES "User"("id"),
    "clinicBranchId" UUID NOT NULL REFERENCES "ClinicBranch"("id"), -- Enlazado a la Sede
    "date" DATE NOT NULL,
    "time" VARCHAR NOT NULL, -- e.g. "09:30"
    "type" VARCHAR NOT NULL, -- e.g. "Control general", "Primera vez"
    "status" VARCHAR NOT NULL DEFAULT 'pending', -- pending, in-progress, completed, cancelled
    "notes" TEXT,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 8.1 FORMULARIOS DINÁMICOS
CREATE TABLE "FormTemplate" (
    "id" UUID PRIMARY KEY,
    "userId" UUID REFERENCES "User"("id"), -- NULL = Plantilla Global del sistema
    "title" VARCHAR NOT NULL, -- e.g. "Control Pediátrico Mensual"
    "specialty" VARCHAR, -- e.g. "Cardiología"
    "schemaJson" JSONB NOT NULL, -- Configuración del formulario
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 8.2 EL ACTO MÉDICO (SOAP)
CREATE TABLE "Consultation" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"),
    "patientId" UUID NOT NULL REFERENCES "Patient"("id"),
    "clinicBranchId" UUID NOT NULL REFERENCES "ClinicBranch"("id"), -- Enlazado a la Sede
    "appointmentId" UUID REFERENCES "Appointment"("id"), -- Vincula la consulta con la cita original
    "formTemplateId" UUID REFERENCES "FormTemplate"("id"),
    "date" TIMESTAMP NOT NULL,
    "status" VARCHAR NOT NULL DEFAULT 'pending',
    "reason" VARCHAR,          -- S: Subjetivo
    "physicalExam" TEXT,       -- O: Objetivo
    "diagnosis" VARCHAR,       -- A: Análisis/Diagnóstico
    "treatmentPlan" TEXT,      -- P: Plan
    "dynamicData" JSONB,       -- Respuestas del FormTemplate
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 8.3 ESTUDIOS ASOCIADOS A LA CONSULTA
CREATE TABLE "VitalSign" (
    "id" UUID PRIMARY KEY,
    "patientId" UUID NOT NULL REFERENCES "Patient"("id"),
    "consultationId" UUID NOT NULL UNIQUE REFERENCES "Consultation"("id"), -- 1:1
    "weight" FLOAT,
    "height" FLOAT,
    "systolicBP" INT,
    "diastolicBP" INT,
    "heartRate" INT,
    "respiratoryRate" INT, -- Frecuencia respiratoria
    "temperature" FLOAT,
    "oxygenSat" INT,
    "date" TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE "LabRequest" (
    "id" UUID PRIMARY KEY,
    "consultationId" UUID NOT NULL UNIQUE REFERENCES "Consultation"("id"), -- 1:1
    "examsList" JSONB NOT NULL, -- Array de strings con los exámenes pedidos
    "instructions" TEXT,
    "isCompleted" BOOLEAN NOT NULL DEFAULT false,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 8.4 SEGUIMIENTO CLÍNICO (Follow-ups)
CREATE TABLE "FollowUp" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"), -- Doctor que hace el seguimiento
    "patientId" UUID NOT NULL REFERENCES "Patient"("id"),
    "consultationId" UUID REFERENCES "Consultation"("id"), -- Vincula el seguimiento al acto médico original
    "scheduledDate" TIMESTAMP NOT NULL,
    "status" VARCHAR NOT NULL DEFAULT 'PENDING', -- PENDING, SENT, RESPONDED
    "response" TEXT,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- 9. ANTECEDENTES Y EXPEDIENTE PROFUNDO
-- Todas vinculadas directamente al Paciente (Patient)
-- ==============================================================================

CREATE TABLE "MedicalBackground" (
    "id" UUID PRIMARY KEY,
    "patientId" UUID NOT NULL UNIQUE REFERENCES "Patient"("id"), -- 1:1
    "hasDiabetes" BOOLEAN NOT NULL DEFAULT false,
    "hasHypertension" BOOLEAN NOT NULL DEFAULT false,
    "hasAsthma" BOOLEAN NOT NULL DEFAULT false,
    "otherConditions" TEXT,
    "pastHospitalizations" TEXT
);

CREATE TABLE "SurgicalHistory" (
    "id" UUID PRIMARY KEY,
    "patientId" UUID NOT NULL REFERENCES "Patient"("id"), -- 1:N
    "procedure" VARCHAR NOT NULL,
    "date" TIMESTAMP,
    "hospital" VARCHAR,
    "notes" TEXT
);

CREATE TABLE "FamilyHistory" (
    "id" UUID PRIMARY KEY,
    "patientId" UUID NOT NULL REFERENCES "Patient"("id"), -- 1:N
    "condition" VARCHAR NOT NULL,
    "relationship" VARCHAR NOT NULL,
    "note" TEXT
);

CREATE TABLE "Lifestyle" (
    "id" UUID PRIMARY KEY,
    "patientId" UUID NOT NULL UNIQUE REFERENCES "Patient"("id"), -- 1:1
    "smokingStatus" VARCHAR,
    "alcoholConsumption" VARCHAR,
    "activityLevel" VARCHAR,
    "dietType" VARCHAR
);

CREATE TABLE "ObstetricHistory" (
    "id" UUID PRIMARY KEY,
    "patientId" UUID NOT NULL UNIQUE REFERENCES "Patient"("id"), -- 1:1
    "lastPeriodDate" TIMESTAMP,
    "pregnancies" INT,
    "births" INT,
    "cesareans" INT,
    "abortions" INT,
    "contraceptiveMethod" VARCHAR
);

CREATE TABLE "Vaccination" (
    "id" UUID PRIMARY KEY,
    "patientId" UUID NOT NULL REFERENCES "Patient"("id"), -- 1:N
    "vaccine" VARCHAR NOT NULL,
    "doseNumber" INT NOT NULL,
    "date" TIMESTAMP NOT NULL
);
```

---

## 2. Directrices de Arquitectura para el Agente Backend

### Modificación respecto a la Fase 1
Es obligatorio que la agenda (`Appointment`) y las notas médicas (`Consultation`) estén amarradas al **`clinicBranchId`** y no al `clinicId`. Esto garantiza que los historiales reflejen exactamente la Sede física donde ocurrió el encuentro médico, solucionando el problema de médicos que rotan entre múltiples sucursales u hospitales.

### Arquitectura de Formularios y JSONB
1. **El Problema:** Cada especialidad (Cardiología vs Ginecología) necesita preguntas completamente distintas en su historia clínica. Crear 300 columnas en la base de datos es un anti-patrón (*Anti-pattern: Sparse Tables*).
2. **La Solución (`JSONB`):** Usar un enfoque híbrido. La tabla `FormTemplate` almacena la estructura del formulario (`schemaJson`). La tabla `Consultation` captura las respuestas en `dynamicData`.
3. **Restricción:** Exigir que el driver de base de datos o el ORM (ej: Prisma, Drizzle) utilice el tipo nativo `JSONB` de PostgreSQL, no un simple string, para poder ejecutar búsquedas indexadas (ej: buscar pacientes con un valor específico dentro del JSON).

### Integridad Referencial 1 a 1
El agente backend debe asegurar un constraint `UNIQUE` en la llave foránea `consultationId` tanto en `VitalSign` como en `LabRequest`. Una consulta no puede tener múltiples registros independientes de signos vitales (se sobrescriben o actualizan) para mantener la limpieza del dashboard clínico. Lo mismo aplica para antecedentes de un solo registro como `MedicalBackground`, `Lifestyle` y `ObstetricHistory` (relación 1:1 con `Patient`).

### El Seguimiento Clínico (`FollowUp`)
El sistema de recordatorios o seguimientos por WhatsApp (`FollowUp`) está diseñado para enlazarse obligatoriamente al acto médico (`consultationId`). Esto asegura que cuando un paciente responda al seguimiento días después, el doctor tenga el contexto inmediato de la consulta y el récipe que generó dicho seguimiento, evitando ineficiencias de búsqueda manual.
