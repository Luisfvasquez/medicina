<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\AccountStatus;
use App\Exceptions\Auth\AccountBannedException;
use App\Exceptions\Auth\AccountSuspendedException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PatientRegisterRequest;
use App\Models\Patient;
use App\Models\PatientAccount;
use App\Services\Auth\AuthResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class PatientAuthController extends Controller
{
    public function __construct(
        private AuthResponseService $authResponse,
    ) {}

    /**
     * Register a Patient
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(PatientRegisterRequest $request): JsonResponse
    {
        $cityId = null;
        if ($request->cityId) {
            $city = \App\Models\City::where('uuid', $request->cityId)->first();
            $cityId = $city?->id;
        }

        $patientAccount = PatientAccount::create([
            'full_name' => $request->fullName,
            'email' => $request->email,
            'phone' => $request->phone,
            'national_id' => $request->nationalId,
            'username' => $request->username,
            'city_id' => $cityId,
            'password_hash' => $request->password ? Hash::make($request->password) : null,
        ]);

        // Link existing Patient records by email (family members created by doctor)
        $unlinkedPatients = Patient::where('email', $request->email)
            ->whereNull('patient_account_id')
            ->get();

        foreach ($unlinkedPatients as $patient) {
            $patient->patient_account_id = $patientAccount->id;
            $patient->save();
        }

        $token = JWTAuth::fromUser($patientAccount);

        return response()->json([
            'accessToken'  => $token,
            'access_token' => $token,
            'tokenType'    => 'bearer',
            'token_type'   => 'bearer',
            'expiresIn'    => (int) config('jwt.ttl') * 60,
            'expires_in'   => (int) config('jwt.ttl') * 60,
            'user'         => $this->authResponse->patientPayload($patientAccount),
        ])->withCookie($this->authResponse->authCookie($token));
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $patient = PatientAccount::where('email', $request->email)->first();

        if (!$patient || !Hash::check($request->password, $patient->password_hash)) {
            throw new InvalidCredentialsException();
        }

        $this->checkAccountStatus($patient);

        $token = JWTAuth::fromUser($patient);

        return response()->json([
            'accessToken'  => $token,
            'access_token' => $token,
            'tokenType'    => 'bearer',
            'token_type'   => 'bearer',
            'expiresIn'    => (int) config('jwt.ttl') * 60,
            'expires_in'   => (int) config('jwt.ttl') * 60,
            'user'         => $this->authResponse->patientPayload($patient),
        ])->withCookie($this->authResponse->authCookie($token));
    }

    public function me(): JsonResponse
    {
        $patient = JWTAuth::parseToken()->authenticate();

        return response()->json([
            'user' => $this->authResponse->patientPayload($patient),
        ]);
    }

    public function logout(): JsonResponse
    {
        JWTAuth::parseToken()->invalidate();

        return response()->json([
            'status'  => 'success',
            'message' => 'Sesión cerrada correctamente.',
        ])->withCookie($this->authResponse->clearCookie());
    }

    public function refresh(): JsonResponse
    {
        $token = JWTAuth::parseToken()->refresh();

        return response()->json([
            'accessToken'  => $token,
            'access_token' => $token,
            'tokenType'    => 'bearer',
            'token_type'   => 'bearer',
            'expiresIn'    => (int) config('jwt.ttl') * 60,
            'expires_in'   => (int) config('jwt.ttl') * 60,
            'user'         => $this->authResponse->patientPayload(JWTAuth::setToken($token)->toUser()),
        ])->withCookie($this->authResponse->authCookie($token));
    }

    private function checkAccountStatus(PatientAccount $patient): void
    {
        match ($patient->status) {
            AccountStatus::SUSPENDED => throw new AccountSuspendedException(
                detail: 'Su cuenta ha sido suspendida. Contacte al administrador.'
            ),
            AccountStatus::BANNED => throw new AccountBannedException(
                detail: 'Su cuenta ha sido baneada.'
            ),
            default => null,
        };
    }
}
