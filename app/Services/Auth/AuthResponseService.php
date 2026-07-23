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

        $user->loadMissing(['providerProfile', 'city', 'verificationDocuments']);

        return [
            'id'              => $user->uuid,
            'fullName'        => $user->full_name,
            'email'           => $user->email,
            'phone'           => $user->phone,
            'nationalId'      => $user->national_id,
            'role'            => $user->role->value,
            'isVerified'      => $isVerified,
            'logoUrl'         => $user->logo_url,
            'signatureUrl'    => $user->signature_url,
            'cityId'          => $user->city?->uuid,
            'status'          => $user->status?->value ?? $user->status,
            'planType'        => $user->plan_type?->value ?? $user->plan_type,
            'providerProfile' => $user->providerProfile ? [
                'id'             => $user->providerProfile->uuid ?? $user->providerProfile->id,
                'type'           => $user->providerProfile->type,
                'commercialName' => $user->providerProfile->commercial_name,
                'rif'            => $user->providerProfile->rif,
                'isVerified'     => (bool) $user->providerProfile->is_verified,
                'address'        => $user->providerProfile->address,
                'phone'          => $user->providerProfile->phone,
            ] : null,
            'verificationDocuments' => $user->verificationDocuments ? $user->verificationDocuments->map(function ($doc) {
                return [
                    'id'        => $doc->id,
                    'uuid'      => $doc->uuid,
                    'type'      => $doc->type,
                    'status'    => $doc->status,
                    'fileUrl'   => $doc->file_url,
                    'comments'  => $doc->comments,
                    'createdAt' => $doc->created_at?->toIso8601String(),
                ];
            }) : [],
        ];
    }

    /**
     * Resolve and auto-link Patient clinical profile for a PatientAccount
     */
    public function resolveClinicalProfile(PatientAccount $patient): ?\App\Models\Patient
    {
        // 1. Direct relationship check
        $profile = \App\Models\Patient::where('patient_account_id', $patient->id)
            ->where(function ($q) {
                $q->whereNotNull('allergies')
                  ->orWhereNotNull('chronic_conditions')
                  ->orWhereNotNull('blood_type')
                  ->orWhereNotNull('address')
                  ->orWhereNotNull('birth_date');
            })
            ->latest('updated_at')
            ->first()
            ?? \App\Models\Patient::where('patient_account_id', $patient->id)->first();

        // 2. Fallback search by national_id, email, or phone
        if (!$profile) {
            $profile = \App\Models\Patient::where(function ($q) use ($patient) {
                if ($patient->national_id) {
                    $q->orWhere('national_id', $patient->national_id);
                }
                if ($patient->email) {
                    $q->orWhere('email', $patient->email);
                }
                if ($patient->phone) {
                    $q->orWhere('phone', $patient->phone);
                }
            })->latest('updated_at')->first();

            // Auto-link found profile to patient_account_id
            if ($profile && !$profile->patient_account_id) {
                $profile->patient_account_id = $patient->id;
                $profile->save();
            }
        }

        return $profile;
    }

    /**
     * @return array{id: string, fullName: string, email: ?string, phone: ?string}
     */
    public function patientPayload(PatientAccount $patient): array
    {
        $patient->loadMissing(['patient', 'city']);
        
        $profile = $this->resolveClinicalProfile($patient);

        // Fetch all matching patient records to coalesce clinical data if multiple records exist
        $allProfiles = \App\Models\Patient::where('patient_account_id', $patient->id)
            ->when($patient->national_id, fn($q) => $q->orWhere('national_id', $patient->national_id))
            ->when($patient->email, fn($q) => $q->orWhere('email', $patient->email))
            ->when($patient->phone, fn($q) => $q->orWhere('phone', $patient->phone))
            ->get();

        // Auto-link any unlinked matching profiles
        foreach ($allProfiles as $p) {
            if (!$p->patient_account_id) {
                $p->patient_account_id = $patient->id;
                $p->save();
            }
        }

        $address               = $profile?->address ?? $allProfiles->pluck('address')->filter()->first();
        $birthDate             = $profile?->birth_date ?? $allProfiles->pluck('birth_date')->filter()->first();
        $gender                = $profile?->gender ?? $allProfiles->pluck('gender')->filter()->first();
        $bloodType             = $profile?->blood_type ?? $allProfiles->pluck('blood_type')->filter()->first();
        $allergiesList         = $allProfiles->pluck('allergies')->filter()->unique()->values();
        $allergies             = $allergiesList->isNotEmpty() ? $allergiesList->implode(', ') : $profile?->allergies;
        $chronicList           = $allProfiles->pluck('chronic_conditions')->filter()->unique()->values();
        $chronicConditions     = $chronicList->isNotEmpty() ? $chronicList->implode(', ') : $profile?->chronic_conditions;
        $emergencyContactName  = $profile?->emergency_contact_name ?? $allProfiles->pluck('emergency_contact_name')->filter()->first();
        $emergencyContactPhone = $profile?->emergency_contact_phone ?? $allProfiles->pluck('emergency_contact_phone')->filter()->first();

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
            
            // Clinical/profile details from associated Patient model(s)
            'address'                 => $address,
            'birthDate'               => $birthDate instanceof \DateTimeInterface ? $birthDate->format('Y-m-d') : ($birthDate ? (string) $birthDate : null),
            'gender'                  => $gender instanceof \BackedEnum ? $gender->value : (string) $gender,
            'bloodType'               => $bloodType,
            'allergies'               => $allergies,
            'chronicConditions'       => $chronicConditions,
            'emergencyContactName'    => $emergencyContactName,
            'emergencyContactPhone'   => $emergencyContactPhone,
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
