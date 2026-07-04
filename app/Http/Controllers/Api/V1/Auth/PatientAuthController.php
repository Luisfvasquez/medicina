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
        $patient = auth('patient_api')->user();

        return response()->json([
            'user' => $this->authResponse->patientPayload($patient),
        ]);
    }

    public function updateProfile(\Illuminate\Http\Request $request): JsonResponse
    {
        $patient = auth('patient_api')->user();

        $validated = $request->validate([
            'fullName' => 'sometimes|string|max:255',
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:patient_accounts,email,' . $patient->id,
            'phone' => 'sometimes|string|unique:patient_accounts,phone,' . $patient->id,
            'nationalId' => 'sometimes|string|nullable|unique:patient_accounts,national_id,' . $patient->id,
            'national_id' => 'sometimes|string|nullable|unique:patient_accounts,national_id,' . $patient->id,
            'username' => 'sometimes|string|nullable|unique:patient_accounts,username,' . $patient->id,
            'cityId' => 'sometimes|string|nullable',
            'city_id' => 'sometimes|string|nullable',
            'avatarUrl' => 'sometimes|string|nullable',
            'avatar_url' => 'sometimes|string|nullable',
            
            // Datos clínicos
            'address' => 'sometimes|string|nullable',
            'birthDate' => 'sometimes|date|nullable',
            'birth_date' => 'sometimes|date|nullable',
            'gender' => 'sometimes|string|nullable',
            'bloodType' => 'sometimes|string|nullable',
            'blood_type' => 'sometimes|string|nullable',
            'allergies' => 'sometimes|string|nullable',
            'chronicConditions' => 'sometimes|string|nullable',
            'chronic_conditions' => 'sometimes|string|nullable',
            'emergencyContactName' => 'sometimes|string|nullable',
            'emergency_contact_name' => 'sometimes|string|nullable',
            'emergencyContactPhone' => 'sometimes|string|nullable',
            'emergency_contact_phone' => 'sometimes|string|nullable',
        ]);

        $accountData = [];
        if ($request->has('fullName') || $request->has('full_name')) {
            $accountData['full_name'] = $request->fullName ?? $request->full_name;
        }
        if ($request->has('email')) {
            $accountData['email'] = $request->email;
        }
        if ($request->has('phone')) {
            $accountData['phone'] = $request->phone;
        }
        if ($request->has('nationalId') || $request->has('national_id')) {
            $accountData['national_id'] = $request->nationalId ?? $request->national_id;
        }
        if ($request->has('username')) {
            $accountData['username'] = $request->username;
        }
        if ($request->has('cityId') || $request->has('city_id')) {
            $cityUuid = $request->cityId ?? $request->city_id;
            if ($cityUuid) {
                $city = \App\Models\City::where('uuid', $cityUuid)->first();
                $accountData['city_id'] = $city?->id;
            } else {
                $accountData['city_id'] = null;
            }
        }
        if ($request->has('avatarUrl') || $request->has('avatar_url')) {
            $accountData['avatar_url'] = $request->avatarUrl ?? $request->avatar_url;
        }

        $patient->update($accountData);

        $patient->loadMissing('patient');
        $profile = $patient->patient;

        if ($profile) {
            $profileData = [];
            if ($request->has('fullName') || $request->has('full_name')) {
                $nameParts = explode(' ', $request->fullName ?? $request->full_name);
                $profileData['first_name'] = $nameParts[0] ?? '';
                $profileData['last_name'] = implode(' ', array_slice($nameParts, 1)) ?: '';
            }
            if ($request->has('email')) {
                $profileData['email'] = $request->email;
            }
            if ($request->has('phone')) {
                $profileData['phone'] = $request->phone;
            }
            if ($request->has('nationalId') || $request->has('national_id')) {
                $profileData['national_id'] = $request->nationalId ?? $request->national_id;
            }
            if ($request->has('address')) {
                $profileData['address'] = $request->address;
            }
            if ($request->has('birthDate') || $request->has('birth_date')) {
                $profileData['birth_date'] = $request->birthDate ?? $request->birth_date;
            }
            if ($request->has('gender')) {
                $profileData['gender'] = $request->gender;
            }
            if ($request->has('bloodType') || $request->has('blood_type')) {
                $profileData['blood_type'] = $request->bloodType ?? $request->blood_type;
            }
            if ($request->has('allergies')) {
                $profileData['allergies'] = $request->allergies;
            }
            if ($request->has('chronicConditions') || $request->has('chronic_conditions')) {
                $profileData['chronic_conditions'] = $request->chronicConditions ?? $request->chronic_conditions;
            }
            if ($request->has('emergencyContactName') || $request->has('emergency_contact_name')) {
                $profileData['emergency_contact_name'] = $request->emergencyContactName ?? $request->emergency_contact_name;
            }
            if ($request->has('emergencyContactPhone') || $request->has('emergency_contact_phone')) {
                $profileData['emergency_contact_phone'] = $request->emergencyContactPhone ?? $request->emergency_contact_phone;
            }

            if (!empty($profileData)) {
                $profile->update($profileData);
            }
        }

        return response()->json([
            'status' => 'success',
            'user' => $this->authResponse->patientPayload($patient->fresh()),
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
