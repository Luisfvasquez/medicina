<?php

namespace App\Enums;

enum RxStatus: string
{
    case ACTIVE = 'ACTIVE';
    case CANCELLED = 'CANCELLED';
    case EXPIRED = 'EXPIRED';
}
