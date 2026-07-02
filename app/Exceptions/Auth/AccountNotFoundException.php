<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class AccountNotFoundException extends ApiException
{
    public function __construct(
        string $identifier,
        ?string $instance = null,
    ) {
        parent::__construct(
            type:    'https://api.pharmako.com/errors/not-found',
            title:   'Cuenta No Registrada',
            status:  404,
            detail:  'No existe una cuenta asociada a este identificador. Verificá el número o registrate.',
            instance: $instance,
        );
    }
}
