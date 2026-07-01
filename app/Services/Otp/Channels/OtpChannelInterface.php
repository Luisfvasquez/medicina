<?php

namespace App\Services\Otp\Channels;

/**
 * Contract for OTP delivery channels.
 *
 * Implement this interface to add a new channel (SMS, Push, etc.).
 */
interface OtpChannelInterface
{
    /**
     * Send the OTP code to the given identifier.
     *
     * @param string $identifier Phone (E.164) or email address
     * @param string $code       Plain 6-digit OTP code
     */
    public function send(string $identifier, string $code): void;
}
