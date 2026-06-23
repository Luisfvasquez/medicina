# LUCA Health OS - Plan Arquitectónico: Operaciones y Cumplimiento (Fase 4)

> **Documento de Diseño para el Agente Backend**
> Este documento engloba los módulos finales para convertir el sistema en una plataforma nivel Enterprise. Abarca Resultados Médicos, Facturación, Inventarios de Farmacia, y los críticos módulos de Cumplimiento Legal (Log de Auditoría HIPAA y KYC).

---

## 1. El Script SQL (Plan de Estructura)

```sql
-- ==============================================================================
-- LUCA Health OS - Fase 4: Operaciones, Pagos y Auditoría
-- ==============================================================================

-- 13. NOTIFICACIONES (In-App)
CREATE TABLE "Notification" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"),
    "type" VARCHAR NOT NULL, -- SYSTEM, NEW_QUOTE_REQUEST, QUOTE_RECEIVED, FOLLOW_UP_ALERT
    "title" VARCHAR NOT NULL,
    "message" VARCHAR NOT NULL,
    "isRead" BOOLEAN NOT NULL DEFAULT false,
    "link" VARCHAR, -- Deeplink a la vista correspondiente
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 14. RESULTADOS DE LABORATORIO
CREATE TABLE "LabResult" (
    "id" UUID PRIMARY KEY,
    "labRequestId" UUID NOT NULL UNIQUE REFERENCES "LabRequest"("id"), -- 1:1 con la orden
    "patientId" UUID NOT NULL REFERENCES "Patient"("id"),
    "fileUrl" VARCHAR, -- PDF de resultados
    "resultJson" JSONB, -- Resultados estructurados
    "notes" TEXT,
    "reviewedBy" UUID REFERENCES "User"("id"), -- El médico que revisó el resultado
    "reviewedAt" TIMESTAMP,
    "status" VARCHAR NOT NULL DEFAULT 'PENDING', -- PENDING, COMPLETED, ABNORMAL, CANCELLED
    "performedAt" TIMESTAMP, -- Fecha real del examen
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 15. INVENTARIO DE FARMACIAS
CREATE TABLE "PharmacyInventory" (
    "id" UUID PRIMARY KEY,
    "providerId" UUID NOT NULL REFERENCES "ProviderProfile"("id"),
    "medicationId" UUID NOT NULL REFERENCES "Medication"("id"),
    "stock" INT NOT NULL DEFAULT 0,
    "minStockAlert" INT DEFAULT 10, -- Nivel de alerta baja
    "batchNumber" VARCHAR, -- Número de lote
    "expirationDate" DATE,
    "unitPrice" DECIMAL(10,2), -- JAMÁS usar Float
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE ("providerId", "medicationId", "batchNumber") -- Previene lotes duplicados en la misma farmacia
);

-- 16. FACTURACIÓN Y PAGOS (Billing)
CREATE TABLE "Invoice" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"), -- Médico o Institución que emite
    "patientId" UUID NOT NULL REFERENCES "Patient"("id"),
    "clinicBranchId" UUID REFERENCES "ClinicBranch"("id"), -- Sede donde se facturó
    "consultationId" UUID REFERENCES "Consultation"("id"),
    "prescriptionId" UUID REFERENCES "Prescription"("id"),
    "subtotal" DECIMAL(10,2) NOT NULL DEFAULT 0,
    "tax" DECIMAL(10,2) DEFAULT 0,
    "discount" DECIMAL(10,2) DEFAULT 0,
    "total" DECIMAL(10,2) NOT NULL DEFAULT 0,
    "currency" VARCHAR NOT NULL DEFAULT 'USD',
    "status" VARCHAR NOT NULL DEFAULT 'DRAFT', -- DRAFT, SENT, PAID, PARTIALLY_PAID, OVERDUE, CANCELLED
    "dueDate" DATE,
    "notes" TEXT,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE "InvoiceItem" (
    "id" UUID PRIMARY KEY,
    "invoiceId" UUID NOT NULL REFERENCES "Invoice"("id") ON DELETE CASCADE,
    "description" VARCHAR NOT NULL,
    "quantity" INT NOT NULL DEFAULT 1,
    "unitPrice" DECIMAL(10,2) NOT NULL DEFAULT 0,
    "total" DECIMAL(10,2) NOT NULL DEFAULT 0
);

CREATE TABLE "Payment" (
    "id" UUID PRIMARY KEY,
    "invoiceId" UUID NOT NULL REFERENCES "Invoice"("id") ON DELETE CASCADE,
    "amount" DECIMAL(10,2) NOT NULL,
    "method" VARCHAR NOT NULL, -- CASH, CARD, TRANSFER, INSURANCE, OTHER
    "reference" VARCHAR, -- ID de Stripe/Zelle
    "paidAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "notes" TEXT
);

-- 17. AUDITORÍA (HIPAA / Cumplimiento Médico)
CREATE TABLE "AuditLog" (
    "id" UUID PRIMARY KEY,
    "userId" UUID REFERENCES "User"("id") ON DELETE SET NULL, -- Si se borra el usuario, el log debe persistir
    "patientId" UUID REFERENCES "Patient"("id"), -- A qué paciente pertenecen los datos revisados
    "action" VARCHAR NOT NULL, -- VIEW, CREATE, UPDATE, DELETE, EXPORT, PRINT
    "resource" VARCHAR NOT NULL, -- UUID de la entidad
    "resourceType" VARCHAR NOT NULL, -- e.g. "Consultation", "Prescription"
    "details" JSONB, -- Cambios exactos: { "old": {...}, "new": {...} }
    "ipAddress" VARCHAR,
    "userAgent" VARCHAR,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 18. VERIFICACIÓN KYC (Know Your Customer)
CREATE TABLE "VerificationDocument" (
    "id" UUID PRIMARY KEY,
    "userId" UUID NOT NULL REFERENCES "User"("id"),
    "type" VARCHAR NOT NULL, -- MEDICAL_LICENSE, NATIONAL_ID, BUSINESS_RIF
    "fileUrl" VARCHAR NOT NULL,
    "status" VARCHAR NOT NULL DEFAULT 'PENDING', -- PENDING, APPROVED, REJECTED
    "comments" TEXT,
    "createdAt" TIMESTAMP NOT NULL DEFAULT NOW(),
    "updatedAt" TIMESTAMP NOT NULL DEFAULT NOW()
);
```

---

## 2. Directrices de Arquitectura para el Agente Backend

### Cumplimiento Legal (HIPAA) - AuditLog
1. Es el módulo más crítico a nivel legal. La tabla `AuditLog` debe configurarse para que sus registros sean prácticamente **inmutables**. El agente no debe exponer endpoints de borrado para esta tabla.
2. Todo endpoint que haga un `GET` a historias clínicas, consultas o notas privadas del médico, **debe disparar obligatoriamente** la creación de un AuditLog con la acción `VIEW`.
3. Si un usuario se elimina del sistema, su `userId` en la base de datos se vuelve NULL (`ON DELETE SET NULL`), pero el registro de auditoría queda intacto. Jamás hacer un CASCADE sobre logs de auditoría.

### Facturación y Sedes
Para cumplir con la cohesión arquitectónica, agregué el `clinicBranchId` a la tabla `Invoice`. Esto es obligatorio porque una factura legal debe reflejar desde qué sede se prestó el servicio o se vendió el medicamento, y facilita el control fiscal y de reportes por sucursal.

### Eliminación en Cascada (CASCADE)
Para las tablas `InvoiceItem` y `Payment`, se estableció un `ON DELETE CASCADE` con `Invoice`. Si una factura en estado borrador (DRAFT) es eliminada del sistema, todos sus ítems y registros asociados deben borrarse automáticamente para mantener la base de datos limpia.
