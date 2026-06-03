<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
}
