# Especificación de Arquitectura de Backend: Autenticación JWT y Módulos del Sistema

Este documento define la estructura de trabajo, la lógica de negocio y los componentes de software (Modelos, Controladores, Form Requests y Middlewares) necesarios para implementar el backend del proyecto en **Laravel**. Toda la comunicación se realizará exclusivamente a través de **APIs RESTful** y la autenticación se gestionará mediante **JSON Web Tokens (JWT)** empleando un enfoque **Multi-Guard**.

---

## 1. Arquitectura de Autenticación JWT (Multi-Guard)

Dado que el sistema separa de forma estricta las identidades globales de los pacientes (`PatientAccount`) de los usuarios internos del ecosistema (`User`: Doctores, Proveedores, Administradores), se deben configurar dos Guards independientes en Laravel. Esto evita la coexistencia de campos nulos masivos y mantiene la integridad referencial.

### Configuración de Guards (`config/auth.php`)

```php
'guards' => [
    'user_api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
    'patient_api' => [
        'driver' => 'jwt',
        'provider' => 'patient_accounts',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    'patient_accounts' => [
        'driver' => 'eloquent',
        'model' => App\Models\PatientAccount::class,
    ],
],

```

### Estructura del Token JWT (Custom Claims)

Para reducir las consultas a la base de datos en peticiones subsecuentes, el payload del token JWT debe incluir claims personalizados:

* **Para `User`**: `id`, `email`, `role` (`DOCTOR`, `PROVIDER`, `ADMIN`), `isActive`.
* **Para `PatientAccount`**: `id`, `email`, `phone`.

---

## 2. Mapa Completo de Módulos del Sistema

A partir del esquema de base de datos provisto, el sistema se segmenta en los siguientes dominios de backend:

1. **Módulo de Identidad Maestra:** Gestión de la cuenta global del paciente (`PatientAccount`).
2. **Módulo de Usuarios y Roles:** Control de Doctores, Proveedores y Administradores (`User`, `UserRole`, `PlanType`).
3. **Módulo de Expedientes (CRM Médico):** Manejo de las fichas locales e historias clínicas que cada doctor posee de un paciente (`Patient`).
4. **Módulo Clínico Dinámico:** Gestión de consultas, motor de formularios dinámicos JSON, signos vitales y solicitudes de laboratorio (`FormTemplate`, `Consultation`, `VitalSign`, `LabRequest`).
5. **Módulo de Recetas (Core):** Emisión de recetas médicas digitales, ítems y plantillas de prescripción (`Prescription`, `PrescriptionItem`, `PrescriptionTemplate`, `TemplateItem`).
6. **Módulo Marketplace:** Cotizaciones y ofertas de medicamentos y exámenes para Farmacias y Laboratorios (`ProviderProfile`, `QuoteRequest`, `QuoteOffer`).
7. **Módulo de Sistema y Seguimiento:** Alertas de auditoría, documentos médicos legales (certificados) y control de seguimiento post-consulta (`Notification`, `MedicalDocument`, `FollowUp`).
8. **Módulo de Antecedentes Profundos:** Registro exhaustivo de antecedentes médicos, quirúrgicos, familiares, estilo de vida y gineco-obstétricos (`MedicalBackground`, `SurgicalHistory`, `FamilyHistory`, `Lifestyle`, `ObstetricHistory`, `Vaccination`).
9. **Módulo Institucional (Clínicas):** Soporte multi-clínica y gestión de personal médico/administrativo adscrito (`Clinic`, `ClinicMember`).
10. **Módulo de Verificación (KYC):** Flujo obligatorio de aprobación de licencias médicas y registros mercantiles corporativos (`VerificationDocument`).

---

## 3. Lógica de Registro y Onboarding por Rol

El flujo de registro es asíncrono y diferenciado. Los endpoints de registro son públicos, pero los accesos posteriores están condicionados por el rol y el estado de verificación.

### A. Registro de Paciente Global (`PatientAccount`)

* **Endpoint:** `POST /api/v1/auth/patients/register`
* **Lógica:** Validación de unicidad de teléfono (WhatsApp) y correo. Se cifra la contraseña mediante `Bcrypt`. Se emite directamente el token JWT.
* **Proceso de Fondo (Queue):** Despacho de un Job para envío de OTP de verificación mediante API de mensajería.

### B. Registro de Doctor (`User` + KYC)

* **Endpoint:** `POST /api/v1/auth/users/register/doctor`
* **Lógica:** 1. Creación del registro en la tabla `User` con `role => 'DOCTOR'`, `isActive => true`, y `planType => 'FREE'`.
2. Almacenamiento seguro en Cloud Storage (ej. Private S3 Bucket) del archivo de la licencia médica o título profesional.
3. Creación del registro en `VerificationDocument` asociado al usuario con `type => 'MEDICAL_LICENSE'` y `status => 'PENDING'`.
* **Proceso de Fondo (Queue):** Notificación por correo al equipo de administración para la auditoría manual del documento. El doctor puede iniciar sesión, pero un middleware bloqueará sus acciones clínicas.

### C. Registro de Proveedor (`User` + `ProviderProfile` + KYC)

* **Endpoint:** `POST /api/v1/auth/users/register/provider`
* **Lógica:**
1. Creación del registro en la tabla `User` con `role => 'PROVIDER'`.
2. Creación atómica (dentro de una transacción de base de datos) del registro en `ProviderProfile` vinculando el `user_id`, capturando el RIF, nombre comercial y tipo (`PHARMACY` o `LABORATORY`), con `isVerified => false`.
3. Carga del RIF digital/Registro Mercantil a Storage y creación del registro en `VerificationDocument`.


* **Procesamiento:** Uso de transacciones de base de datos (`DB::transaction`) para asegurar la consistencia absoluta entre las tablas `User` y `ProviderProfile`.

---

## 4. Directorio de Componentes Laravel a Desarrollar

Para este primer hito de Autenticación, Registro y Control de Identidades, se requiere estructurar los siguientes archivos en la aplicación:

### A. Modelos Eloquent (`app/Models`)

1. **`PatientAccount.php`**
* Implementa `Tymon\JWTAuth\Contracts\JWTSubject`.
* Relaciones: `hasMany(Patient::class)`.
* Atributos ocultos: `passwordHash`.


2. **`User.php`**
* Implementa `Tymon\JWTAuth\Contracts\JWTSubject`.
* Relaciones: `hasOne(ProviderProfile::class)`, `hasMany(VerificationDocument::class)`, `hasMany(ClinicMember::class)`.
* Scopes útiles: `scopeActive($query)`, `scopeDoctors($query)`.


3. **`ProviderProfile.php`**
* Relaciones: `belongsTo(User::class)`.


4. **`VerificationDocument.php`**
* Relaciones: `belongsTo(User::class)`.



### B. Controladores de la API (`app/Http/Controllers/Api/V1/Auth`)

1. **`PatientAuthController.php`**
* Métodos: `register()`, `login()`, `logout()`, `refresh()`, `me()`.
* Gestiona el guard `patient_api`.


2. **`UserAuthController.php`**
* Métodos: `registerDoctor()`, `registerProvider()`, `login()`, `logout()`, `refresh()`, `me()`.
* Gestiona el guard `user_api`. Controla la verificación de la columna `isActive`.



### C. Validaciones de Entrada (`app/Http/Requests/Auth`)

1. **`PatientRegisterRequest.php`**: Reglas de validación (`required|string|unique:patient_accounts|phone:AUTO`).
2. **`DoctorRegisterRequest.php`**: Validación de datos de usuario junto con la de archivos binarios (`required|file|mimes:pdf,jpg,png|max:10240` para la licencia).
3. **`ProviderRegisterRequest.php`**: Validación de campos comerciales (RIF con patrón regex específico) y archivo del registro fiscal o mercantil.
4. **`LoginRequest.php`**: Reglas estándar de email y password.

### D. Middlewares del Sistema (`app/Http/Middleware`)

1. **`EnsureKycIsApproved.php`**
* **Propósito:** Evalúa si el `User` autenticado con rol `DOCTOR` o `PROVIDER` cuenta con al menos un `VerificationDocument` aprobado (`APPROVED`). En caso negativo, retorna un código de estado HTTP `403 Forbidden` indicando "Su documentación se encuentra en revisión. Acceso restringido."


2. **`CheckUserStatus.php`**
* **Propósito:** Intercepta la petición inmediatamente posterior a la verificación del token JWT. Si `User->isActive` es `false`, invalida el token actual de forma forzada y retorna un error `401 Unauthorized` con el mensaje "Cuenta suspendida."



---

## 5. Próximos Pasos en el Backend

1. Configurar las migraciones con los tipos de datos óptimos basados en los esquemas (usando tipos `uuid` o `string` según la estrategia de indexación).
2. Implementar los Repositorios o Servicios dedicados (`AuthService`, `KycService`) para abstraer la lógica pesada fuera de los controladores.
3. Configurar el entorno de testing (`tests/Feature/Auth`) para validar la generación correcta de los tokens JWT y las restricciones de los middlewares ante usuarios no verificados.
