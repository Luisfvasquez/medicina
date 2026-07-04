<?php

namespace App\Services\Auth;

use App\Models\PatientAccount;
use App\Models\User;

/**
 * Builds standardized auth responses and manages auth cookies.
 */
class AuthResponseService
{
    // ─── Payload builders (contract: camelCase) ────────────────────────────

    /**
     * @return array{id: string, fullName: string, email: string, phone: ?string, role: string, isVerified: bool}
     */
    public function userPayload(User $user): array
    {
        $isVerified = false;
        if ($user->role->value === 'ADMIN') {
            $isVerified = true;
        } elseif ($user->role->value === 'DOCTOR') {
            $isVerified = $user->verificationDocuments()
                ->where('type', 'MEDICAL_LICENSE')
                ->where('status', 'APPROVED')
                ->exists();
        } elseif ($user->role->value === 'PROVIDER') {
            $isVerified = $user->providerProfile ? (bool) $user->providerProfile->is_verified : false;
        }

        $user->loadMissing(['providerProfile', 'city']);

        return [
            'id'              => $user->uuid,
            'fullName'        => $user->full_name,
            'full_name'       => $user->full_name,
            'email'           => $user->email,
            'phone'           => $user->phone,
            'role'            => $user->role->value,
            'isVerified'      => $isVerified,
            'logoUrl'         => $user->logo_url,
            'logo_url'        => $user->logo_url,
            'signatureUrl'    => $user->signature_url,
            'signature_url'   => $user->signature_url,
            'cityId'          => $user->city?->uuid,
            'city_id'         => $user->city?->uuid,
            'status'          => $user->status?->value ?? $user->status,
            'planType'        => $user->plan_type?->value ?? $user->plan_type,
            'plan_type'       => $user->plan_type?->value ?? $user->plan_type,
            'providerProfile' => $user->providerProfile ? [
                'id' => $user->providerProfile->uuid ?? $user->providerProfile->id,
                'type' => $user->providerProfile->type,
                'commercial_name' => $user->providerProfile->commercial_name,
                'rif' => $user->providerProfile->rif,
                'is_verified' => (bool) $user->providerProfile->is_verified,
                'address' => $user->providerProfile->address,
                'phone' => $user->providerProfile->phone,
            ] : null,
            'provider_profile' => $user->providerProfile ? [
                'id' => $user->providerProfile->uuid ?? $user->providerProfile->id,
                'type' => $user->providerProfile->type,
                'commercial_name' => $user->providerProfile->commercial_name,
                'rif' => $user->providerProfile->rif,
                'is_verified' => (bool) $user->providerProfile->is_verified,
                'address' => $user->providerProfile->address,
                'phone' => $user->providerProfile->phone,
            ] : null,
        ];
    }

    /**
     * @return array{id: string, fullName: string, email: ?string, phone: ?string}
     */
    public function patientPayload(PatientAccount $patient): array
    {
        $patient->loadMissing(['patient', 'city']);
        $profile = $patient->patient;

        return [
            'id'                      => $patient->uuid,
            'fullName'                => $patient->full_name,
            'email'                   => $patient->email,
            'phone'                   => $patient->phone,
            'nationalId'              => $patient->national_id,
            'username'                => $patient->username,
            'cityId'                  => $patient->city?->uuid,
            'city_id'                 => $patient->city?->uuid,
            'avatarUrl'               => $patient->avatar_url,
            'isActive'                => $patient->is_active,
            'status'                  => $patient->status?->value ?? $patient->status,
            'role'                    => 'patient',
            
            // Clinical/profile details from associated Patient model if exists
            'address'                 => $profile?->address,
            'birthDate'               => $profile?->birth_date?->format('Y-m-d'),
            'gender'                  => $profile?->gender?->value ?? $profile?->gender,
            'bloodType'               => $profile?->blood_type,
            'allergies'               => $profile?->allergies,
            'chronicConditions'       => $profile?->chronic_conditions,
            'emergencyContactName'    => $profile?->emergency_contact_name,
            'emergencyContactPhone'   => $profile?->emergency_contact_phone,
        ];
    }

    // ─── Cookie management ──────────────────────────────────────────────────

    public function authCookie(string $token): \Illuminate\Cookie\CookieJar|\Symfony\Component\HttpFoundation\Cookie
    {
        $ttl = (int) (config('jwt.ttl') ?? 60) * 60; // minutes → seconds

        return cookie(
            'auth_token',
            $token,
            $ttl / 60, // back to minutes for Laravel cookie helper
            '/',
            null,
            app()->isProduction(),
            true,   // HttpOnly
            false,
            'Strict'
        );
    }

    public function clearCookie(): \Illuminate\Cookie\CookieJar|\Symfony\Component\HttpFoundation\Cookie
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
