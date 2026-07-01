<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class AccountSuspendedException extends ApiException
{
    public function __construct(
        ?string $detail = null,
        ?string $instance = null,
    ) {
        parent::__construct(
            type:    'https://api.pharmako.com/errors/account-suspended',
            title:   'Cuenta Suspendida',
            status:  403,
            detail:  $detail ?? 'Su cuenta ha sido suspendida. Contacte al administrador.',
            instance: $instance,
        );
    }
}
