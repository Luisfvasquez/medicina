# Documentación de API: Autenticación y Registro (Fase 1)

Este documento establece los estándares de comunicación y el contrato técnico de los endpoints de Autenticación y Registro de **LUCA Health OS**. Está diseñado para guiar al equipo de Frontend (Web / Mobile) en la integración de estos flujos.

---

## 1. Estándares Generales de Comunicación

### Formato de JSON
* **Naming Case:** Todos los JSON de respuesta y envío utilizan **`camelCase`** en sus llaves (ej: `accessToken`, `providerType`, `cityId`).
* **Fechas:** Se transmiten en formato **ISO 8601 en UTC** (`YYYY-MM-DDTHH:mm:ss.sssZ`, ej: `2026-06-30T16:25:00.000Z`).

### Manejo de Sesión (Cookies de Autenticación)
* El backend inyecta una cookie HTTP-Only segura llamada **`auth_token`** tras un inicio de sesión o registro exitoso.
* **Seguridad:** `HttpOnly; Secure; SameSite=Strict; Path=/`.
* El frontend no necesita almacenar el token en `localStorage`. El navegador lo incluirá de manera automática en todas las peticiones subsecuentes al backend.

### Idempotencia
* Los endpoints mutantes (`POST` de registro, creación de cotizaciones, etc.) admiten el header `Idempotency-Key` conteniendo un UUIDv4 generado en el cliente. Esto previene re-envíos duplicados ante micro-cortes de red.

---

## 2. Estándar de Errores (RFC 7807)

Todos los errores del servidor (`4xx` y `5xx`) retornan un JSON unificado bajo la especificación **RFC 7807 (Problem Details for HTTP APIs)**.

### Estructura de Error de Validación (HTTP 422)
```json
{
  "type": "https://api.pharmako.com/errors/validation",
  "title": "Error de Validación",
  "status": 422,
  "detail": "Uno o más campos enviados no cumplen con las reglas requeridas.",
  "instance": "/api/v1/auth/users/register/doctor",
  "invalidParams": [
    {
      "name": "email",
      "reason": "El correo ingresado ya se encuentra registrado."
    }
  ]
}
```

---

## 3. Resolución Híbrida de Geolocalización (UUID a BIGINT)

Para mantener la base de datos veloz y normalizada utilizando claves numéricas (`BIGINT`) pero exponiendo identificadores seguros al exterior, el sistema utiliza un mecanismo híbrido:
1. El cliente **debe enviar un UUID** válido de la ciudad en el campo `cityId`.
2. El backend intercepta este UUID, valida su existencia contra la columna `uuid` en la tabla `cities`, y resuelve internamente el ID incremental `BIGINT` para asociarlo de manera eficiente a las tablas de negocio.

---

## 4. Registro de Cuentas (Endpoints)

### A. Registro de Paciente
* **Ruta:** `POST /api/v1/auth/patients/register`
* **Content-Type:** `application/json`

**Parámetros del Body:**
```json
{
  "fullName": "Juan Pérez",
  "email": "juan.perez@email.com",
  "phone": "+584121234567",
  "password": "PasswordSeguro123",
  "cityId": "a5e88d12-1402-4756-829d-47201bfa54d2" // UUID de la ciudad
}
```

### B. Registro de Doctor Médico
* **Ruta:** `POST /api/v1/auth/users/register/doctor`
* **Content-Type:** `multipart/form-data`

**Parámetros Form-Data:**
* `fullName` (String, Requerido)
* `email` (String, Requerido)
* `password` (String, Requerido, Min: 8)
* `phone` (String, Opcional)
* `cityId` (UUID, Requerido) - UUID de la ciudad.
* `specialtyUuids` (Array de UUIDs, Requerido) - Ej: `specialtyUuids[0] = "uuid-de-especialidad"`.
* `medicalLicense` (File, Requerido) - Documento PDF/Imagen (máx 10MB) que verifique su licencia médica para el proceso KYC.

### C. Registro de Proveedor (Farmacia / Laboratorio)
* **Ruta:** `POST /api/v1/auth/users/register/provider`
* **Content-Type:** `multipart/form-data`

**Parámetros Form-Data:**
* `fullName` (String, Requerido) - Nombre del representante legal.
* `email` (String, Requerido)
* `password` (String, Requerido, Min: 8)
* `commercialName` (String, Requerido) - Nombre de la farmacia o laboratorio.
* `providerType` (String, Requerido) - Debe ser exactamente `PHARMACY` o `LABORATORY`.
* `rif` (String, Requerido) - Registro de Identificación Fiscal.
* `cityId` (UUID, Requerido) - UUID de la ciudad.
* `phone` (String, Opcional)
* `businessDocument` (File, Requerido) - Documento PDF/Imagen (máx 10MB) del RIF o Registro Mercantil para el proceso KYC.

---

## 5. El Flujo de Clínicas / Instituciones Médicas (B2B)

> [!IMPORTANT]
> **Las Clínicas NO tienen auto-registro público vía API.**
> 
> A diferencia de los Doctores independientes o los Proveedores comerciales (Farmacias/Laboratorios), las Clínicas son tratadas como entidades corporativas B2B. Su incorporación en la plataforma es administrativa:
> - **Se registran mediante semillas (Seeders):** En ambientes locales/testings mediante `ClinicSeeder.php`.
> - **Se configuran por soporte administrativo:** En ambiente de producción, su creación y habilitación se realiza a través del Panel de Control de Administración Central de LUCA.
> - **Invitación a Doctores:** Una vez creada la clínica por administración, se asocian sus sucursales (`clinic_branches`) y se asignan o invitan doctores a las mismas.

---

## 6. Endpoints de Autenticación y Flujo OTP

### A. Solicitar Código OTP
* **Ruta:** `POST /api/v1/auth/send-otp`
* **Body (WhatsApp):**
  ```json
  {
    "phone": "+584121234567",
    "role": "DOCTOR",
    "channel": "WHATSAPP"
  }
  ```
* **Body (Email):**
  ```json
  {
    "email": "doctor@email.com",
    "role": "DOCTOR",
    "channel": "EMAIL"
  }
  ```
  *(Nota: `role` accepts `DOCTOR`, `PROVIDER`, `ADMIN`, `PATIENT`)*

### B. Verificar OTP e Iniciar Sesión
* **Ruta:** `POST /api/v1/auth/verify-otp`
* **Body (WhatsApp):**
  ```json
  {
    "phone": "+584121234567",
    "code": "123456"
  }
  ```
* **Respuesta Exitosa (200 OK):**
  * *Headers:* Genera la cookie segura `auth_token` con el JWT de sesión.
  * *Body:*
    ```json
    {
      "user": {
        "id": "353e7120-73e8-471b-8910-e2da89daf025",
        "fullName": "Dr. Carlos Mendoza",
        "email": "doctor@email.com",
        "phone": "+584121234567",
        "role": "DOCTOR"
      }
    }
    ```

### C. Login Tradicional (Contraseña)
* **Ruta:** `POST /api/v1/auth/login-password`
* **Body:**
  ```json
  {
    "email": "doctor@email.com",
    "password": "miSuperClave123!"
  }
  ```
* **Respuesta Exitosa (200 OK):**
  * *Headers:* Genera la cookie segura `auth_token` con el JWT de sesión.
  * *Body:* (Mismo formato que verify-otp)

### D. Obtener Usuario Actual
* **Ruta:** `GET /api/v1/auth/me`
* **Respuesta Exitosa (200 OK):** Retorna la información detallada del perfil basándose en la cookie `auth_token` activa.

### E. Cerrar Sesión (Logout)
* **Ruta:** `POST /api/v1/auth/logout`
* **Respuesta Exitosa (200 OK):** Expira y limpia la cookie `auth_token` del cliente.
