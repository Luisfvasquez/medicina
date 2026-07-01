<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class InvalidCredentialsException extends ApiException
{
    public function __construct(
        ?string $detail = null,
        ?string $instance = null,
    ) {
        parent::__construct(
            type:    'https://api.pharmako.com/errors/unauthorized',
            title:   'Credenciales Incorrectas',
            status:  401,
            detail:  $detail ?? 'El correo o la contraseña son incorrectos.',
            instance: $instance,
        );
    }
}
