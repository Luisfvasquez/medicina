# LUCA Health OS - Base de Datos Completa (Fase 1-5)

> **Documento de Referencia para Backend**
> Consolidación de todos los schemas de las fases 1-4 más el catálogo público (Fase 5).
> Última actualización: 2026-06-24

---

## Tabla de Contenidos

1. [Normalización Geográfica](#1-normalización-geográfica)
2. [Identidad de Pacientes](#2-identidad-de-pacientes)
3. [Usuarios y Doctores](#3-usuarios-y-doctores)
4. [Especialidades](#4-especialidades)
5. [Clínicas y Sucursales](#5-clínicas-y-sucursales)
6. [Proveedores (Farmacias/Laboratorios)](#6-proveedores-farmaciaslaboratorios)
7. [Agenda y Citas](#7-agenda-y-citas)
8. [Formularios Dinámicos](#8-formularios-dinámicos)
9. [Consultas Médicas (SOAP)](#9-consultas-médicas-soap)
10. [Antecedentes Médicos](#10-antecedentes-médicos)
11. [Vademécum y Recetas](#11-vademécum-y-recetas)
12. [Documentos Médicos](#12-documentos-médicos)
13. [Marketplace B2B2C](#13-marketplace-b2b2c)
14. [Notificaciones](#14-notificaciones)
15. [Resultados de Laboratorio](#15-resultados-de-laboratorio)
16. [Inventario de Farmacias](#16-inventario-de-farmacias)
17. [Facturación y Pagos](#17-facturación-y-pagos)
18. [Auditoría HIPAA](#18-auditoría-hipaa)
19. [Verificación KYC](#19-verificación-kyc)

---

## 1. Normalización Geográfica

### Country
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `name` | VARCHAR | NOT NULL | Nombre del país |
| `code` | VARCHAR(2) | NOT NULL | Código ISO (ej: "VE", "CO") |

### State
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `countryId` | UUID | FK → Country | País al que pertenece |
| `name` | VARCHAR | NOT NULL | Nombre del estado/departamento |

### City
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `stateId` | UUID | FK → State | Estado al que pertenece |
| `name` | VARCHAR | NOT NULL | Nombre de la ciudad |

**Arquitectura:** La ubicación se normaliza a nivel ciudad. Las entidades (Usuarios, Clínicas) solo tienen `cityId`. Esto evita anomalías como un usuario con `cityId` = "Madrid" pero `countryId` = "Colombia".

---

## 2. Identidad de Pacientes

### PatientAccount
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `phone` | VARCHAR | UNIQUE, NOT NULL | Teléfono (login principal) |
| `email` | VARCHAR | UNIQUE | Email (opcional) |
| `passwordHash` | VARCHAR | NULL | Contraseña (nullable para OTP puro) |
| `fullName` | VARCHAR | NOT NULL | Nombre completo |
| `avatarUrl` | VARCHAR | | URL del avatar |
| `nationalId` | VARCHAR | UNIQUE | Cédula/Pasaporte |
| `username` | VARCHAR | UNIQUE | Nombre de usuario |
| `cityId` | UUID | FK → City | Ciudad del paciente |
| `isActive` | BOOLEAN | DEFAULT true | Si la cuenta está activa |
| `status` | AccountStatus | DEFAULT 'ACTIVE' | Estado (ACTIVE/WARNED/SUSPENDED/BANNED) |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

**Enums:**
```sql
CREATE TYPE "AccountStatus" AS ENUM ('ACTIVE', 'WARNED', 'SUSPENDED', 'BANNED');
```

---

## 3. Usuarios y Doctores

### User
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `email` | VARCHAR | UNIQUE, NOT NULL | Email (login) |
| `passwordHash` | VARCHAR | NOT NULL | Contraseña hasheada |
| `fullName` | VARCHAR | NOT NULL | Nombre completo |
| `phone` | VARCHAR | | Teléfono |
| `role` | UserRole | NOT NULL, DEFAULT 'DOCTOR' | Rol en el sistema |
| `isActive` | BOOLEAN | DEFAULT true | Si está activo |
| `status` | AccountStatus | DEFAULT 'ACTIVE' | Estado de la cuenta |
| `planType` | PlanType | DEFAULT 'FREE' | Plan de suscripción |
| `logoUrl` | VARCHAR | | URL del logo/foto |
| `signatureUrl` | VARCHAR | | URL de la firma digital |
| `cityId` | UUID | FK → City | Ciudad principal |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

**Enums:**
```sql
CREATE TYPE "UserRole" AS ENUM ('DOCTOR', 'PROVIDER', 'ADMIN');
CREATE TYPE "PlanType" AS ENUM ('FREE', 'PRO', 'ENTERPRISE');
CREATE TYPE "AccountStatus" AS ENUM ('ACTIVE', 'WARNED', 'SUSPENDED', 'BANNED');
```

---

## 4. Especialidades

### Specialty
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `name` | VARCHAR | UNIQUE, NOT NULL | Nombre (ej: "Cardiología") |
| `description` | TEXT | | Descripción |

### DoctorSpecialty (Pivote)
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `userId` | UUID | FK → User, NOT NULL | El doctor |
| `specialtyId` | UUID | FK → Specialty, NOT NULL | La especialidad |
| | | UNIQUE(userId, specialtyId) | Un doctor no repite especialidad |

**Nota:** Relación muchos-a-muchos. Un doctor puede tener múltiples especialidades.

---

## 5. Clínicas y Sucursales

### Clinic
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `name` | VARCHAR | NOT NULL | Nombre de la clínica |
| `rif` | VARCHAR | UNIQUE | RIF fiscal (opcional para consultorios) |
| `logoUrl` | VARCHAR | | URL del logo |
| `website` | VARCHAR | | Sitio web |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

### ClinicBranch
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `clinicId` | UUID | FK → Clinic, NOT NULL | Clínica padre |
| `name` | VARCHAR | NOT NULL | Nombre de la sucursal |
| `address` | VARCHAR | NOT NULL | Dirección completa |
| `cityId` | UUID | FK → City, NOT NULL | Ciudad |
| `phone` | VARCHAR | NOT NULL | Teléfono |
| `isMainBranch` | BOOLEAN | DEFAULT false | Si es la sede principal |
| `latitude` | DECIMAL(10,8) | | Coordenada latitud |
| `longitude` | DECIMAL(11,8) | | Coordenada longitud |
| `googleMapsUrl` | VARCHAR | | Link a Google Maps |
| `observations` | TEXT | | Notas internas |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

**Arquitectura "Organización-Sede":**
- `Clinic` = La marca/organización (ej: "Clínica Central C.A.")
- `ClinicBranch` = La sede física (puede haber 5 en diferentes ciudades)

### ClinicBranchMember
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `userId` | UUID | FK → User, NOT NULL | El doctor/miembro |
| `clinicBranchId` | UUID | FK → ClinicBranch, NOT NULL | La sucursal |
| `role` | ClinicRole | DEFAULT 'DOCTOR' | Rol en la sucursal |
| `department` | VARCHAR | | Departamento/área |
| `officeNumber` | VARCHAR | | Número de consultorio |
| `isActive` | BOOLEAN | DEFAULT true | Si está activo |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |
| | | UNIQUE(userId, clinicBranchId) | Un usuario una sola vez por sucursal |

**Enums:**
```sql
CREATE TYPE "ClinicRole" AS ENUM ('OWNER', 'ADMIN', 'DOCTOR', 'RECEPTIONIST');
```

---

## 6. Proveedores (Farmacias/Laboratorios)

### ProviderProfile
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `userId` | UUID | FK → User, UNIQUE, NOT NULL | Usuario dueño |
| `type` | ProviderType | NOT NULL | PHARMACY o LABORATORY |
| `commercialName` | VARCHAR | NOT NULL | Nombre comercial |
| `rif` | VARCHAR | UNIQUE, NOT NULL | RIF fiscal |
| `address` | VARCHAR | NOT NULL | Dirección sede principal |
| `cityId` | UUID | FK → City, NOT NULL | Ciudad |
| `phone` | VARCHAR | NOT NULL | Teléfono |
| `isOpen` | BOOLEAN | DEFAULT false | Si acepta cotizaciones |
| `isVerified` | BOOLEAN | DEFAULT false | Verificación KYC |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

**Enums:**
```sql
CREATE TYPE "ProviderType" AS ENUM ('PHARMACY', 'LABORATORY');
```

### ProviderBranch
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `providerProfileId` | UUID | FK → ProviderProfile, NOT NULL | Farmacia padre |
| `name` | VARCHAR | NOT NULL | Nombre de la sucursal |
| `address` | VARCHAR | NOT NULL | Dirección |
| `cityId` | UUID | FK → City, NOT NULL | Ciudad |
| `phone` | VARCHAR | NOT NULL | Teléfono |
| `isOpen` | BOOLEAN | DEFAULT false | Si está abierta |
| `isMainBranch` | BOOLEAN | DEFAULT false | Si es la matriz |
| `latitude` | DECIMAL(10,8) | | Coordenada |
| `longitude` | DECIMAL(11,8) | | Coordenada |
| `googleMapsUrl` | VARCHAR | | Link Maps |
| `observations` | TEXT | | Notas |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

---

## 7. Agenda y Citas

### Appointment
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `patientId` | UUID | FK → Patient, NOT NULL | Paciente |
| `userId` | UUID | FK → User, NOT NULL | Doctor |
| `clinicBranchId` | UUID | FK → ClinicBranch, NOT NULL | Sucursal |
| `date` | DATE | NOT NULL | Fecha de la cita |
| `time` | VARCHAR | NOT NULL | Hora (ej: "09:30") |
| `slotTime` | TIME | | Slot normalizado |
| `type` | VARCHAR | NOT NULL | Tipo (primera vez, control) |
| `status` | AppointmentStatus | DEFAULT 'PENDING' | Estado |
| `notes` | TEXT | | Notas |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |
| `deletedAt` | TIMESTAMP | | Soft delete |

**Enums:**
```sql
CREATE TYPE "AppointmentStatus" AS ENUM ('PENDING', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'NO_SHOW');
```

---

## 8. Formularios Dinámicos

### FormTemplate
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `userId` | UUID | FK → User, NULL | NULL = plantilla global del sistema |
| `title` | VARCHAR | NOT NULL | Título (ej: "Control Pediátrico") |
| `specialty` | VARCHAR | | Especialidad asociada |
| `schemaJson` | JSONB | NOT NULL | Estructura del formulario |
| `createdAt` | TIMESTAMP | | Fecha de creación |

---

## 9. Consultas Médicas (SOAP)

### Consultation
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `userId` | UUID | FK → User, NOT NULL | Doctor |
| `patientId` | UUID | FK → Patient, NOT NULL | Paciente |
| `clinicBranchId` | UUID | FK → ClinicBranch, NOT NULL | Sucursal |
| `appointmentId` | UUID | FK → Appointment, UNIQUE | Cita asociada (1:1) |
| `formTemplateId` | UUID | FK → FormTemplate | Plantilla usada |
| `date` | TIMESTAMP | NOT NULL | Fecha/hora de la consulta |
| `status` | VARCHAR | DEFAULT 'pending' | Estado |
| `reason` | VARCHAR | | S: Subjetivo |
| `physicalExam` | TEXT | | O: Objetivo |
| `diagnosis` | VARCHAR | | A: Análisis |
| `treatmentPlan` | TEXT | | P: Plan |
| `dynamicData` | JSONB | | Respuestas del formulario |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

### VitalSign
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `patientId` | UUID | FK → Patient, NOT NULL | Paciente |
| `consultationId` | UUID | FK → Consultation, UNIQUE, NOT NULL | Consulta (1:1) |
| `weight` | FLOAT | | Peso (kg) |
| `height` | FLOAT | | Altura (cm) |
| `systolicBP` | INT | | Presión sistólica |
| `diastolicBP` | INT | | Presión diastólica |
| `heartRate` | INT | | Frecuencia cardíaca |
| `respiratoryRate` | INT | | Frecuencia respiratoria |
| `temperature` | FLOAT | | Temperatura |
| `oxygenSat` | INT | | Saturación de oxígeno |
| `date` | TIMESTAMP | DEFAULT NOW() | Fecha de medición |

### LabRequest
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `consultationId` | UUID | FK → Consultation, UNIQUE, NOT NULL | Consulta (1:1) |
| `examsList` | JSONB | NOT NULL | Array de exámenes |
| `instructions` | TEXT | | Instrucciones |
| `isCompleted` | BOOLEAN | DEFAULT false | Completado |
| `createdAt` | TIMESTAMP | | Fecha de creación |

### FollowUp
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `userId` | UUID | FK → User, NOT NULL | Doctor |
| `patientId` | UUID | FK → Patient, NOT NULL | Paciente |
| `consultationId` | UUID | FK → Consultation | Consulta origen |
| `scheduledDate` | TIMESTAMP | NOT NULL | Fecha programada |
| `status` | VARCHAR | DEFAULT 'PENDING' | PENDING/SENT/RESPONDED |
| `response` | TEXT | | Respuesta del paciente |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

---

## 10. Antecedentes Médicos

### MedicalBackground
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `patientId` | UUID | FK → Patient, UNIQUE, NOT NULL | Paciente (1:1) |
| `hasDiabetes` | BOOLEAN | DEFAULT false | Diabetes |
| `hasHypertension` | BOOLEAN | DEFAULT false | Hipertensión |
| `hasAsthma` | BOOLEAN | DEFAULT false | Asma |
| `otherConditions` | TEXT | | Otras condiciones |
| `pastHospitalizations` | TEXT | | Hospitalizaciones previas |

### SurgicalHistory
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `patientId` | UUID | FK → Patient, NOT NULL | Paciente (1:N) |
| `procedure` | VARCHAR | NOT NULL | Procedimiento |
| `date` | TIMESTAMP | | Fecha |
| `hospital` | VARCHAR | | Hospital |
| `notes` | TEXT | | Notas |

### FamilyHistory
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `patientId` | UUID | FK → Patient, NOT NULL | Paciente (1:N) |
| `condition` | VARCHAR | NOT NULL | Condición |
| `relationship` | VARCHAR | NOT NULL | Parentesco |
| `note` | TEXT | | Nota |

### Lifestyle
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `patientId` | UUID | FK → Patient, UNIQUE, NOT NULL | Paciente (1:1) |
| `smokingStatus` | VARCHAR | | Fumador |
| `alcoholConsumption` | VARCHAR | | Consumo de alcohol |
| `activityLevel` | VARCHAR | | Nivel de actividad |
| `dietType` | VARCHAR | | Tipo de dieta |

### ObstetricHistory
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `patientId` | UUID | FK → Patient, UNIQUE, NOT NULL | Paciente (1:1) |
| `lastPeriodDate` | TIMESTAMP | | Última menstruación |
| `pregnancies` | INT | | Embarazos |
| `births` | INT | | Partos |
| `cesareans` | INT | | Cesáreas |
| `abortions` | INT | | Abortos |
| `contraceptiveMethod` | VARCHAR | | Método anticonceptivo |

### Vaccination
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `patientId` | UUID | FK → Patient, NOT NULL | Paciente (1:N) |
| `vaccine` | VARCHAR | NOT NULL | Vacuna |
| `doseNumber` | INT | NOT NULL | Número de dosis |
| `date` | TIMESTAMP | NOT NULL | Fecha de aplicación |

---

## 11. Vademécum y Recetas

### Medication
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `userId` | UUID | FK → User, NULL | NULL = global, NOT NULL = privado del doctor |
| `activePrinciple` | VARCHAR | NOT NULL | Principio activo |
| `concentration` | VARCHAR | NOT NULL | Concentración |
| `presentation` | VARCHAR | NOT NULL | CAPSULA/TABLETA/JARABE/etc |
| `administrationRoute` | VARCHAR | NOT NULL | ORAL/INTRAVENOSA/TOPICA/etc |
| `commercialName` | VARCHAR | | Nombre comercial |
| `requiresPrescription` | BOOLEAN | DEFAULT true | Requiere receta |
| `contraindications` | TEXT | | Contraindicaciones |
| `isActive` | BOOLEAN | DEFAULT true | Activo |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

### Prescription
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `userId` | UUID | FK → User, NOT NULL | Doctor |
| `patientId` | UUID | FK → Patient, NOT NULL | Paciente |
| `consultationId` | UUID | FK → Consultation, UNIQUE | Consulta asociada |
| `clinicBranchId` | UUID | FK → ClinicBranch | Sucursal |
| `date` | TIMESTAMP | NOT NULL | Fecha de emisión |
| `expirationDate` | TIMESTAMP | NOT NULL | Fecha de vencimiento |
| `notes` | TEXT | | Notas |
| `publicToken` | VARCHAR | UNIQUE, NOT NULL | Token para QR |
| `status` | VARCHAR | DEFAULT 'ACTIVE' | ACTIVE/CANCELLED/EXPIRED |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

### PrescriptionItem
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `prescriptionId` | UUID | FK → Prescription, NOT NULL | Receta padre |
| `medicationId` | UUID | FK → Medication, NOT NULL | Medicamento |
| `dose` | VARCHAR | | Ej: "1 cápsula" |
| `frequency` | VARCHAR | | Ej: "Cada 8 horas" |
| `duration` | VARCHAR | | Ej: "7 días" |
| `quantity` | INT | DEFAULT 1 | Cantidad |
| `notes` | TEXT | | Notas |

### PrescriptionTemplate
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `userId` | UUID | FK → User, NOT NULL | Doctor |
| `title` | VARCHAR | NOT NULL | Título (ej: "Post-operatorio") |

### TemplateItem
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `templateId` | UUID | FK → PrescriptionTemplate, NOT NULL | Plantilla padre |
| `medicationId` | UUID | FK → Medication, NOT NULL | Medicamento |
| `dose` | VARCHAR | | Dosis |
| `frequency` | VARCHAR | | Frecuencia |
| `duration` | VARCHAR | | Duración |

---

## 12. Documentos Médicos

### MedicalDocument
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `userId` | UUID | FK → User, NOT NULL | Doctor |
| `patientId` | UUID | FK → Patient, NOT NULL | Paciente |
| `clinicBranchId` | UUID | FK → ClinicBranch | Sucursal |
| `type` | VARCHAR | NOT NULL | CERTIFICATE/REFERRAL/REPORT |
| `content` | TEXT | NOT NULL | Contenido del documento |
| `publicToken` | VARCHAR | UNIQUE, NOT NULL | Token para verificación |
| `createdAt` | TIMESTAMP | | Fecha de creación |

**Tipos:**
- `CERTIFICATE`: Reposos médicos
- `REFERRAL`: Referencias a otros especialistas
- `REPORT`: Informes médicos

---

## 13. Marketplace B2B2C

### QuoteRequest
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `prescriptionId` | UUID | FK → Prescription, NOT NULL | Receta a cotizar |
| `patientId` | UUID | FK → Patient, NOT NULL | Paciente |
| `cityId` | UUID | FK → City, NOT NULL | Ciudad donde busca |
| `status` | VARCHAR | DEFAULT 'OPEN' | OPEN/CLOSED |
| `createdAt` | TIMESTAMP | | Fecha de creación |

### QuoteOffer
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `quoteRequestId` | UUID | FK → QuoteRequest, NOT NULL | Solicitud padre |
| `providerId` | UUID | FK → ProviderProfile, NOT NULL | Farmacia |
| `price` | DECIMAL(10,2) | NOT NULL | Precio total |
| `currency` | VARCHAR | DEFAULT 'USD' | Moneda |
| `availability` | VARCHAR | | Disponibilidad |
| `comments` | TEXT | | Comentarios |
| `createdAt` | TIMESTAMP | | Fecha de creación |

---

## 14. Notificaciones

### Notification
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `userId` | UUID | FK → User, NOT NULL | Destinatario |
| `type` | VARCHAR | NOT NULL | SYSTEM/NEW_QUOTE_REQUEST/etc |
| `title` | VARCHAR | NOT NULL | Título |
| `message` | VARCHAR | NOT NULL | Mensaje |
| `isRead` | BOOLEAN | DEFAULT false | Leído |
| `link` | VARCHAR | | Deeplink |
| `createdAt` | TIMESTAMP | | Fecha de creación |

---

## 15. Resultados de Laboratorio

### LabResult
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `labRequestId` | UUID | FK → LabRequest, UNIQUE, NOT NULL | Orden (1:1) |
| `patientId` | UUID | FK → Patient, NOT NULL | Paciente |
| `fileUrl` | VARCHAR | | URL del PDF |
| `resultJson` | JSONB | | Resultados estructurados |
| `notes` | TEXT | | Notas |
| `reviewedBy` | UUID | FK → User | Doctor que revisó |
| `reviewedAt` | TIMESTAMP | | Fecha de revisión |
| `status` | VARCHAR | DEFAULT 'PENDING' | PENDING/COMPLETED/ABNORMAL/CANCELLED |
| `performedAt` | TIMESTAMP | | Fecha del examen |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

---

## 16. Inventario de Farmacias

### PharmacyInventory
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `providerId` | UUID | FK → ProviderProfile, NOT NULL | Farmacia |
| `medicationId` | UUID | FK → Medication, NOT NULL | Medicamento |
| `stock` | INT | DEFAULT 0 | Cantidad actual |
| `minStockAlert` | INT | DEFAULT 10 | Nivel de alerta |
| `batchNumber` | VARCHAR | | Número de lote |
| `expirationDate` | DATE | | Fecha de vencimiento |
| `unitPrice` | DECIMAL(10,2) | | Precio unitario |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |
| | | UNIQUE(providerId, medicationId, batchNumber) | Previene duplicados |

---

## 17. Facturación y Pagos

### Invoice
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `userId` | UUID | FK → User, NOT NULL | Emisor |
| `patientId` | UUID | FK → Patient, NOT NULL | Paciente |
| `clinicBranchId` | UUID | FK → ClinicBranch | Sucursal |
| `consultationId` | UUID | FK → Consultation | Consulta asociada |
| `prescriptionId` | UUID | FK → Prescription | Receta asociada |
| `subtotal` | DECIMAL(10,2) | DEFAULT 0 | Subtotal |
| `tax` | DECIMAL(10,2) | DEFAULT 0 | Impuesto |
| `discount` | DECIMAL(10,2) | DEFAULT 0 | Descuento |
| `total` | DECIMAL(10,2) | DEFAULT 0 | Total |
| `currency` | VARCHAR | DEFAULT 'USD' | Moneda |
| `status` | VARCHAR | DEFAULT 'DRAFT' | DRAFT/SENT/PAID/PARTIALLY_PAID/OVERDUE/CANCELLED |
| `dueDate` | DATE | | Fecha de vencimiento |
| `notes` | TEXT | | Notas |
| `createdAt` | TIMESTAMP | | Fecha de creación |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

### InvoiceItem
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `invoiceId` | UUID | FK → Invoice, NOT NULL, CASCADE | Factura padre |
| `description` | VARCHAR | NOT NULL | Descripción |
| `quantity` | INT | DEFAULT 1 | Cantidad |
| `unitPrice` | DECIMAL(10,2) | DEFAULT 0 | Precio unitario |
| `total` | DECIMAL(10,2) | DEFAULT 0 | Total línea |

### Payment
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `invoiceId` | UUID | FK → Invoice, NOT NULL, CASCADE | Factura padre |
| `amount` | DECIMAL(10,2) | NOT NULL | Monto pagado |
| `method` | VARCHAR | NOT NULL | CASH/CARD/TRANSFER/INSURANCE/OTHER |
| `reference` | VARCHAR | | Referencia (Stripe/Zelle) |
| `paidAt` | TIMESTAMP | DEFAULT NOW() | Fecha de pago |
| `notes` | TEXT | | Notas |

---

## 18. Auditoría HIPAA

### AuditLog
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `userId` | UUID | FK → User, SET NULL | Usuario que realizó la acción |
| `patientId` | UUID | FK → Patient | Paciente afectado |
| `action` | VARCHAR | NOT NULL | VIEW/CREATE/UPDATE/DELETE/EXPORT/PRINT |
| `resource` | VARCHAR | NOT NULL | UUID del recurso |
| `resourceType` | VARCHAR | NOT NULL | Tipo (Consultation/Prescription/etc) |
| `details` | JSONB | | Cambios: {old: {}, new: {}} |
| `ipAddress` | VARCHAR | | IP del cliente |
| `userAgent` | VARCHAR | | User agent |
| `createdAt` | TIMESTAMP | | Fecha |

**Notas:**
- `ON DELETE SET NULL` en userId (preserva logs si se elimina usuario)
- NO exponer endpoint de DELETE para esta tabla
- Todo GET a datos sensibles debe crear un log

---

## 19. Verificación KYC

### VerificationDocument
| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PK | Identificador único |
| `uuid` | VARCHAR | UNIQUE | UUID público |
| `userId` | UUID | FK → User, NOT NULL | Usuario |
| `type` | VARCHAR | NOT NULL | MEDICAL_LICENSE/NATIONAL_ID/BUSINESS_RIF |
| `fileUrl` | VARCHAR | NOT NULL | URL del documento |
| `status` | VARCHAR | DEFAULT 'PENDING' | PENDING/APPROVED/REJECTED |
| `comments` | TEXT | | Comentarios del revisor |
| `createdAt` | TIMESTAMP | | Fecha de subida |
| `updatedAt` | TIMESTAMP | | Fecha de actualización |

---

## Resumen de Relaciones

```
Country ← State ← City
                    ↑
                    │
PatientAccount ← Patient ← Appointment ← Consultation
      ↑                  ↑              ↑
      │                  │              │
      └── User ◯─────────┼──────────────┘
         ↑    ↑           │
         │    │           │
    DoctorSpecialty    ClinicBranch
         ↑    
         │
    Specialty

User ← ProviderProfile ← ProviderBranch
           ↑
           └── PharmacyInventory → Medication

Clinic ← ClinicBranch ← ClinicBranchMember → User
```

---

## Enums Consolidado

| Enum | Valores |
|------|---------|
| AccountStatus | ACTIVE, WARNED, SUSPENDED, BANNED |
| UserRole | DOCTOR, PROVIDER, ADMIN |
| PlanType | FREE, PRO, ENTERPRISE |
| ClinicRole | OWNER, ADMIN, DOCTOR, RECEPTIONIST |
| ProviderType | PHARMACY, LABORATORY |
| AppointmentStatus | PENDING, CONFIRMED, IN_PROGRESS, COMPLETED, CANCELLED, NO_SHOW |
| Weekday | MONDAY, TUESDAY, WEDNESDAY, THURSDAY, FRIDAY, SATURDAY, SUNDAY |
| ExceptionType | VACATION, DAY_OFF, CUSTOM_HOURS |
