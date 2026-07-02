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

        return [
            'id'         => $user->uuid,
            'fullName'   => $user->full_name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'role'       => $user->role->value,
            'isVerified' => $isVerified,
        ];
    }

    /**
     * @return array{id: string, fullName: string, email: ?string, phone: ?string}
     */
    public function patientPayload(PatientAccount $patient): array
    {
        return [
            'id'       => $patient->uuid,
            'fullName' => $patient->full_name,
            'email'    => $patient->email,
            'phone'    => $patient->phone,
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
