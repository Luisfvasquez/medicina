<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class OtpExpiredException extends ApiException
{
    public function __construct(
        ?string $detail = null,
        ?string $instance = null,
    ) {
        parent::__construct(
            type:    'https://api.pharmako.com/errors/otp-expired',
            title:   'Código Vencido',
            status:  401,
            detail:  $detail ?? 'El código ingresado ha expirado. Solicite uno nuevo.',
            instance: $instance,
        );
    }
}
