<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class OtpNotFoundException extends ApiException
{
    public function __construct(
        ?string $detail = null,
        ?string $instance = null,
    ) {
        parent::__construct(
            type:    'https://api.pharmako.com/errors/not-found',
            title:   'Código No Encontrado',
            status:  404,
            detail:  $detail ?? 'No se encontró un código OTP activo para este identificador.',
            instance: $instance,
        );
    }
}
