<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\DoctorRegisterRequest;
use App\Http\Requests\Auth\ProviderRegisterRequest;
use App\Models\User;
use App\Models\City;
use App\Models\Specialty;
use App\Models\ProviderProfile;
use App\Models\VerificationDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserAuthController extends Controller
{
    public function registerDoctor(DoctorRegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Convert city_uuid to city_id (BIGINT)
            $cityId = null;
            if ($request->city_uuid) {
                $city = City::where('uuid', $request->city_uuid)->first();
                $cityId = $city?->id;
            }

            // Convert specialty_uuids to specialty_ids (BIGINT array)
            $specialtyIds = Specialty::whereIn('uuid', $request->specialty_uuids)->pluck('id')->toArray();

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

            $token = auth('user_api')->login($user);
            return $this->respondWithToken($token);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    public function registerProvider(ProviderRegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'PROVIDER',
                'is_active' => true,
                'plan_type' => 'FREE',
                'city_id' => $request->city_id,
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

            $token = auth('user_api')->login($user);
            return $this->respondWithToken($token);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('user_api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me(): JsonResponse
    {
        $user = auth('user_api')->user()->load('providerProfile', 'city', 'specialties', 'clinicBranchMembers.branch');
        return response()->json($user);
    }

    public function logout(): JsonResponse
    {
        auth('user_api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('user_api')->refresh());
    }

    protected function respondWithToken($token): JsonResponse
    {
        $user = auth('user_api')->user();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('user_api')->factory()->getTTL() * 60,
            'user' => [
                'uuid' => $user->uuid,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
                'status' => $user->status->value,
                'is_verified' => $user->verificationDocuments()->where('status', 'APPROVED')->exists(),
                'pending_documents' => $user->verificationDocuments()->where('status', 'PENDING')->count(),
            ]
        ]);
    }
}
