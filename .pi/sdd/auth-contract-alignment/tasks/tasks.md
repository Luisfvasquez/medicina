# SDD Tasks — Auth Contract Alignment

**Change**: auth-contract-alignment
**Date**: 2026-06-30
**Status**: draft

## Work Units

### WU-1: Infraestructura base — Config, Migración, Models

**Files**: 4 created, 0 modified
**Estimated lines**: ~180

- [ ] **T1.1** Crear `config/otp.php` con `code_length`, `expiry_seconds`, `max_attempts`, `attempt_window_seconds`, `lockout_seconds`, `channels`
- [ ] **T1.2** Crear `database/migrations/xxxx_create_otp_tables.php` — tabla `otp_codes` (uuid, identifier, channel, role, code_hash, expires_at, verified_at, softDeletes) + tabla `otp_attempts` (identifier, role, attempts, locked_until)
- [ ] **T1.3** Crear `app/Models/OtpCode.php` — HasPublicUuid, $fillable, casts (channel→enum, role→enum), scopes (active, forIdentifier), helpers (isExpired, isVerified, verify, markAsVerified)
- [ ] **T1.4** Crear `app/Models/OtpAttempt.php` — $fillable, casts, helpers (isLocked, recordFailure, recordSuccess, scope forIdentifier)

---

### WU-2: Excepciones custom + Handler global RFC 7807

**Files**: 9 created, 2 modified
**Estimated lines**: ~220

- [ ] **T2.1** Crear `app/Exceptions/ApiException.php` — abstract, constructor con type/title/status/detail/instance/invalidParams, método `render()` que retorna JsonResponse RFC 7807
- [ ] **T2.2** Crear `app/Exceptions/Auth/InvalidCredentialsException.php` — extiende ApiException, status 401
- [ ] **T2.3** Crear `app/Exceptions/Auth/AccountSuspendedException.php` — status 403
- [ ] **T2.4** Crear `app/Exceptions/Auth/AccountBannedException.php` — status 403
- [ ] **T2.5** Crear `app/Exceptions/Auth/OtpRateLimitedException.php` — status 429
- [ ] **T2.6** Crear `app/Exceptions/Auth/OtpExpiredException.php` — status 401
- [ ] **T2.7** Crear `app/Exceptions/Auth/OtpInvalidException.php` — status 401
- [ ] **T2.8** Crear `app/Exceptions/Auth/OtpNotFoundException.php` — status 404
- [ ] **T2.9** Modificar `bootstrap/app.php` — registrar handlers en `withExceptions`: ApiException→RFC7807, AuthenticationException→401, ValidationException→422 (con invalidParams), ModelNotFoundException→404, AccessDeniedHttpException→403

---

### WU-3: Servicios OTP + Auth

**Files**: 6 created
**Estimated lines**: ~280

- [ ] **T3.1** Crear `app/Services/Otp/Channels/OtpChannelInterface.php` — interface con método `send(string $identifier, string $code): void`
- [ ] **T3.2** Crear `app/Services/Otp/Channels/WhatsAppChannel.php` — implementa interface, log en Log::info() con código y phone (mock)
- [ ] **T3.3** Crear `app/Services/Otp/Channels/EmailChannel.php` — implementa interface, log en Log::info() con código y email (mock)
- [ ] **T3.4** Crear `app/Services/Auth/OtpService.php` — método `send(identifier, channel, role)` (rate limit check, invalidate old, generate code, hash, create OtpCode, dispatch channel) + método `verify(identifier, code, role)` (find active, check expiry, verify hash, mark verified, record success) + helpers privados
- [ ] **T3.5** Crear `app/Services/Auth/AuthResponseService.php` — `userPayload(User)` + `patientPayload(PatientAccount)` (camelCase shape) + `authCookie(string $token)` + `clearCookie()`

---

### WU-4: Requests de validación

**Files**: 3 created
**Estimated lines**: ~100

- [ ] **T4.1** Crear `app/Http/Requests/Auth/SendOtpRequest.php` — phone (required_without email, regex E.164), email (required_without phone, email), role (required|in:DOCTOR...), channel (required|in:WHATSAPP...), withValidator para mutual exclusion phone/email según canal
- [ ] **T4.2** Crear `app/Http/Requests/Auth/VerifyOtpRequest.php` — phone (required_without email, regex E.164), email (required_without phone, email), code (required|digits:6), role (optional|in:...)
- [ ] **T4.3** Crear `app/Http/Requests/Auth/LoginPasswordRequest.php` — email (required|email), password (required|string|min:6)

---

### WU-5: Controladores nuevos + Recursos

**Files**: 5 created, 2 modified
**Estimated lines**: ~320

- [ ] **T5.1** Crear `app/Http/Controllers/Api/V1/Auth/AuthController.php` — `loginPassword()` (validar credenciales, throw InvalidCredentialsException, checkAccountStatus, responder con cookie + userPayload) + `logout()` (logout + clearCookie) + `me()` (auth->user + userPayload)
- [ ] **T5.2** Crear `app/Http/Controllers/Api/V1/Auth/OtpController.php` — `send(SendOtpRequest)` (delegar a OtpService, responder 200) + `verify(VerifyOtpRequest)` (delegar a OtpService, resolver usuario por rol, login con guard correcto, responder con cookie + userPayload/patientPayload)
- [ ] **T5.3** Crear `app/Http/Resources/Auth/UserResource.php` — `toArray()` retorna id (uuid), fullName, email, phone, role (string)
- [ ] **T5.4** Crear `app/Http/Resources/Auth/PatientResource.php` — `toArray()` retorna id (uuid), fullName, email, phone
- [ ] **T5.5** Modificar `app/Http/Controllers/Api/V1/Auth/UserAuthController.php` — refactorizar `respondWithToken()` para usar `AuthResponseService->userPayload()` + `logout()` usa `clearCookie()` + login失败 lanza `InvalidCredentialsException` + agregar checkAccountStatus
- [ ] **T5.6** Modificar `app/Http/Controllers/Api/V1/Auth/PatientAuthController.php` — refactorizar `respondWithToken()` para usar `AuthResponseService->patientPayload()` + `logout()` usa `clearCookie()` + login失败 lanza `InvalidCredentialsException`

---

### WU-6: Middleware + Rutas

**Files**: 2 created, 1 modified
**Estimated lines**: ~100

- [ ] **T6.1** Crear `app/Http/Middleware/DeprecatedRoute.php` — recibe sugerencia, agrega header `Warning: 299 - "Deprecated: ..."`
- [ ] **T6.2** Registrar `deprecated` alias en `bootstrap/app.php` → `withMiddleware()`
- [ ] **T6.3** Modificar `routes/api.php` — agregar rutas planas (`send-otp`, `verify-otp`, `login-password`) en grupo `/v1/auth` + rutas deprecated en `/v1/auth/users` y `/v1/auth/patients`

---

### WU-7: Tests de Feature

**Files**: 3 created
**Estimated lines**: ~400

- [ ] **T7.1** Crear `tests/Feature/Api/V1/Auth/OtpFlowTest.php` — 10 tests: send-otp WhatsApp OK, send-otp email OK, validación phone requerido, validación email requerido, rate limit bloquea, verify-otp OK con cookie, verify-otp código incorrecto 401, verify-otp expirado 401, verify-otp sin código 404, verify-otp limpia cookie
- [ ] **T7.2** Crear `tests/Feature/Api/V1/Auth/LoginPasswordTest.php` — 5 tests: credenciales correctas 200 + cookie, credenciales incorrectas 401 RFC7807, usuario no existe 401 RFC7807, cuenta suspendida 403, logout 200 + cookie cleared
- [ ] **T7.3** Crear `tests/Feature/Api/V1/Auth/AuthContractTest.php` — 7 tests: user tiene camelCase fullName, user tiene id no uuid, user tiene role string, patient tiene camelCase, error 401 RFC7807 completo, error 422 invalidParams completo, deprecation warning en rutas antiguas

---

### WU-8: Documentación

**Files**: 1 created
**Estimated lines**: ~150

- [ ] **T8.1** Crear `doc-integrations/api-auth.md` — docs completas de la API de auth: flujos (OTP completo, password, logout), endpoints con request/response examples, códigos de error RFC 7807, headers (Set-Cookie), rate limits, notas de implementación

---

## Resumen de work units

| WU | Área | Files | Líneas est. |
|----|------|-------|-----------|
| WU-1 | Infraestructura | 4 | ~180 |
| WU-2 | Excepciones | 9 | ~220 |
| WU-3 | Servicios | 6 | ~280 |
| WU-4 | Requests | 3 | ~100 |
| WU-5 | Controladores | 5 created, 2 modified | ~320 |
| WU-6 | Routes + Middleware | 2 created, 1 modified | ~100 |
| WU-7 | Tests | 3 | ~400 |
| WU-8 | Docs | 1 | ~150 |
| **Total** | | **33 archivos** | **~1,750 líneas** |

---

## Review Workload Forecast

- **Archivos modificados**: 5 (`UserAuthController`, `PatientAuthController`, `api.php`, `bootstrap/app.php`)
- **Archivos creados**: 28
- **Total changed lines estimado**: ~1,750
- **Riesgo de review**: ⚠️ ALTO (supera 400 líneas)

> **Recomendación**: Este change supera las 400 líneas. Dado que la arquitectura está bien definida (spec + design), los archivos son nuevos en su mayoría, y los tests son scoped a auth, se recomienda hacer un solo PR con revisiones incrementales. Si preferís encadenar, se podría separar en dos: (A) Infraestructura + Excepciones + Servicios, (B) Controladores + Routes + Tests + Docs.
