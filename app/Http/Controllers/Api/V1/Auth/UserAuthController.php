<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\AccountStatus;
use App\Enums\DocVerificationType;
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

            // Convert cityId (UUID) to city_id (BIGINT)
            $cityId = null;
            if ($request->cityId) {
                $city = City::where('uuid', $request->cityId)->first();
                $cityId = $city?->id;
            }


            $user = User::create([
                'full_name' => $request->fullName,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'DOCTOR',
                'is_active' => true,
                'plan_type' => 'FREE',
                'city_id' => $cityId,
            ]);

            $user->specialties()->attach($request->specialtyIds);

            // Auto-create personal clinic and main branch for independent practice
            $clinic = \App\Models\Clinic::create([
                'name' => 'Consultorio Privado de ' . $user->full_name,
                'rif' => null,
                'logo_url' => null,
                'website' => null,
            ]);

            $branch = \App\Models\ClinicBranch::create([
                'clinic_id' => $clinic->id,
                'name' => 'Consultorio Principal',
                'address' => 'Dirección a completar',
                'city_id' => $cityId ?? (\App\Models\City::first()?->id ?? 1),
                'phone' => $user->phone ?? '0000000000',
                'is_main_branch' => true,
                'google_maps_url' => null,
                'observations' => null,
            ]);

            \App\Models\ClinicBranchMember::create([
                'user_id' => $user->id,
                'clinic_branch_id' => $branch->id,
                'role' => \App\Enums\ClinicRole::OWNER,
                'is_active' => true,
            ]);

            // Manejo de archivo omitido en esta fase inicial, se guardaría en storage real.
            $path = $request->file('medicalLicense')->store('licenses', 'local');

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

            // Convert cityId (UUID) to city_id (BIGINT)
            $cityId = null;
            if ($request->cityId) {
                $city = City::where('uuid', $request->cityId)->first();
                $cityId = $city?->id;
            }

            $user = User::create([
                'full_name' => $request->fullName,
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
                'commercial_name' => $request->commercialName,
                'type' => $request->providerType,
                'rif' => $request->rif,
                'is_verified' => false,
            ]);

            $path = $request->file('businessDocument')->store('business_docs', 'local');

            // CLINIC / LABORATORY → COMMERCIAL_REGISTER, PHARMACY → BUSINESS_RIF
            $docType = in_array($request->providerType, ['CLINIC', 'LABORATORY'])
                ? DocVerificationType::COMMERCIAL_REGISTER
                : DocVerificationType::BUSINESS_RIF;

            VerificationDocument::create([
                'user_id' => $user->id,
                'type' => $docType,
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
        $user = JWTAuth::authenticate();

        return response()->json([
            'user' => $this->authResponse->userPayload($user),
        ]);
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(true);

        return response()->json([
            'status'  => 'success',
            'message' => 'Sesión cerrada correctamente.',
        ])->withCookie($this->authResponse->clearCookie());
    }

    public function refresh(): JsonResponse
    {
        $token = JWTAuth::refresh();

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
