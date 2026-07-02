<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Exceptions\Auth\AccountNotFoundException;
use App\Models\PatientAccount;
use App\Models\User;
use App\Services\Auth\AuthResponseService;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private AuthResponseService $authResponse,
    ) {}

    /**
     * POST /api/v1/auth/send-otp
     *
     * Si el cliente no envía `role`, lo auto-detectamos buscando el identificador
     * en patient_accounts (→ PATIENT) y luego en users (→ rol real del usuario).
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        $identifier = $request->filled('phone')
            ? $request->phone
            : $request->email;

        $role = $request->input('role') ?? $this->detectRole($identifier);

        $result = $this->otpService->send(
            $identifier,
            $request->channel,
            $role,
        );

        return response()->json([
            'status'           => 'success',
            'message'          => 'Código de verificación enviado con éxito.',
            'otpExpirySeconds' => $result['otpExpirySeconds'],
        ]);
    }

    /**
     * POST /api/v1/auth/verify-otp
     *
     * El rol se toma del OTP almacenado — no del cliente.
     * Esto garantiza que el usuario sólo puede verificar con el rol
     * que tenía cuando solicitó el código.
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $identifier = $request->filled('phone')
            ? $request->phone
            : $request->email;

        // Si el cliente envía role, lo usamos para buscar el OTP correcto.
        // De lo contrario, intentamos detectarlo automáticamente.
        $role = $request->input('role') ?? $this->detectRole($identifier);

        $otp = $this->otpService->verify($identifier, $request->code, $role);

        // Resolver el usuario desde la tabla correcta según el rol del OTP
        $user = $this->resolveUser($otp);

        // Extraer el valor string del rol
        $roleValue = $otp->role instanceof \BackedEnum
            ? $otp->role->value
            : (string) $otp->role;

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $payload = $roleValue === 'PATIENT'
            ? $this->authResponse->patientPayload($user)
            : $this->authResponse->userPayload($user);

        return response()->json([
            'accessToken'  => $token,
            'access_token' => $token,
            'tokenType'    => 'bearer',
            'token_type'   => 'bearer',
            'expiresIn'    => (int) config('jwt.ttl') * 60,
            'expires_in'   => (int) config('jwt.ttl') * 60,
            'user'         => $payload,
            // Devolvemos el rol real para que el frontend pueda ramificar sin selector
            'userType'     => $roleValue === 'PATIENT' ? 'patient' : 'user',
        ], 200)->withCookie($this->authResponse->authCookie($token));
    }

    /**
     * Auto-detecta el rol del identificador buscando en ambas tablas.
     * patient_accounts tiene prioridad sobre users.
     */
    private function detectRole(string $identifier): string
    {
        $isPatient = PatientAccount::where('phone', $identifier)
            ->orWhere('email', $identifier)
            ->exists();

        if ($isPatient) {
            return 'PATIENT';
        }

        $user = User::where('phone', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        return $user?->role ?? 'DOCTOR';
    }

    private function resolveUser(\App\Models\OtpCode $otp): User|PatientAccount
    {
        $roleValue = $otp->role instanceof \BackedEnum
            ? $otp->role->value
            : (string) $otp->role;

        $user = match ($roleValue) {
            'PATIENT' => PatientAccount::where('phone', $otp->identifier)
                                      ->orWhere('email', $otp->identifier)
                                      ->first(),
            default   => User::where('phone', $otp->identifier)
                             ->orWhere('email', $otp->identifier)
                             ->first(),
        };

        if (!$user) {
            throw new AccountNotFoundException($otp->identifier);
        }

        return $user;
    }
}
