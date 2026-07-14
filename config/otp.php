<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el sistema de autenticación por código OTP
    | (WhatsApp / Email).
    |
    */

    'code_length' => env('OTP_CODE_LENGTH', 8),

    'expiry_seconds' => env('OTP_EXPIRY_SECONDS', 600), // 10 minutos

    'max_attempts' => env('OTP_MAX_ATTEMPTS', 3),

    'attempt_window_seconds' => env('OTP_ATTEMPT_WINDOW', 900), // 15 minutos

    'lockout_seconds' => env('OTP_LOCKOUT_SECONDS', 1800), // 30 minutos

    /*
    |--------------------------------------------------------------------------
    | Canales de envío
    |--------------------------------------------------------------------------
    |
    | Mapeo de canal → clase que implementa OtpChannelInterface.
    | Para producción, reemplazar WhatsAppChannel y EmailChannel por
    | adaptadores reales (Vonage, Twilio, SMTP real).
    |
    */

    'channels' => [
        'whatsapp' => App\Services\Otp\Channels\WhatsAppChannel::class,
        'email'    => App\Services\Otp\Channels\EmailChannel::class,
    ],
];
