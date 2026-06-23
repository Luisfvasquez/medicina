# LUCA Health OS - Diccionario de Base de Datos (Fase 1)

> **Documento de Arquitectura para Backend**
> Este documento detalla la primera fase fundacional de la base de datos, enfocada en Autenticación, Normalización de Ubicaciones, Perfiles de Usuario y la Arquitectura "Organización-Sede".

---

## 1. El Script SQL (Fase 1)

Este es el script consolidado que debes ejecutar en PostgreSQL para inicializar la estructura.

```sql
-- 1. LOCATION MASTER TABLES
CREATE TABLE "Country" (
    "id" UUID PRIMARY KEY,
    "name" VARCHAR NOT NULL,
    "code" VARCHAR(2) NOT NULL
);

CREATE TABLE "State" (
    "id" UUID PRIMARY KEY,
    "countryId" UUID NOT NULL REFERENCES "Country"("id"),
    "name" VARCHAR NOT NULL
);

CREATE TABLE "City" (
    "id" UUID PRIMARY KEY,
    "stateId" UUID NOT NULL REFERENCES "State"("id"),
    "name" VARCHAR NOT NULL
);

-- 2. GLOBAL PATIENT IDENTITY
CREATE TYPE "AccountStatus" AS ENUM ('ACTIVE', 'WARNED', 'SUSPENDED', 'BANNED');

CREATE TABLE "PatientAccount" (
    "id" UUID PRIMARY KEY,
    "phone" VARCHAR NOT NULL UNIQUE,
    "email" VARCHAR UNIQUE,
    "passwordHash" VARCHAR,
    "fullName" VARCHAR NOT NULL,
    "avatarUrl" VARCHAR,
    "nationalId" VARCHAR UNIQUE,
    "username" VARCHAR UNIQUE,
    "cityId" UUID REFERENCES "City"("id"),
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "status" "AccountStatus" NOT NULL DEFAULT 'ACTIVE',
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 3. USERS (DOCTORS, ADMINS, PROVIDER OWNERS)
CREATE TYPE "UserRole" AS ENUM ('DOCTOR', 'PROVIDER', 'ADMIN');
CREATE TYPE "PlanType" AS ENUM ('FREE', 'PRO', 'ENTERPRISE');

CREATE TABLE "User" (
    "id" UUID PRIMARY KEY,
    "email" VARCHAR NOT NULL UNIQUE,
    "passwordHash" VARCHAR NOT NULL,
    "fullName" VARCHAR NOT NULL,
    "phone" VARCHAR,
    "role" "UserRole" NOT NULL DEFAULT 'DOCTOR',
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "status" "AccountStatus" NOT NULL DEFAULT 'ACTIVE',
    "planType" "PlanType" NOT NULL DEFAULT 'FREE',
    "logoUrl" VARCHAR,
    "signatureUrl" VARCHAR,
    "cityId" UUID REFERENCES "City"("id"),
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 4. SPECIALTIES NORMALIZATION
CREATE TABLE "Specialty" (
    "id" UUID PRIMARY KEY,
    "name" VARCHAR NOT NULL UNIQUE,
    "description" TEXT
);

CREATE TABLE "DoctorSpecialty" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"),
    "specialtyId" UUID NOT NULL REFERENCES "Specialty"("id"),
    UNIQUE ("userId", "specialtyId")
);

-- 5. CLINICS (Institutional)
CREATE TABLE "Clinic" (
    "id" UUID PRIMARY KEY,
    "name" VARCHAR NOT NULL,
    "rif" VARCHAR UNIQUE,
    "logoUrl" VARCHAR,
    "website" VARCHAR,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE "ClinicBranch" (
    "id" UUID PRIMARY KEY,
    "clinicId" UUID NOT NULL REFERENCES "Clinic"("id"),
    "name" VARCHAR NOT NULL,
    "address" VARCHAR NOT NULL,
    "cityId" UUID NOT NULL REFERENCES "City"("id"),
    "phone" VARCHAR NOT NULL,
    "isMainBranch" BOOLEAN NOT NULL DEFAULT false,
    "latitude" DECIMAL(10, 8),
    "longitude" DECIMAL(11, 8),
    "googleMapsUrl" VARCHAR,
    "observations" TEXT,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TYPE "ClinicRole" AS ENUM ('OWNER', 'ADMIN', 'DOCTOR', 'RECEPTIONIST');
CREATE TABLE "ClinicBranchMember" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"),
    "clinicBranchId" UUID NOT NULL REFERENCES "ClinicBranch"("id"),
    "role" "ClinicRole" NOT NULL DEFAULT 'DOCTOR',
    "department" VARCHAR,
    "officeNumber" VARCHAR,
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE ("userId", "clinicBranchId")
);

-- 6. PROVIDERS (Pharmacies & Labs)
CREATE TYPE "ProviderType" AS ENUM ('PHARMACY', 'LABORATORY');

CREATE TABLE "ProviderProfile" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id") UNIQUE,
    "type" "ProviderType" NOT NULL,
    "commercialName" VARCHAR NOT NULL,
    "rif" VARCHAR NOT NULL UNIQUE,
    "isVerified" BOOLEAN NOT NULL DEFAULT false,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE "ProviderBranch" (
    "id" UUID PRIMARY KEY,
    "providerProfileId" UUID NOT NULL REFERENCES "ProviderProfile"("id"),
    "name" VARCHAR NOT NULL,
    "address" VARCHAR NOT NULL,
    "cityId" UUID NOT NULL REFERENCES "City"("id"),
    "phone" VARCHAR NOT NULL,
    "isOpen" BOOLEAN NOT NULL DEFAULT false,
    "isMainBranch" BOOLEAN NOT NULL DEFAULT false,
    "latitude" DECIMAL(10, 8),
    "longitude" DECIMAL(11, 8),
    "googleMapsUrl" VARCHAR,
    "observations" TEXT,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 7. PATIENT RECORDS (Doctor's CRM)
CREATE TYPE "Gender" AS ENUM ('MALE', 'FEMALE', 'OTHER');

CREATE TABLE "Patient" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"),
    "patientAccountId" UUID REFERENCES "PatientAccount"("id"),
    "firstName" VARCHAR NOT NULL,
    "lastName" VARCHAR NOT NULL,
    "nationalId" VARCHAR NOT NULL,
    "birthDate" TIMESTAMP NOT NULL,
    "gender" "Gender" NOT NULL,
    "email" VARCHAR,
    "phone" VARCHAR,
    "address" VARCHAR,
    "cityId" UUID REFERENCES "City"("id"),
    "emergencyContactName" VARCHAR,
    "emergencyContactPhone" VARCHAR,
    "accessCode" VARCHAR UNIQUE,
    "lastLogin" TIMESTAMP,
    "bloodType" VARCHAR,
    "allergies" VARCHAR,
    "chronicConditions" VARCHAR,
    "privateNotes" TEXT,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);
```

---

## 2. Diccionario y Arquitectura de Tablas

### Módulo 1: Normalización de Ubicaciones
**Tablas:** `Country`, `State`, `City`
* **¿Por qué?** Para que el futuro Marketplace pueda filtrar exactamente por ciudad. Si dejamos que la ubicación sea "texto libre", la base de datos no sabrá que "CABA" y "Buenos Aires" son lo mismo.
* **Uso Arquitectónico:** Fíjate que a las entidades (Usuarios, Clínicas) **solo se les asigna el `cityId`**. No les ponemos `stateId` ni `countryId` porque la ciudad ya sabe a qué estado pertenece. Esto evita anomalías de datos (Ej: Un usuario con `cityId` = Madrid pero `countryId` = Colombia).

### Módulo 2: Identidad del Paciente
**Tabla:** `PatientAccount`
* **Concepto:** Representa la identidad global del paciente en todo LUCA. Un paciente puede atenderse con 10 doctores distintos, pero solo tiene 1 `PatientAccount`.
* **Login:** Pensada para Login por WhatsApp (OTP), por lo que el `phone` es `UNIQUE` y el `passwordHash` permite nulos (si deciden no usar clave).
* **Escalabilidad:** Tiene `nationalId` y `username` únicos para permitir compartir perfiles o realizar búsquedas.

### Módulo 3: Autenticación de Profesionales
**Tablas:** `User`, `Specialty`, `DoctorSpecialty`
* **`User`:** Es la base para cualquier administrador, doctor, o dueño de farmacia. Define roles y plan de pago (`PlanType`).
* **`DoctorSpecialty`:** Tabla pivote (Muchos-a-Muchos). Un doctor no es solo "Cardiólogo", puede ser "Cirujano" y "Cardiólogo". Se normalizó para poder hacer reportes de especialidades a nivel sistema.

### Módulo 4: Arquitectura Institucional (Marca vs Edificio)
**Tablas:** `Clinic`, `ClinicBranch`, `ClinicBranchMember`
* **Concepto "Organización-Sede":** Resolvemos el problema de las cooperativas y clínicas privadas en un solo modelo.
* **`Clinic` (La Organización/Marca):** Guarda datos legales (RIF, Nombre comercial, Logo). El RIF es opcional (`NULL`) para soportar consultorios de médicos independientes que operan con firma personal.
* **`ClinicBranch` (La Sede Física):** Aquí va la ubicación real. Una marca puede tener 10 sedes. Usamos `DECIMAL(10, 8)` en `latitude` y `longitude` para precisión milimétrica en Google Maps y evitar errores de redondeo de los tipos `FLOAT`.
* **`ClinicBranchMember`:** ¡El Santo Grial! Enlaza al Médico directamente con la Sede (no con la clínica global). Tiene campos de `department` y `officeNumber`. 
    * *Caso Consultorio Privado:* El doctor llena su Sede y deja el departamento vacío.
    * *Caso Policlínica:* El doctor llena "Piso 4 - Pediatría", Consultorio 412. Cuando el paciente agenda, sabe exactamente a qué pasillo ir.

### Módulo 5: Proveedores del Marketplace
**Tablas:** `ProviderProfile`, `ProviderBranch`
* **Concepto:** Idéntico a las clínicas. "Farmatodo" es un `ProviderProfile`. Sus 300 locales son `ProviderBranch`.
* **Disponibilidad:** `isOpen` está a nivel de la Sede (`ProviderBranch`), porque la sede principal puede ser 24 horas (`true`), pero la sede del centro comercial puede estar cerrada (`false`).

### Módulo 6: El CRM del Doctor
**Tabla:** `Patient`
* **Concepto Clave:** ¡No confundir con `PatientAccount`!
* **`PatientAccount`:** Es el "usuario de WhatsApp" dueño de sus datos globales.
* **`Patient`:** Es la "Ficha Médica" privada que el doctor le crea a ese paciente en su computadora. Tiene campos como `privateNotes` (que el paciente jamás ve) y está enlazado obligatoriamente al Doctor (`userId`). Si el paciente va a 3 doctores en LUCA, existirán 3 filas `Patient` distintas, todas apuntando a 1 solo `PatientAccount`.
