<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\AccountStatus;
use App\Exceptions\Auth\AccountBannedException;
use App\Exceptions\Auth\AccountSuspendedException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginPasswordRequest;
use App\Models\User;
use App\Services\Auth\AuthResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private AuthResponseService $authResponse,
    ) {}

    /**
     * POST /api/v1/auth/login-password
     */
    public function loginPassword(LoginPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            throw new InvalidCredentialsException();
        }

        $this->checkAccountStatus($user);

        // Guard reads cookie first; login() returns JWT and sets user in guard
        $token = auth('user_api')->login($user);

        return response()->json([
            'accessToken'  => $token,
            'access_token' => $token,
            'tokenType'    => 'bearer',
            'token_type'   => 'bearer',
            'expiresIn'    => (int) config('jwt.ttl') * 60,
            'expires_in'   => (int) config('jwt.ttl') * 60,
            'user'         => $this->authResponse->userPayload($user),
        ])->withCookie($this->authResponse->authCookie($token));
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(): JsonResponse
    {
        if (auth('user_api')->check()) {
            auth('user_api')->logout();
        } elseif (auth('patient_api')->check()) {
            auth('patient_api')->logout();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Sesión cerrada correctamente.',
        ])->withCookie($this->authResponse->clearCookie());
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(): JsonResponse
    {
        $user = auth('user_api')->user() ?? auth('patient_api')->user();

        if ($user instanceof \App\Models\PatientAccount) {
            return response()->json([
                'user' => $this->authResponse->patientPayload($user),
            ]);
        }

        return response()->json([
            'user' => $this->authResponse->userPayload($user),
        ]);
    }

    private function checkAccountStatus(User $user): void
    {
        match ($user->status) {
            AccountStatus::SUSPENDED => throw new AccountSuspendedException(),
            AccountStatus::BANNED    => throw new AccountBannedException(),
            default => null,
        };
    }
}
