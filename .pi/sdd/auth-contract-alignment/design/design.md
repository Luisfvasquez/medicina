
# SDD Design — Auth Contract Alignment

**Change**: auth-contract-alignment
**Date**: 2026-06-30
**Status**: draft

---

## 1. Arquitectura de Archivos

```
app/
├── Exceptions/
│   ├── ApiException.php                    # Base RFC 7807
│   └── Auth/
│       ├── OtpRateLimitedException.php
│       ├── OtpExpiredException.php
│       ├── OtpInvalidException.php
│       ├── OtpNotFoundException.php
│       ├── InvalidCredentialsException.php
│       ├── AccountSuspendedException.php
│       └── AccountBannedException.php
├── Http/
│   ├── Controllers/Api/V1/Auth/
│   │   ├── AuthController.php             # login-password, logout, me
│   │   ├── OtpController.php              # send-otp, verify-otp
│   │   ├── UserAuthController.php        # refactorizado
│   │   └── PatientAuthController.php     # refactorizado
│   ├── Middleware/
│   │   └── DeprecatedRoute.php
│   └── Resources/Auth/
│       ├── UserResource.php
│       └── PatientResource.php
├── Models/
│   ├── OtpCode.php
│   └── OtpAttempt.php
└── Services/
    ├── Auth/
    │   ├── OtpService.php
    │   └── AuthResponseService.php
    └── Otp/
        └── Channels/
            ├── OtpChannelInterface.php
            ├── WhatsAppChannel.php
            └── EmailChannel.php

config/
└── otp.php

database/migrations/
└── xxxx_create_otp_tables.php

routes/
└── api.php (modificado)

tests/Feature/Api/V1/Auth/
├── OtpFlowTest.php
├── LoginPasswordTest.php
└── AuthContractTest.php
```

---

## 2. Detalle de Clases

### 2.1 `ApiException` (base)

```php
// app/Exceptions/ApiException.php
abstract class ApiException extends \Exception
{
    public function __construct(
        protected string $type,
        protected string $title,
        protected int $status,
        protected ?string $detail = null,
        protected ?string $instance = null,
        protected ?array $invalidParams = null,
    ) {
        parent::__construct($title);
    }

    public function render(): JsonResponse
    {
        $payload = [
            'type'     => $this->type,
            'title'    => $this->title,
            'status'   => $this->status,
            'detail'   => $this->detail ?? $this->getMessage(),
            'instance' => $this->instance ?? ('/' . request()->path()),
        ];

        if ($this->invalidParams) {
            $payload['invalidParams'] = $this->invalidParams;
        }

        return response()->json($payload, $this->status);
    }
}
```

### 2.2 `OtpCode` Model

```php
// app/Models/OtpCode.php
class OtpCode extends Model
{
    use HasFactory, HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'identifier', 'channel', 'role',
        'code_hash', 'expires_at', 'verified_at',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'verified_at'  => 'datetime',
        'channel'      => ChannelEnum::class,
        'role'         => UserRole::class,
    ];

    // relations
    public function userable(): MorphTo
    {
        return $this->morphTo();
    }

    // query scopes
    public function scopeActive($q)
    {
        return $q->whereNull('verified_at')
                 ->where('expires_at', '>', now());
    }

    public function scopeForIdentifier($q, string $identifier, string $role)
    {
        return $q->where('identifier', $identifier)
                 ->where('role', $role);
    }

    // helpers
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function verify(string $plainCode): bool
    {
        return hash_equals($this->code_hash, hash('sha256', $plainCode));
    }

    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }

    public function getPublicCode(): string
    {
        // Retorna los últimos 2 dígitos para mostrar al usuario
        return substr($this->code_hash, -2);
    }
}
```

### 2.3 `OtpAttempt` Model

```php
// app/Models/OtpAttempt.php
class OtpAttempt extends Model
{
    use HasFactory;

    protected $fillable = ['identifier', 'role', 'attempts', 'locked_until'];

    protected $casts = [
        'locked_until' => 'datetime',
        'role'        => UserRole::class,
    ];

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function recordFailure(): void
    {
        $max   = config('otp.max_attempts', 3);
        $winSec = config('otp.attempt_window_seconds', 900);
        $lockSec = config('otp.lockout_seconds', 1800);

        $this->attempts = $this->attempts + 1;

        if ($this->attempts >= $max) {
            $this->locked_until = now()->addSeconds($lockSec);
        }

        $this->save();
    }

    public function recordSuccess(): void
    {
        $this->update(['attempts' => 0, 'locked_until' => null]);
    }

    public function scopeForIdentifier($q, string $identifier, string $role)
    {
        return $q->where('identifier', $identifier)
                 ->where('role', $role);
    }
}
```

### 2.4 `OtpService`

```php
// app/Services/Auth/OtpService.php
class OtpService
{
    public function __construct(
        private OtpCode $otpCodeModel,
        private OtpAttempt $otpAttemptModel,
    ) {}

    public function send(string $identifier, string $channel, string $role): array
    {
        $this->checkRateLimit($identifier, $role);

        // Invalidar códigos anteriores activos
        $this->otpCodeModel
            ->forIdentifier($identifier, $role)
            ->active()
            ->update(['verified_at' => now()]);

        // Generar código
        $plainCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash  = hash('sha256', $plainCode);
        $expirySec = config('otp.expiry_seconds', 180);

        $otp = $this->otpCodeModel->create([
            'identifier'  => $identifier,
            'channel'    => $channel,
            'role'       => $role,
            'code_hash'  => $codeHash,
            'expires_at' => now()->addSeconds($expirySec),
        ]);

        // Enviar por canal
        $channelClass = config("otp.channels.{$channel}");
        app($channelClass)->send($identifier, $plainCode);

        return [
            'sent'           => true,
            'otpExpirySeconds' => $expirySec,
        ];
    }

    public function verify(string $identifier, string $plainCode, string $role): OtpCode
    {
        $otp = $this->otpCodeModel
            ->forIdentifier($identifier, $role)
            ->active()
            ->latest()
            ->first();

        if (!$otp) {
            throw new OtpNotFoundException(
                'https://api.pharmako.com/errors/not-found',
                'Código No Encontrado',
                404,
                'No se encontró un código OTP activo para este identificador.'
            );
        }

        if ($otp->isExpired()) {
            throw new OtpExpiredException(
                'https://api.pharmako.com/errors/otp-expired',
                'Código Vencido',
                401,
                'El código ingresado ha expirado. Solicite uno nuevo.'
            );
        }

        if (!$otp->verify($plainCode)) {
            $this->recordFailure($identifier, $role);
            throw new OtpInvalidException(
                'https://api.pharmako.com/errors/otp-invalid',
                'Código Inválido',
                401,
                'El código ingresado es incorrecto.'
            );
        }

        $otp->markAsVerified();
        $this->recordSuccess($identifier, $role);

        return $otp;
    }

    private function checkRateLimit(string $identifier, string $role): void
    {
        $attempt = $this->otpAttemptModel
            ->forIdentifier($identifier, $role)
            ->first();

        if ($attempt && $attempt->isLocked()) {
            throw new OtpRateLimitedException(
                'https://api.pharmako.com/errors/rate-limited',
                'Demasiados Intentos',
                429,
                'Demasiados intentos fallidos. Intente de nuevo en ' .
                    $attempt->locked_until->diffInMinutes(now()) . ' minutos.'
            );
        }
    }

    private function recordFailure(string $identifier, string $role): void
    {
        $attempt = $this->otpAttemptModel
            ->forIdentifier($identifier, $role)
            ->firstOrCreate(
                ['identifier' => $identifier, 'role' => $role],
                ['attempts' => 0, 'locked_until' => null]
            );
        $attempt->recordFailure();
    }

    private function recordSuccess(string $identifier, string $role): void
    {
        $attempt = $this->otpAttemptModel
            ->forIdentifier($identifier, $role)
            ->first();

        if ($attempt) {
            $attempt->recordSuccess();
        }
    }
}
```

### 2.5 `AuthResponseService`

```php
// app/Services/Auth/AuthResponseService.php
class AuthResponseService
{
    public function userPayload(User $user): array
    {
        return [
            'id'       => $user->uuid,
            'fullName' => $user->full_name,
            'email'    => $user->email,
            'phone'    => $user->phone,
            'role'     => $user->role->value,
        ];
    }

    public function patientPayload(PatientAccount $patient): array
    {
        return [
            'id'       => $patient->uuid,
            'fullName' => $patient->full_name,
            'email'    => $patient->email,
            'phone'    => $patient->phone,
        ];
    }

    public function authCookie(string $token): Cookie
    {
        $ttl = (int) config('jwt.ttl', 60);

        return cookie(
            'auth_token',
            $token,
            $ttl,
            '/',
            null,
            app()->isProduction(),
            true,   // HttpOnly
            false,
            'Strict'
        );
    }

    public function clearCookie(): Cookie
    {
        return cookie(
            'auth_token',
            '',
            0,
            '/',
            null,
            app()->isProduction(),
            true,
            false,
            'Strict'
        );
    }
}
```

### 2.6 Canales OTP

```php
// app/Services/Otp/Channels/OtpChannelInterface.php
interface OtpChannelInterface
{
    public function send(string $identifier, string $code): void;
}

// app/Services/Otp/Channels/WhatsAppChannel.php
class WhatsAppChannel implements OtpChannelInterface
{
    public function send(string $phone, string $code): void
    {
        // TODO: integrar con WhatsApp Business API (Vonage/Twilio)
        // Por ahora: mock
        Log::info("[OTP WhatsApp] Código: $code → $phone");
    }
}

// app/Services/Otp/Channels/EmailChannel.php
class EmailChannel implements OtpChannelInterface
{
    public function send(string $email, string $code): void
    {
        // TODO: integrar con SMTP real
        // Por ahora: mock
        Log::info("[OTP Email] Código: $code → $email");
    }
}
```

### 2.7 `AuthController`

```php
// app/Http/Controllers/Api/V1/Auth/AuthController.php
class AuthController extends Controller
{
    public function __construct(
        private AuthResponseService $authResponse,
    ) {}

    public function loginPassword(LoginPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            throw new InvalidCredentialsException(
                'https://api.pharmako.com/errors/unauthorized',
                'Credenciales Incorrectas',
                401,
                'El correo o la contraseña son incorrectos.'
            );
        }

        $this->checkAccountStatus($user);

        $token = auth('user_api')->login($user);

        return response()->json([
            'user' => $this->authResponse->userPayload($user),
        ], 200)->withCookie($this->authResponse->authCookie($token));
    }

    public function logout(): JsonResponse
    {
        auth('user_api')->logout();

        return response()->json([
            'status'  => 'success',
            'message' => 'Sesión cerrada correctamente.',
        ], 200)->withCookie($this->authResponse->clearCookie());
    }

    public function me(): JsonResponse
    {
        $user = auth('user_api')->user();

        return response()->json([
            'user' => $this->authResponse->userPayload($user),
        ]);
    }

    private function checkAccountStatus(User $user): void
    {
        match ($user->status) {
            AccountStatus::SUSPENDED => throw new AccountSuspendedException(
                'https://api.pharmako.com/errors/account-suspended',
                'Cuenta Suspendida',
                403,
                'Su cuenta ha sido suspendida. Contacte al administrador.'
            ),
            AccountStatus::BANNED => throw new AccountBannedException(
                'https://api.pharmako.com/errors/account-banned',
                'Cuenta Baneada',
                403,
                'Su cuenta ha sido baneada.'
            ),
            default => null,
        };
    }
}
```

### 2.8 `OtpController`

```php
// app/Http/Controllers/Api/V1/Auth/OtpController.php
class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private AuthResponseService $authResponse,
    ) {}

    public function send(SendOtpRequest $request): JsonResponse
    {
        $identifier = $request->filled('phone')
            ? $request->phone
            : $request->email;

        $result = $this->otpService->send(
            $identifier,
            $request->channel,
            $request->role,
        );

        return response()->json([
            'status'          => 'success',
            'message'         => 'Código de verificación enviado con éxito.',
            'otpExpirySeconds' => $result['otpExpirySeconds'],
        ]);
    }

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $identifier = $request->filled('phone')
            ? $request->phone
            : $request->email;

        $otp = $this->otpService->verify(
            $identifier,
            $request->code,
            $request->role ?? $otp->role->value,
        );

        // Resolver el usuario basado en el rol del OTP
        $user = $this->resolveUser($otp);

        $token = auth('user_api')->login($user);

        return response()->json([
            'user' => $this->authResponse->userPayload($user),
        ], 200)->withCookie($this->authResponse->authCookie($token));
    }

    private function resolveUser(OtpCode $otp): Model
    {
        return match ($otp->role->value) {
            'PATIENT' => PatientAccount::where('phone', $otp->identifier)
                                       ->orWhere('email', $otp->identifier)
                                       ->firstOrFail(),
            default   => User::where('phone', $otp->identifier)
                              ->orWhere('email', $otp->identifier)
                              ->firstOrFail(),
        };
    }
}
```

---

## 3. Handler Global de Errores

### 3.1 Registro en `bootstrap/app.php` (Laravel 11)

```php
// bootstrap/app.php
use App\Exceptions\Handler;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withExceptions(function (ExceptionHandler $exceptions) {

        // Excepciones custom → RFC 7807
        $exceptions->render(function (\App\Exceptions\ApiException $e) {
            return $e->render();
        });

        // AuthenticationException → 401 RFC 7807
        $exceptions->render(function (AuthenticationException $e) {
            return response()->json([
                'type'     => 'https://api.pharmako.com/errors/unauthorized',
                'title'    => 'No Autenticado',
                'status'   => 401,
                'detail'   => 'Debe iniciar sesión para acceder a este recurso.',
                'instance' => '/' . request()->path(),
            ], 401);
        });

        // ValidationException → 422 RFC 7807 con invalidParams
        $exceptions->render(function (ValidationException $e) {
            $params = collect($e->errors())->map(fn ($msgs, $field) => [
                'name'   => $field,
                'reason' => $msgs[0],
            ])->values()->all();

            return response()->json([
                'type'          => 'https://api.pharmako.com/errors/validation',
                'title'         => 'Error de Validación',
                'status'        => 422,
                'detail'        => 'Uno o más campos enviados no cumplen con las reglas requeridas.',
                'instance'      => '/' . request()->path(),
                'invalidParams' => $params,
            ], 422);
        });

        // ModelNotFoundException → 404
        $exceptions->render(function (ModelNotFoundException $e) {
            return response()->json([
                'type'     => 'https://api.pharmako.com/errors/not-found',
                'title'    => 'Recurso No Encontrado',
                'status'   => 404,
                'detail'   => 'El recurso solicitado no existe.',
                'instance' => '/' . request()->path(),
            ], 404);
        });

        // AccessDeniedHttpException → 403
        $exceptions->render(function (AccessDeniedHttpException $e) {
            return response()->json([
                'type'     => 'https://api.pharmako.com/errors/forbidden',
                'title'    => 'Acceso Denegado',
                'status'   => 403,
                'detail'   => 'No tiene permisos para acceder a este recurso.',
                'instance' => '/' . request()->path(),
            ], 403);
        });

    })->create();
```

---

## 4. Middleware Deprecated

```php
// app/Http/Middleware/DeprecatedRoute.php
class DeprecatedRoute
{
    public function handle(Request $request, \Closure $next, string $suggestion)
    {
        $response = $next($request);

        $response->headers->set(
            'Warning',
            '299 - "Deprecated: ' . $suggestion . '"'
        );

        return $response;
    }
}
```

Registro en `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'deprecated' => \App\Http\Middleware\DeprecatedRoute::class,
    ]);
})
```

---

## 5. Requests de Validación

### `SendOtpRequest`

- `phone`: required_without:email|regex:^\+[1-9]\d{7,14}$
- `email`: required_without:phone|email
- `role`: required|in:DOCTOR,PATIENT,PROVIDER,ADMIN
- `channel`: required|in:WHATSAPP,EMAIL
- *Mutual exclusion*: phone si channel=WHATSAPP, email si channel=EMAIL

### `VerifyOtpRequest`

- `phone`: required_without:email|regex:^\+[1-9]\d{7,14}
- `email`: required_without:phone|email
- `code`: required|digits:6

### `LoginPasswordRequest`

- `email`: required|email
- `password`: required|string|min:6

---

## 6. Migración de Base de Datos

```php
// database/migrations/xxxx_create_otp_tables.php
public function up(): void
{
    // otp_codes
    Schema::create('otp_codes', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->string('identifier'); // phone o email
        $table->enum('channel', ['WHATSAPP','EMAIL']);
        $table->enum('role', ['DOCTOR','PATIENT','PROVIDER','ADMIN']);
        $table->string('code_hash'); // SHA256
        $table->timestamp('expires_at');
        $table->timestamp('verified_at')->nullable();
        $table->timestamps();
        $table->softDeletes();

        $table->index(['identifier', 'role']);
        $table->index(['expires_at']);
    });

    // otp_attempts
    Schema::create('otp_attempts', function (Blueprint $table) {
        $table->id();
        $table->string('identifier');
        $table->enum('role', ['DOCTOR','PATIENT','PROVIDER','ADMIN']);
        $table->unsignedTinyInteger('attempts')->default(0);
        $table->timestamp('locked_until')->nullable();
        $table->timestamps();

        $table->unique(['identifier', 'role']);
    });
}
```

---

## 7. Modificación de Rutas

```php
// routes/api.php — grupo v1/auth

Route::prefix('v1/auth')->group(function () {

    // === NUEVAS RUTAS PLANAS (CONTRATO) ===
    Route::post('send-otp',       [OtpController::class, 'send']);
    Route::post('verify-otp',     [OtpController::class, 'verify']);
    Route::post('login-password', [AuthController::class, 'loginPassword']);

    Route::middleware('auth:user_api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
    });

    // === BACKWARDS COMPAT — deprecated ===
    Route::prefix('users')->group(function () {
        Route::post('login', [UserAuthController::class, 'login'])
            ->middleware('deprecated:Use POST /api/v1/auth/login-password');

        Route::middleware('auth:user_api')->group(function () {
            Route::post('logout',  [UserAuthController::class, 'logout']);
            Route::post('refresh', [UserAuthController::class, 'refresh']);
            Route::get('me',       [UserAuthController::class, 'me']);
        });
    });

    Route::prefix('patients')->group(function () {
        Route::post('register', [PatientAuthController::class, 'register']);
        Route::post('login',    [PatientAuthController::class, 'login'])
            ->middleware('deprecated:Use POST /api/v1/auth/login-password');

        Route::middleware('auth:patient_api')->group(function () {
            Route::post('logout',  [PatientAuthController::class, 'logout']);
            Route::post('refresh', [PatientAuthController::class, 'refresh']);
            Route::get('me',       [PatientAuthController::class, 'me']);
        });
    });
});
```

---

## 8. Refactor de Controladores Existentes

### `UserAuthController` — cambios

1. `respondWithToken()` → usar `AuthResponseService->userPayload()`
2. `logout()` → usar `AuthResponseService->clearCookie()`
3. Login fallido → throw `InvalidCredentialsException`
4. Agregar check de `AccountStatus` antes de login

### `PatientAuthController` — cambios

1. `respondWithToken()` → usar `AuthResponseService->patientPayload()`
2. `logout()` → usar `AuthResponseService->clearCookie()`
3. Login fallido → throw `InvalidCredentialsException`
4. Cookie HttpOnly en todas las respuestas de auth
