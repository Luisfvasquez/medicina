# SDD Proposal — Auth Contract Alignment

**Change**: auth-contract-alignment
**Date**: 2026-06-30
**Status**: draft

## 1. Context & Problem

El backend de LUCA Health OS (Laravel 13.8) tiene un sistema de autenticación JWT que no cumple con el contrato definido en `api-contract-auth.md`. El contrato especifica:

- Login multicanal (OTP por WhatsApp, OTP por email, contraseña)
- Errores formateados como RFC 7807 Problem Details
- camelCase en todas las claves JSON
- Auth via cookies HttpOnly (no Bearer header)
- Rutas planas bajo `/api/v1/auth/`

La implementación actual usa:

- Solo login por contraseña en `/api/v1/auth/users/login`
- Respuestas con snake_case (`full_name`, `uuid`)
- Errores planos `{"error": "..."}`
- JWT Bearer token en header
- Rutas anidadas (`/auth/users/`, `/auth/patients/`)

**¿Por qué?** El backend se desarrolló primero con una arquitectura JWT standalone. El contrato con el frontend Next.js se definió después y exige un contrato diferente.

## 2. Proposal

Reescribir la capa de autenticación del backend para cumplir el contrato API. El trabajo abarca:

1. **Nuevo endpoint `POST /api/v1/auth/send-otp`** — Genera código OTP de 6 dígitos, lo persiste en DB con expiry, y lo "envía" al canal indicado (WhatsApp simulado → log, Email → log). Requiere crear el modelo `OtpCode`.

2. **Nuevo endpoint `POST /api/v1/auth/verify-otp`** — Valida el código OTP contra la DB. Si es válido, crea la sesión y devuelve el usuario + cookie HttpOnly. Requiere `OtpCode` y `OtpAttempt` (rate limiting).

3. **Refactorizar `POST /api/v1/auth/login-password`** — Cambiar respuesta a formato contrato, usar cookie HttpOnly en vez de body, migrar las rutas existentes `/auth/users/login` y `/auth/patients/login` a `/auth/login-password`.

4. **Refactorizar `POST /api/v1/auth/logout`** — Limpiar cookie HttpOnly + invalidar token del lado servidor.

5. **Middleware de errores RFC 7807** — Exception handler global que intercepta `AuthenticationException`, `ValidationException`, y excepciones custom, y las formatea como Problem Details JSON.

6. **Respuestas con camelCase** — Todos los controllers de auth y pacientes deben devolver `fullName`, `id`, `phone`, `role` como string (no enum). Para el paciente, `id` en vez de `uuid`.

7. **Refactorizar `/me` endpoint** — Respetar el shape del contrato para `/auth/me`.

## 3. Decisions & Constraints

- **Auth mechanism**: Cookies HttpOnly con CSRF protection. El token JWT se almacena en cookie segura del lado cliente. Se usa `sanctum` o cookie manual.
- **OTP mock**: El "envío" a WhatsApp/Email se loguea en `Log::info()` con el código. En producción se reemplaza con adapter real (Vonage/Twilio). El código queda guardado en DB para verificación.
- **OTP expiry**: Configurable en `config/otp.php`. Default 180s.
- **Rate limiting OTP**: Máximo 3 intentos fallidos por `phone`/`email` en 15 minutos. Lockout después de 3 intentos. Modelo `OtpAttempt`.
- **Migración de rutas**: Los paths existentes (`/auth/users/login`, `/auth/patients/login`) se deprecan y se mantiene temporalmente para backwards compat hasta que el frontend migre.
- **Clean Code**: Excepciones custom en `App\Exceptions\Auth\`, servicios en `App\Services\Auth\`, transformers en `App\Http\Resources\Auth\`.

## 4. Scope Boundaries

### In Scope

- Auth controllers (UserAuthController, PatientAuthController, nuevo OtpController)
- Middleware de errores RFC 7807
- Modelo OtpCode + OtpAttempt
- Configuración OTP
- Migraciones de DB
- Tests de Feature para auth completo
- Documentación en `doc-integrations/`

### Out of Scope (NO se hace en este change)

- Integración real con Twilio/Vonage/WhatsApp API
- Sistema de forgot password / reset password
- Refresh token con cookie (el logout limpia la cookie; el refresh se maneja via el endpoint existente)
- Middleware CSRF (se configura Sanctum/Laravel padrão CSRF, no se implementa validación de tokens CSRF en API stateless para mobile porque CORS lo maneja)
- Refactor de otros controllers (los otros 44 controladores)
- Cambios en el sync endpoint
- Cambios en el schema de User/PatientAccount

## 5. Risks

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Cookies HttpOnly rompen el sync offline | Baja | Alta | El sync endpoint usa Bearer token en header, no cookies. Se mantiene Bearer para sync. |
| Backwards compat se rompe para clientes existentes | Media | Media | Se mantienen rutas antiguas como deprecated temporarily. Se avisa en headers `Warning`. |
| OTP por email necesita SMTP real | Baja | Baja | Mock loguea. Configurar .env para prod después. |
| Rate limiting por IP se puede evadir | Baja | Baja | Se usa phone/email como key además de IP. |

## 6. Success Criteria

1. `POST /api/v1/auth/send-otp` genera código, lo persiste con expiry, y responde 200 con `{"status":"success","message":"...","otpExpirySeconds":180}`.
2. `POST /api/v1/auth/verify-otp` valida código y responde con usuario + cookie HttpOnly + body con shape del contrato.
3. `POST /api/v1/auth/login-password` responde con usuario + cookie HttpOnly + body con shape del contrato.
4. `POST /api/v1/auth/logout` limpia la cookie + responde `{"status":"success","message":"..."}`.
5. Todos los errores 4xx/5xx de auth retornan RFC 7807 con `type`, `title`, `status`, `detail`, `instance`.
6. Respuestas de auth usan camelCase: `fullName`, `phone`, `role` como string.
7. Máximo 3 intentos de OTP fallidos por phone/email en 15 min.
8. Tests de Feature pasan para: send-otp, verify-otp, login-password, logout, rate-limiting.
9. Documentación completa en `doc-integrations/api-auth.md`.
