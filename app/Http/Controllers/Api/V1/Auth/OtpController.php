<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
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
     */
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
            'status'           => 'success',
            'message'          => 'Código de verificación enviado con éxito.',
            'otpExpirySeconds' => $result['otpExpirySeconds'],
        ]);
    }

    /**
     * POST /api/v1/auth/verify-otp
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $identifier = $request->filled('phone')
            ? $request->phone
            : $request->email;

        $role = $request->role ?? 'DOCTOR';

        $otp = $this->otpService->verify($identifier, $request->code, $role);

        // Resolve the user based on OTP role
        $user = $this->resolveUser($otp);

        // Resolve role as string — cast may not apply when model is hydrated manually
        $roleValue = $otp->role instanceof \BackedEnum
            ? $otp->role->value
            : (string) $otp->role;

        // Choose the appropriate guard based on role
        $guard = $roleValue === 'PATIENT' ? 'patient_api' : 'user_api';
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        // Build the appropriate payload
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
        ], 200)->withCookie($this->authResponse->authCookie($token));
    }

    private function resolveUser(\App\Models\OtpCode $otp): User|PatientAccount
    {
        $roleValue = $otp->role instanceof \BackedEnum
            ? $otp->role->value
            : (string) $otp->role;

        return match ($roleValue) {
            'PATIENT' => PatientAccount::where('phone', $otp->identifier)
                                      ->orWhere('email', $otp->identifier)
                                      ->firstOrFail(),
            default   => User::where('phone', $otp->identifier)
                             ->orWhere('email', $otp->identifier)
                             ->firstOrFail(),
        };
    }
}
