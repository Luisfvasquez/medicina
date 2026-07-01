<?php

namespace App\Services\Otp\Channels;

use Illuminate\Support\Facades\Log;

/**
 * Email OTP delivery channel (mock).
 *
 * Currently logs the code to Log. Replace with real mailer (Laravel Mail,
 * SendGrid, SES) for production.
 */
class EmailChannel implements OtpChannelInterface
{
    public function send(string $email, string $code): void
    {
        // TODO: integrate with Laravel Mail, SendGrid, SES, etc.
        // Example: Mail::to($email)->send(new OtpMail($code));

        Log::info("[OTP Email] Código: $code → $email");
    }
}
