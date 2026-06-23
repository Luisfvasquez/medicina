# LUCA Health OS - Plan Arquitectónico: Prescripciones y Marketplace (Fase 3)

> **Documento de Diseño para el Agente Backend**
> Este documento detalla la arquitectura de la Fase 3, que es el núcleo comercial del sistema B2B2C de LUCA. Conecta el acto médico (recetas) con la monetización y el servicio al paciente (cotizaciones en farmacias/laboratorios). 

---

## 1. El Script SQL (Plan de Estructura)

Este es el blueprint SQL que el agente de backend debe implementar.

```sql
-- ==============================================================================
-- LUCA Health OS - Fase 3: Vademécum, Recetas y Marketplace
-- ==============================================================================

-- 10. VADEMÉCUM Y RECETAS (Prescriptions)
CREATE TABLE "Medication" (
    "id" UUID PRIMARY KEY,
    "userId" UUID REFERENCES "User"("id"), -- NULL = Vademécum Global de LUCA. NOT NULL = Medicamento privado del doctor.
    "activePrinciple" VARCHAR NOT NULL,
    "concentration" VARCHAR NOT NULL,
    "presentation" VARCHAR NOT NULL, -- CAPSULA, TABLETA, JARABE, GOTAS, AMPOLLA, CREMA
    "administrationRoute" VARCHAR NOT NULL, -- ORAL, INTRAVENOSA, TOPICA, etc.
    "commercialName" VARCHAR,
    "requiresPrescription" BOOLEAN NOT NULL DEFAULT true,
    "contraindications" TEXT,
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE "Prescription" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"),
    "patientId" UUID NOT NULL REFERENCES "Patient"("id"),
    "consultationId" UUID UNIQUE REFERENCES "Consultation"("id"), -- Vinculado a la consulta si aplica (1:1)
    "clinicBranchId" UUID REFERENCES "ClinicBranch"("id"), -- Sede física donde se recetó (Actualización Fase 1)
    "date" TIMESTAMP NOT NULL,
    "expirationDate" TIMESTAMP NOT NULL,
    "notes" TEXT,
    "publicToken" VARCHAR NOT NULL UNIQUE, -- Token seguro para el QR de la farmacia
    "status" VARCHAR NOT NULL DEFAULT 'ACTIVE' -- ACTIVE, CANCELLED, EXPIRED
);

CREATE TABLE "PrescriptionItem" (
    "id" UUID PRIMARY KEY,
    "prescriptionId" UUID NOT NULL REFERENCES "Prescription"("id"),
    "medicationId" UUID NOT NULL REFERENCES "Medication"("id"),
    "dose" VARCHAR, -- Ej. "1 cápsula"
    "frequency" VARCHAR, -- Ej. "Cada 8 horas"
    "duration" VARCHAR, -- Ej. "7 días"
    "quantity" INT NOT NULL DEFAULT 1, -- Cantidad de cajas a comprar
    "notes" TEXT
);

-- 10.1 PLANTILLAS DE RECETAS (Para agilizar el trabajo del médico)
CREATE TABLE "PrescriptionTemplate" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"),
    "title" VARCHAR NOT NULL -- Ej. "Post-operatorio Apendicitis"
);

CREATE TABLE "TemplateItem" (
    "id" UUID PRIMARY KEY,
    "templateId" UUID NOT NULL REFERENCES "PrescriptionTemplate"("id"),
    "medicationId" UUID NOT NULL REFERENCES "Medication"("id"),
    "dose" VARCHAR,
    "frequency" VARCHAR,
    "duration" VARCHAR
);

-- 11. DOCUMENTOS MÉDICOS LEGALES
CREATE TABLE "MedicalDocument" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"),
    "patientId" UUID NOT NULL REFERENCES "Patient"("id"),
    "clinicBranchId" UUID REFERENCES "ClinicBranch"("id"), -- Sede donde se emitió
    "type" VARCHAR NOT NULL, -- CERTIFICATE (Reposos), REFERRAL (Referencias), REPORT (Informes)
    "content" TEXT NOT NULL,
    "publicToken" VARCHAR NOT NULL UNIQUE, -- Código QR de validación patronal
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- 12. MARKETPLACE B2B2C (Farmacias y Laboratorios)
-- ==============================================================================

CREATE TABLE "ProviderProfile" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL UNIQUE REFERENCES "User"("id"),
    "type" VARCHAR NOT NULL, -- PHARMACY, LABORATORY
    "commercialName" VARCHAR NOT NULL,
    "rif" VARCHAR NOT NULL,
    "address" VARCHAR NOT NULL,
    "cityId" UUID NOT NULL REFERENCES "City"("id"), -- Normalizado (Actualización Fase 1)
    "phone" VARCHAR NOT NULL,
    "isOpen" BOOLEAN NOT NULL DEFAULT false, -- Switch para aceptar cotizaciones
    "isVerified" BOOLEAN NOT NULL DEFAULT false -- KYC de LUCA
);

CREATE TABLE "QuoteRequest" (
    "id" UUID PRIMARY KEY,
    "prescriptionId" UUID NOT NULL REFERENCES "Prescription"("id"),
    "patientId" UUID NOT NULL REFERENCES "Patient"("id"),
    "cityId" UUID NOT NULL REFERENCES "City"("id"), -- El paciente busca farmacias en esta ciudad específica
    "status" VARCHAR NOT NULL DEFAULT 'OPEN', -- OPEN (Recibiendo ofertas), CLOSED (Completada o vencida)
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE "QuoteOffer" (
    "id" UUID PRIMARY KEY,
    "quoteRequestId" UUID NOT NULL REFERENCES "QuoteRequest"("id"),
    "providerId" UUID NOT NULL REFERENCES "ProviderProfile"("id"),
    "price" DECIMAL(10, 2) NOT NULL, -- Usar DECIMAL para dinero, NUNCA usar FLOAT
    "currency" VARCHAR NOT NULL DEFAULT 'USD',
    "availability" VARCHAR, -- Ej. "Entrega inmediata", "Llega mañana"
    "comments" TEXT,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW()
);
```

---

## 2. Directrices de Arquitectura para el Agente Backend

### Alineación estricta con la Fase 1
1. **Sedes Clínicas:** En el diseño original, `Prescription` y `MedicalDocument` apuntaban al `clinicId`. Esto es un error arquitectónico porque los reposos médicos y los récipes **deben llevar la dirección exacta y sello de la sede física**. Es obligatorio que apunten al `clinicBranchId`.
2. **Normalización Geográfica:** El diseño original del Marketplace tenía campos de texto libre como `state` y `city` en el perfil de la farmacia (`ProviderProfile`) y en la solicitud de cotización (`QuoteRequest`). Esto destruiría el motor de búsqueda. Obligatoriamente deben apuntar a la tabla maestra `City` (`cityId`) que creamos en la Fase 1.

### Dinero y Precios
En la tabla `QuoteOffer`, el campo `price` debe ser del tipo numérico de alta precisión (como `DECIMAL` o `NUMERIC` en Postgres, o `Decimal` en Prisma), **jamás utilizar Float o Double** para manejar valores monetarios por los problemas de redondeo en punto flotante.

### Manejo de Tokens Públicos (`publicToken`)
Las tablas `Prescription` y `MedicalDocument` poseen un `publicToken`. Este token debe generarse usando un algoritmo seguro (ej. un nano ID o un hash alfanumérico corto pero criptográficamente fuerte de al menos 10 caracteres). Este será el identificador que irá incrustado en el **Código QR** del récipe impreso para que las farmacias o los empleadores (en caso de reposos) puedan verificar su autenticidad sin necesidad de iniciar sesión en LUCA.
