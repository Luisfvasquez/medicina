<?php

namespace App\Services\Otp\Channels;

use Illuminate\Support\Facades\Log;

/**
 * WhatsApp OTP delivery channel (mock).
 *
 * Currently logs the code to Log. Replace with real WhatsApp Business API
 * integration (Vonage Messages API, Twilio WhatsApp, etc.) for production.
 */
class WhatsAppChannel implements OtpChannelInterface
{
    public function send(string $phone, string $code): void
    {
        // TODO: integrate with Vonage/Twilio WhatsApp Business API
        // Example: app(VonageClient::class)->messages()->send([
        //     'to'   => $phone,
        //     'from' => config('services.vonage.from'),
        //     'text' => "Tu código de verificación es: $code",
        // ]);

        Log::info("[OTP WhatsApp] Código: $code → $phone");
    }
}
