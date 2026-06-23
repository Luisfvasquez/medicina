<?php

namespace App\Enums;

enum AccountStatus: string
{
    case ACTIVE = 'ACTIVE';
    case WARNED = 'WARNED';
    case SUSPENDED = 'SUSPENDED';
    case BANNED = 'BANNED';
}