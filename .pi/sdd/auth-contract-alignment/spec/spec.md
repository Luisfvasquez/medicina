# SDD Spec — Auth Contract Alignment

**Change**: auth-contract-alignment
**Date**: 2026-06-30
**Status**: draft → ready

## 1. Configuración

**Archivo**: `config/otp.php`

```php
<?php
return [
    'code_length' => 6,
    'expiry_seconds' => env('OTP_EXPIRY_SECONDS', 180), // 3 min
    'max_attempts' => 3,
    'attempt_window_seconds' => 900, // 15 min
    'lockout_seconds' => 1800,      // 30 min after max attempts
    'channels' => [
        'whatsapp' => \App\Services\Otp\Channels\WhatsAppChannel::class,
        'email'    => \App\Services\Otp\Channels\EmailChannel::class,
    ],
];
```

---

## 2. Base de Datos

### 2.1 Migración: `create_otp_tables`

**Tabla `otp_codes`**

| Columna | Tipo | Nullable | Descripción |
|---------|------|----------|-------------|
| id | BIGINT UNSIGNED PK | No | Auto increment |
| uuid | UUID UNIQUE | No | Public ID |
| identifier | VARCHAR(255) | No | phone o email |
| channel | ENUM('WHATSAPP','EMAIL') | No | Canal usado |
| role | ENUM('DOCTOR','PATIENT','PROVIDER','ADMIN') | No | Rol del usuario |
| code_hash | VARCHAR(255) | No | Hash SHA256 del código |
| expires_at | TIMESTAMP | No | Cuando expira |
| verified_at | TIMESTAMP | NULL | Cuando se verificó |
| created_at | TIMESTAMP | No | |
| updated_at | TIMESTAMP | No | |

**Índices**: `(identifier, role)` — para lookup rápido.

**Tabla `otp_attempts`**

| Columna | Tipo | Nullable | Descripción |
|---------|------|----------|-------------|
| id | BIGINT UNSIGNED PK | No | |
| identifier | VARCHAR(255) | No | phone o email |
| role | ENUM(...) | No | |
| attempts | INT | No | Contador |
| locked_until | TIMESTAMP | NULL | Lockout hasta |
| updated_at | TIMESTAMP | No | |

**Índice único**: `(identifier, role)`.

### 2.2 Modelo `OtpCode`

```php
class OtpCode extends Model
{
    use HasPublicUuid;

    protected $fillable = ['identifier','channel','role','code_hash','expires_at','verified_at'];

    // relations
    public function user()      { /* belongsTo User por role */ }
    public function patient()    { /* belongsTo PatientAccount */ }

    // métodos
    public function isExpired(): bool
    public function isVerified(): bool
    public function verify(string $plainCode): bool  // hash compare
    public function markAsVerified(): void
}
```

### 2.3 Modelo `OtpAttempt`

```php
class OtpAttempt extends Model
{
    protected $fillable = ['identifier','role','attempts','locked_until'];

    // métodos
    public function isLocked(): bool
    public function recordFailure(): void   // incrementa attempts, setea lockout si pasa max
    public function recordSuccess(): void    // resetea attempts
}
```

---

## 3. Excepciones Custom

**Ubicación**: `app/Exceptions/Auth/`

```php
// Base
abstract class AuthException extends \App\Exceptions\ApiException {}

class OtpRateLimitedException extends AuthException {
    // HTTP 429, title: "Demasiados Intentos", detail: "... intente en X minutos"
}

class OtpExpiredException extends AuthException {
    // HTTP 401, title: "Código Vencido"
}

class OtpInvalidException extends AuthException {
    // HTTP 401, title: "Código Inválido", detail: "El código ingresado..."
}

class OtpNotFoundException extends AuthException {
    // HTTP 404, title: "Código No Encontrado"
}

class InvalidCredentialsException extends AuthException {
    // HTTP 401, title: "Credenciales Incorrectas"
}

class AccountSuspendedException extends AuthException {
    // HTTP 403, title: "Cuenta Suspendida"
}

class AccountBannedException extends AuthException {
    // HTTP 403, title: "Cuenta Baneada"
}
```

**ApiException base**: Implementa `render($request, Throwable $e)` que formatea a RFC 7807.

---

## 4. Handler Global de Errores

**Ubicación**: `bootstrap/app.php` (Laravel 11 style) o `app/Exceptions/Handler.php`

```php
// app/Exceptions/ApiException.php
abstract class ApiException extends \Exception
{
    protected string $type;        // URI del error
    protected string $title;      // Título corto
    protected int $status;        // HTTP status
    protected ?string $detail;    // Detalle
    protected ?string $instance;  // Path del request
    protected ?array $invalidParams; // Para 422

    public function render(): JsonResponse
    {
        return response()->json([
            'type'          => $this->type,
            'title'         => $this->title,
            'status'        => $this->status,
            'detail'        => $this->detail,
            'instance'      => $this->instance ?? request()->path(),
            'invalidParams' => $this->invalidParams,
        ], $this->status);
    }
}
```

**Errores default mapeados**:

- `AuthenticationException` → 401 con type `unauthorized`
- `ValidationException` → 422 con `invalidParams` extraídos de `$errors`
- `ModelNotFoundException` → 404
- `AuthorizationException` → 403
- `HttpException` genérico → su status code

---

## 5. Servicios

**Ubicación**: `app/Services/Auth/`

### 5.1 `OtpService`

```php
class OtpService
{
    public function __construct(
        private OtpCode $otpCode,
        private OtpAttempt $otpAttempt,
        private OtpChannelInterface $channel,
    ) {}

    public function send(string $identifier, string $channel, string $role): OtpResult
    {
        // 1. Verificar rate limit (OtpAttempt)
        // 2. Invalidar códigos anteriores para el mismo identifier+role
        // 3. Generar código de 6 dígitos
        // 4. Hash con SHA256, guardar en OtpCode con expires_at
        // 5. Enviar por canal (WhatsAppChannel o EmailChannel)
        //    - WhatsAppChannel: Log::info("[OTP WhatsApp] $code → $identifier")
        //    - EmailChannel: Log::info("[OTP Email] $code → $identifier")
        // 6. Retornar OtpResult { sent: true, expiresAt: ..., channel: ... }
    }

    public function verify(string $identifier, string $code, string $role): OtpVerificationResult
    {
        // 1. Buscar OtpCode activo (no verificado, no expirado)
        // 2. Si no existe: throw OtpNotFoundException
        // 3. Si expirado: throw OtpExpiredException
        // 4. Si código no coincide: throw OtpInvalidException + recordFailure en OtpAttempt
        // 5. Marcar OtpCode como verified
        // 6. recordSuccess en OtpAttempt
        // 7. Retornar result con user/patient entity
    }
}
```

### 5.2 `AuthResponseService`

```php
class AuthResponseService
{
    // Transforma User/PatientAccount → array del contrato
    public function userResponse(User $user): array
    {
        return [
            'id'       => $user->uuid,
            'fullName' => $user->full_name,
            'email'    => $user->email,
            'phone'    => $user->phone,
            'role'     => $user->role->value,
        ];
    }

    public function patientResponse(PatientAccount $patient): array
    {
        return [
            'id'       => $patient->uuid,
            'fullName' => $patient->full_name,
            'email'    => $patient->email,
            'phone'    => $patient->phone,
        ];
    }

    // Genera cookie HttpOnly con JWT
    public function makeCookie(string $token): Cookie
    {
        return cookie(
            name: 'auth_token',
            value: $token,
            minutes: config('jwt.ttl'), // TTL del JWT
            path: '/',
            httpOnly: true,
            secure: !app()->isLocal(),
            sameSite: 'strict',
        );
    }

    public function clearCookie(): Cookie
    {
        return cookie(
            name: 'auth_token',
            value: '',
            minutes: 0,
            httpOnly: true,
            secure: !app()->isLocal(),
            sameSite: 'strict',
            raw: false,
        );
    }
}
```

---

## 6. Controladores

### 6.1 `OtpController` (nuevo)

**Archivo**: `app/Http/Controllers/Api/V1/Auth/OtpController.php`

#### `POST /api/v1/auth/send-otp`

**Request**:

```json
// Opción A - WhatsApp
{ "phone": "+584121234567", "role": "DOCTOR", "channel": "WHATSAPP" }
// Opción B - Email
{ "email": "doctor@pharmako.com", "role": "DOCTOR", "channel": "EMAIL" }
```

**Validación**:

- `phone`: regex `^\+[1-9]\d{7,14}$` (E.164) — obligatorio si channel es WHATSAPP
- `email`: email válido — obligatorio si channel es EMAIL
- `role`: enum DOCTOR|PATIENT|PROVIDER|ADMIN
- `channel`: enum WHATSAPP|EMAIL

**Éxito 200**:

```json
{
  "status": "success",
  "message": "Código de verificación enviado con éxito.",
  "otpExpirySeconds": 180
}
```

**Error 422** (validación):

```json
{
  "type": "https://api.pharmako.com/errors/validation",
  "title": "Error de Validación",
  "status": 422,
  "detail": "Uno o más campos enviados no cumplen con las reglas requeridas.",
  "instance": "/api/v1/auth/send-otp",
  "invalidParams": [{ "name": "phone", "reason": "Formato E.164 requerido." }]
}
```

**Error 429** (rate limit):

```json
{
  "type": "https://api.pharmako.com/errors/rate-limited",
  "title": "Demasiados Intentos",
  "status": 429,
  "detail": "Demasiados intentos fallidos. Intente de nuevo en 15 minutos.",
  "instance": "/api/v1/auth/send-otp"
}
```

#### `POST /api/v1/auth/verify-otp`

**Request**:

```json
// WhatsApp
{ "phone": "+584121234567", "code": "843201" }
// Email
{ "email": "doctor@pharmako.com", "code": "843201" }
```

**Validación**:

- `phone`: regex E.164 — obligatorio si se usó WHATSAPP
- `email`: email — obligatorio si se usó EMAIL
- `code`: 6 dígitos exactos

**Éxito 200** (headers Set-Cookie + body):

```
Set-Cookie: auth_token=<jwt>; Path=/; HttpOnly; Secure; SameSite=Strict
```

```json
{
  "user": {
    "id": "uuid-del-usuario",
    "fullName": "Dr. Carlos San José",
    "email": "doctor@pharmako.com",
    "phone": "+584121234567",
    "role": "DOCTOR"
  }
}
```

**Error 404**: Código no encontrado o nunca generado.
**Error 401**: Código expirado o incorrecto.

---

### 6.2 `AuthController` (nuevo — fusiona login-password)

**Archivo**: `app/Http/Controllers/Api/V1/Auth/AuthController.php`

#### `POST /api/v1/auth/login-password`

**Request**:

```json
{
  "email": "doctor@pharmako.com",
  "password": "miSuperClave123!"
}
```

**Validación**: `email` required|email, `password` required|string|min:6.

**Éxito 200**:

```
Set-Cookie: auth_token=<jwt>; Path=/; HttpOnly; Secure; SameSite=Strict
```

```json
{
  "user": {
    "id": "uuid",
    "fullName": "Dr. Carlos San José",
    "email": "doctor@pharmako.com",
    "phone": "+584121234567",
    "role": "DOCTOR"
  }
}
```

**Error 401**:

```json
{
  "type": "https://api.pharmako.com/errors/unauthorized",
  "title": "Credenciales Incorrectas",
  "status": 401,
  "detail": "El correo o la contraseña son incorrectos.",
  "instance": "/api/v1/auth/login-password"
}
```

**Error 403** (suspendido/baneado): RFC 7807 con `AccountSuspendedException` o `AccountBannedException`.

#### `POST /api/v1/auth/logout`

**Request**: Body vacío.

**Éxito 200**:

```
Set-Cookie: auth_token=; Path=/; HttpOnly; Secure; SameSite=Strict; Expires=Thu, 01 Jan 1970 00:00:00 GMT
```

```json
{
  "status": "success",
  "message": "Sesión cerrada correctamente."
}
```

#### `GET /api/v1/auth/me`

**Éxito 200** (cookie auth):

```json
{
  "user": {
    "id": "uuid",
    "fullName": "Dr. Carlos San José",
    "email": "doctor@pharmako.com",
    "phone": "+584121234567",
    "role": "DOCTOR"
  }
}
```

**Error 401**: No autenticado.

---

## 7. Rutas

```php
// routes/api.php

Route::prefix('v1/auth')->group(function () {

    // Nuevas rutas planas (contrato)
    Route::post('send-otp',        [OtpController::class, 'send']);
    Route::post('verify-otp',      [OtpController::class, 'verify']);
    Route::post('login-password',  [AuthController::class, 'loginPassword']);
    Route::post('logout',         [AuthController::class, 'logout']);
    Route::get('me',               [AuthController::class, 'me']);

    // Backwards compat — rutas antiguas como deprecated
    Route::prefix('users')->group(function () {
        Route::post('login',  [UserAuthController::class, 'login'])
            ->middleware('deprecated:Use POST /api/v1/auth/login-password');
        // ... resto existente
    });

    Route::prefix('patients')->group(function () {
        Route::post('login', [PatientAuthController::class, 'login'])
            ->middleware('deprecated:Use POST /api/v1/auth/login-password');
        // ... resto existente
    });
});
```

**Middleware `deprecated`**: Agrega header `Warning: 299 - "Deprecated: ..."` a la respuesta.

---

## 8. Recursos API (Transformers)

**Ubicación**: `app/Http/Resources/Auth/`

```php
// UserResource.php
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'       => $this->uuid,
            'fullName' => $this->full_name,
            'email'    => $this->email,
            'phone'    => $this->phone,
            'role'     => $this->role->value,
        ];
    }
}

// PatientResource.php
class PatientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'       => $this->uuid,
            'fullName' => $this->full_name,
            'email'    => $this->email,
            'phone'    => $this->phone,
        ];
    }
}
```

---

## 9. Tests de Feature

**Ubicación**: `tests/Feature/Api/V1/Auth/`

### `tests/Feature/Api/V1/Auth/OtpFlowTest.php`

```php
test('send-otp-whatsapp genera codigo y responde 200')
test('send-otp-email genera codigo y responde 200')
test('send-otp-whatsapp validacion phone requerido')
test('send-otp-email validacion email requerido')
test('send-otp rate-limit bloquea despues de 3 intentos fallidos')
test('verify-otp codigo correcto responde 200 con cookie y user')
test('verify-otp codigo incorrecto responde 401')
test('verify-otp codigo expirado responde 401')
test('verify-otp sin codigo generado responde 404')
test('verify-otp limpia cookie auth_token al verificar')
```

### `tests/Feature/Api/V1/Auth/LoginPasswordTest.php`

```php
test('login-password credenciales correctas responde 200 con cookie y user')
test('login-password credenciales incorrectas responde 401 rfc7807')
test('login-password email no existe responde 401 rfc7807')
test('login-password cuenta suspendida responde 403 rfc7807')
test('logout responde 200 y limpia cookie')
```

### `tests/Feature/Api/V1/Auth/AuthContractTest.php`

```php
test('respuesta user tiene camelCase fullName')
test('respuesta user tiene id no uuid')
test('respuesta user tiene role como string no enum')
test('error 401 tiene formato rfc7807 completo')
test('error 422 tiene invalidParams')
test('error 422 invalidParams tiene name y reason')
test('deprecation warning en rutas antiguas')
```

---

## 10. Archivos a Crear/Modificar

### Nuevos archivos

| Archivo | Tipo |
|---------|------|
| `config/otp.php` | Config |
| `app/Exceptions/ApiException.php` | Exception base |
| `app/Exceptions/Auth/OtpRateLimitedException.php` | Exception |
| `app/Exceptions/Auth/OtpExpiredException.php` | Exception |
| `app/Exceptions/Auth/OtpInvalidException.php` | Exception |
| `app/Exceptions/Auth/OtpNotFoundException.php` | Exception |
| `app/Exceptions/Auth/InvalidCredentialsException.php` | Exception |
| `app/Exceptions/Auth/AccountSuspendedException.php` | Exception |
| `app/Exceptions/Auth/AccountBannedException.php` | Exception |
| `app/Models/OtpCode.php` | Model |
| `app/Models/OtpAttempt.php` | Model |
| `app/Services/Auth/OtpService.php` | Service |
| `app/Services/Auth/AuthResponseService.php` | Service |
| `app/Services/Otp/Channels/OtpChannelInterface.php` | Interface |
| `app/Services/Otp/Channels/WhatsAppChannel.php` | Channel |
| `app/Services/Otp/Channels/EmailChannel.php` | Channel |
| `app/Http/Controllers/Api/V1/Auth/OtpController.php` | Controller |
| `app/Http/Controllers/Api/V1/Auth/AuthController.php` | Controller |
| `app/Http/Resources/Auth/UserResource.php` | Resource |
| `app/Http/Resources/Auth/PatientResource.php` | Resource |
| `app/Http/Middleware/DeprecatedRoute.php` | Middleware |
| `database/migrations/xxxx_create_otp_tables.php` | Migration |
| `tests/Feature/Api/V1/Auth/OtpFlowTest.php` | Test |
| `tests/Feature/Api/V1/Auth/LoginPasswordTest.php` | Test |
| `tests/Feature/Api/V1/Auth/AuthContractTest.php` | Test |
| `doc-integrations/api-auth.md` | Documentación |

### Archivos a modificar

| Archivo | Cambio |
|---------|--------|
| `routes/api.php` | Agregar nuevas rutas planas, marcar antiguas como deprecated |
| `bootstrap/app.php` | Registrar handler de excepciones |
| `app/Http/Kernel.php` | Registrar middleware deprecated |
| `app/Http/Controllers/Api/V1/Auth/UserAuthController.php` | Usar AuthResponseService para respuestas camelCase |
| `app/Http/Controllers/Api/V1/Auth/PatientAuthController.php` | Usar AuthResponseService, cookie HttpOnly |
| `config/auth.php` | Configurar guards user_api y patient_api para usar cookies |

---

## 11. Secuencia de Implementación

1. **Infraestructura**: Migración, Models, Config
2. **Excepciones + Handler**: ApiException base + handlers RFC 7807
3. **Servicios**: OtpService, AuthResponseService, Channels
4. **Controladores**: OtpController, AuthController (login-password, logout, me)
5. **Refactor UserAuth/PatientAuth**: camelCase + cookie
6. **Rutas**: Nuevas rutas planas + deprecated en antiguas
7. **Tests**: Feature tests completos
8. **Documentación**: doc-integrations/api-auth.md
