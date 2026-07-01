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
            'user' => $this->authResponse->userPayload($user),
        ])->withCookie($this->authResponse->authCookie($token));
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(): JsonResponse
    {
        auth('user_api')->logout();

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
        // Guard auto-parses cookie on first access
        $user = auth('user_api')->user();

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
