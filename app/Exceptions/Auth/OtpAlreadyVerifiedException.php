<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class OtpAlreadyVerifiedException extends ApiException
{
    public function __construct(
        ?string $instance = null,
    ) {
        parent::__construct(
            type:    'https://api.pharmako.com/errors/conflict',
            title:   'Código OTP Ya Utilizado',
            status:  409,
            detail:  'Este código de verificación ya fue utilizado. Solicitá uno nuevo.',
            instance: $instance,
        );
    }
}
