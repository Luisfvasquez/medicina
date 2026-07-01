<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\AccountStatus;
use App\Exceptions\Auth\AccountBannedException;
use App\Exceptions\Auth\AccountSuspendedException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DoctorRegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ProviderRegisterRequest;
use App\Models\City;
use App\Models\ProviderProfile;
use App\Models\Specialty;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Services\Auth\AuthResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserAuthController extends Controller
{
    public function __construct(
        private AuthResponseService $authResponse,
    ) {}

    public function registerDoctor(DoctorRegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Convert city_id (UUID) to city_id (BIGINT)
            $cityId = null;
            if ($request->city_id) {
                $city = City::where('uuid', $request->city_id)->first();
                $cityId = $city?->id;
            }

            // Convert specialty_ids to database BIGINT ids
            $specialtyIds = Specialty::whereIn('uuid', $request->specialty_ids)->pluck('id')->toArray();

            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'DOCTOR',
                'is_active' => true,
                'plan_type' => 'FREE',
                'city_id' => $cityId,
            ]);

            $user->specialties()->attach($specialtyIds);

            // Manejo de archivo omitido en esta fase inicial, se guardaría en storage real.
            $path = $request->file('medical_license')->store('licenses', 'local');

            VerificationDocument::create([
                'user_id' => $user->id,
                'type' => 'MEDICAL_LICENSE',
                'file_url' => $path,
                'status' => 'PENDING',
            ]);

            DB::commit();

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'accessToken'  => $token,
                'access_token' => $token,
                'tokenType'    => 'bearer',
                'token_type'   => 'bearer',
                'expiresIn'    => (int) config('jwt.ttl') * 60,
                'expires_in'   => (int) config('jwt.ttl') * 60,
                'user'         => $this->authResponse->userPayload($user),
            ])->withCookie($this->authResponse->authCookie($token));
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    public function registerProvider(ProviderRegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Convert city_id (UUID) to city_id (BIGINT)
            $cityId = null;
            if ($request->city_id) {
                $city = City::where('uuid', $request->city_id)->first();
                $cityId = $city?->id;
            }

            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'PROVIDER',
                'is_active' => true,
                'plan_type' => 'FREE',
                'city_id' => $cityId,
            ]);

            ProviderProfile::create([
                'user_id' => $user->id,
                'commercial_name' => $request->commercial_name,
                'type' => $request->provider_type,
                'rif' => $request->rif,
                'is_verified' => false,
            ]);

            $path = $request->file('business_document')->store('business_docs', 'local');

            VerificationDocument::create([
                'user_id' => $user->id,
                'type' => 'BUSINESS_REGISTRATION',
                'file_url' => $path,
                'status' => 'PENDING',
            ]);

            DB::commit();

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'accessToken'  => $token,
                'access_token' => $token,
                'tokenType'    => 'bearer',
                'token_type'   => 'bearer',
                'expiresIn'    => (int) config('jwt.ttl') * 60,
                'expires_in'   => (int) config('jwt.ttl') * 60,
                'user'         => $this->authResponse->userPayload($user),
            ])->withCookie($this->authResponse->authCookie($token));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            throw new InvalidCredentialsException();
        }

        $this->checkAccountStatus($user);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'user' => $this->authResponse->userPayload($user),
        ])->withCookie($this->authResponse->authCookie($token));
    }

    public function me(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        return response()->json([
            'user' => $this->authResponse->userPayload($user),
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
            'user' => $this->authResponse->userPayload(JWTAuth::setToken($token)->toUser()),
        ])->withCookie($this->authResponse->authCookie($token));
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
