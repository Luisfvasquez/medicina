<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class OtpInvalidException extends ApiException
{
    public function __construct(
        ?string $detail = null,
        ?string $instance = null,
    ) {
        parent::__construct(
            type:    'https://api.pharmako.com/errors/otp-invalid',
            title:   'Código Inválido',
            status:  401,
            detail:  $detail ?? 'El código ingresado es incorrecto.',
            instance: $instance,
        );
    }
}
