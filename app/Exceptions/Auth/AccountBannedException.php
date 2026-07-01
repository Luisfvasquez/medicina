<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ApiException;

class AccountBannedException extends ApiException
{
    public function __construct(
        ?string $detail = null,
        ?string $instance = null,
    ) {
        parent::__construct(
            type:    'https://api.pharmako.com/errors/account-banned',
            title:   'Cuenta Baneada',
            status:  403,
            detail:  $detail ?? 'Su cuenta ha sido baneada.',
            instance: $instance,
        );
    }
}
