<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class OtpRateLimitedException extends ApiException
{
    public function __construct(
        ?string $detail = null,
        ?string $instance = null,
    ) {
        parent::__construct(
            type:    'https://api.pharmako.com/errors/rate-limited',
            title:   'Demasiados Intentos',
            status:  429,
            detail:  $detail ?? 'Demasiados intentos fallidos. Intente de nuevo en 15 minutos.',
            instance: $instance,
        );
    }
}
