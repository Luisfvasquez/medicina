<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\OtpExpiredException;
use App\Exceptions\Auth\OtpInvalidException;
use App\Exceptions\Auth\OtpNotFoundException;
use App\Exceptions\Auth\OtpRateLimitedException;
use App\Exceptions\Auth\OtpAlreadyVerifiedException;
use App\Models\OtpAttempt;
use App\Models\OtpCode;

/**
 * Orchestrates OTP generation, delivery, and verification.
 */
class OtpService
{
    public function __construct(
        private OtpCode $otpCodeModel,
        private OtpAttempt $otpAttemptModel,
    ) {}

    /**
     * Generate and send an OTP code to the given identifier.
     *
     * @throws OtpRateLimitedException
     */
    public function send(string $identifier, string $channel, string $role): array
    {
        $this->checkRateLimit($identifier, $role);

        // Invalidate any previously active OTP for this identifier+role
        $this->otpCodeModel
            ->forIdentifier($identifier, $role)
            ->active()
            ->update(['verified_at' => now()]);

        // Generate code with configurable length
        $length = (int) config('otp.code_length', 6);
        $maxRandom = (int) str_repeat('9', $length);
        $plainCode = str_pad((string) random_int(0, $maxRandom), $length, '0', STR_PAD_LEFT);
        $codeHash  = hash('sha256', $plainCode);
        $expirySec = (int) config('otp.expiry_seconds', 180);

        // Persist
        $this->otpCodeModel->create([
            'identifier' => $identifier,
            'channel'   => $channel,
            'role'      => $role,
            'code_hash' => $codeHash,
            'expires_at' => now()->addSeconds($expirySec),
        ]);

        // Dispatch channel
        $channelClass = config("otp.channels." . strtolower($channel));
        app($channelClass)->send($identifier, $plainCode);

        return [
            'sent'            => true,
            'otpExpirySeconds' => $expirySec,
        ];
    }

    /**
     * Verify an OTP code and return the OtpCode record if valid.
     *
     * @throws OtpNotFoundException
     * @throws OtpExpiredException
     * @throws OtpInvalidException
     */
    public function verify(string $identifier, string $plainCode, string $role): OtpCode
    {
        // Buscar OTP activo (sin verificar) primero
        $otp = $this->otpCodeModel
            ->forIdentifier($identifier, $role)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (!$otp) {
            // No hay OTP activo — ver si existe uno ya verificado (para dar mensaje específico)
            $verifiedOtp = $this->otpCodeModel
                ->forIdentifier($identifier, $role)
                ->whereNotNull('verified_at')
                ->latest()
                ->first();

            if ($verifiedOtp) {
                throw new OtpAlreadyVerifiedException();
            }

            throw new OtpNotFoundException();
        }

        if ($otp->isExpired()) {
            throw new OtpExpiredException();
        }

        if (!$otp->verify($plainCode)) {
            $this->recordFailure($identifier, $role);
            throw new OtpInvalidException();
        }

        $otp->markAsVerified();
        $this->recordSuccess($identifier, $role);

        return $otp;
    }

    // ─── Private helpers ───────────────────────────────────────────────────

    private function checkRateLimit(string $identifier, string $role): void
    {
        $attempt = $this->otpAttemptModel
            ->forIdentifier($identifier, $role)
            ->first();

        if ($attempt && $attempt->isLocked()) {
            $minutes = (int) $attempt->locked_until->diffInMinutes(now());
            throw new OtpRateLimitedException(
                "Demasiados intentos fallidos. Intente de nuevo en {$minutes} minutos."
            );
        }
    }

    private function recordFailure(string $identifier, string $role): void
    {
        $attempt = $this->otpAttemptModel->firstOrCreate(
            ['identifier' => $identifier, 'role' => $role],
            ['attempts' => 0, 'locked_until' => null],
        );

        $attempt->recordFailure();
    }

    private function recordSuccess(string $identifier, string $role): void
    {
        $attempt = $this->otpAttemptModel
            ->forIdentifier($identifier, $role)
            ->first();

        if ($attempt) {
            $attempt->recordSuccess();
        }
    }
}
