# Documentación de API: Autenticación y Registro (Fase 1)

Este documento detalla la estructura, flujo y consumo de los endpoints de Autenticación de **LUCA Health OS**. Está diseñado para el equipo de Frontend (Web / Móvil) con los requerimientos necesarios para conectar los flujos de registro e inicio de sesión.

---

## Consideraciones Generales Arquitectónicas

1. **Multi-Guard (JWT)**: El sistema posee dos ecosistemas totalmente separados. 
   - **Ecosistema de Pacientes (`patient_api`)**: Usuarios finales que agendan citas.
   - **Ecosistema de Usuarios (`user_api`)**: Profesionales (Doctores), Comercios (Proveedores) y Administradores.
2. **Tokens JWT**: Todas las respuestas exitosas de Login y Register retornarán un token de acceso (`access_token`). Para consumir cualquier ruta protegida, el Frontend debe enviar este token en los Headers HTTP:
   ```http
   Authorization: Bearer <tu_access_token>
   ```
3. **Manejo de Archivos en Registro**: Dado que los Doctores y Proveedores deben subir documentos en formato binario (PDF/JPG/PNG) para la verificación KYC, sus endpoints de registro **deben** ser consumidos utilizando el `Content-Type: multipart/form-data` en lugar del clásico `application/json`.
4. **Idempotencia Obligatoria**: Todas las peticiones `POST` (como Login y Register) requieren un mecanismo estricto de Idempotencia para evitar duplicidades de red o dobles envíos. 
   - El frontend **debe** enviar el Header `Idempotency-Key` conteniendo un identificador único (preferiblemente un UUIDv4) autogenerado desde el lado del cliente por cada intento de formulario (NO cada vez que hay un retry por red, la idea es que si la red falla el mismo retry mande el mismo key).
   ```http
   Idempotency-Key: a1b2c3d4-e5f6-...
   ```
   - Si no se envía este Header, la API retornará inmediatamente un error `400 Bad Request`.

---

## 1. Ecosistema de Pacientes

### A. Registro de Paciente
* **Endpoint:** `POST /api/v1/auth/patients/register`
* **Content-Type:** `application/json`

**Body Request:**
```json
{
  "full_name": "Juan Pérez",
  "email": "juan.perez@email.com",            // Opcional, pero debe ser único si se envía
  "phone": "+584141234567",                   // Requerido y único
  "password": "PasswordSeguro123",            // Opcional (preparado para OTP en un futuro)
  "national_id": "V-12345678",                // Opcional y único
  "username": "juanperez",                    // Opcional y único
  "city_id": "56d77a39-67ed-..."              // UUID de la ciudad, opcional
}
```

### B. Inicio de Sesión de Paciente
* **Endpoint:** `POST /api/v1/auth/patients/login`
* **Content-Type:** `application/json`

**Body Request:**
```json
{
  "email": "juan.perez@email.com",
  "password": "PasswordSeguro123"
}
```

### C. Obtener Perfil Activo (Me)
* **Endpoint:** `GET /api/v1/auth/patients/me`
* **Headers:** `Authorization: Bearer <token>`
* **Respuesta Esperada:** Un objeto JSON con los datos del paciente (sin contraseña).

---

## 2. Ecosistema de Profesionales (Doctores y Proveedores)

### A. Registro de Doctor Médico
* **Endpoint:** `POST /api/v1/auth/users/register/doctor`
* **Content-Type:** `multipart/form-data` (¡Importante!)

**Form-Data Payload:**
| Key | Type | Constraints | Descripción |
|---|---|---|---|
| `full_name` | String | Requerido | Nombre completo del profesional. |
| `email` | String | Requerido, Único | Correo de acceso y contacto. |
| `password` | String | Requerido, Min: 8 | Contraseña de acceso. |
| `phone` | String | Opcional | Número de teléfono de contacto. |
| `city_id` | UUID | Opcional | UUID de la tabla de Ciudades en BD. |
| `specialty_uuids[0]` | UUID | Requerido, Array | El Frontend debe mandar un Array de UUIDs de especialidades. |
| `medical_license` | File | Requerido | Archivo binario (pdf, jpg, png, max: 10MB). |

### B. Registro de Proveedor (Farmacia / Laboratorio)
* **Endpoint:** `POST /api/v1/auth/users/register/provider`
* **Content-Type:** `multipart/form-data`

**Form-Data Payload:**
| Key | Type | Constraints | Descripción |
|---|---|---|---|
| `full_name` | String | Requerido | Nombre del dueño o representante legal. |
| `email` | String | Requerido, Único | Correo administrativo de acceso. |
| `password` | String | Requerido, Min: 8 | Contraseña de acceso. |
| `commercial_name` | String | Requerido | Nombre comercial de la empresa. |
| `provider_type` | String | Requerido | Debe ser estrictamente `PHARMACY` o `LABORATORY`. |
| `rif` | String | Requerido, Único | Registro de Identificación Fiscal. |
| `city_id` | UUID | Opcional | UUID de la tabla de Ciudades en BD. |
| `phone` | String | Opcional | Teléfono de contacto comercial principal. |
| `business_document` | File | Requerido | Archivo del RIF o Registro Mercantil (pdf/jpg/png). |

### C. Inicio de Sesión General (Usuarios)
Este endpoint unifica el inicio de sesión para Doctores, Proveedores y Administradores.
* **Endpoint:** `POST /api/v1/auth/users/login`
* **Content-Type:** `application/json`

**Body Request:**
```json
{
  "email": "doctor@email.com",
  "password": "PasswordSeguro123"
}
```

### D. Obtener Perfil Activo y Validar Roles
* **Endpoint:** `GET /api/v1/auth/users/me`
* **Headers:** `Authorization: Bearer <token>`
* **Uso en el Frontend:** Utiliza el valor de `role` en la respuesta JSON (`DOCTOR`, `PROVIDER`, `ADMIN`) para enrutar condicionalmente al usuario a su respectivo Dashboard.

---

## 3. Respuestas Estándar del Servidor

### A. Éxito en Autenticación (200 OK)
Indistintamente de si es login o registro de paciente o usuario, el payload de éxito retornará el Token Bearer.

```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc... (JWT gigante)",
    "token_type": "bearer",
    "expires_in": 3600
}
```

### B. Errores de Validación (422 Unprocessable Entity)
Cuando el frontend envía datos erróneos, carentes o duplicados (ej: Email ya registrado), Laravel responderá con 422.
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "The email has already been taken."
        ]
    }
}
```

### C. Restricción por Verificación Pendiente (KYC)
El backend procesa la aprobación de documentos manual. Si el Frontend intenta consumir una ruta clínica protegida por el Middleware `EnsureKycIsApproved` y el usuario aún está en revisión, obtendrá un HTTP 403.
```json
{
    "message": "Su documentación se encuentra en revisión. Acceso restringido."
}
```

### D. Cuenta Suspendida (401 Unauthorized)
Si el administrador suspende a un usuario (`isActive = false`), cualquier petición subsecuente al API le revocará forzadamente su token arrojando:
```json
{
    "message": "Cuenta suspendida."
}
```

---

## 4. Endpoints Adicionales Compartidos
Ambos ecosistemas (Patients y Users) comparten las rutas de cierre de sesión y actualización de Token bajo sus respectivos prefijos `v1/auth/patients/` o `v1/auth/users/`.
* `POST /logout`: Invalida el token actual en el servidor.
* `POST /refresh`: Genera y retorna un nuevo JWT extendiendo la vida útil de la sesión sin pedir credenciales.
