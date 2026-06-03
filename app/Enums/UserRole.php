<?php

namespace App\Enums;

enum UserRole: string
{
    case DOCTOR = 'DOCTOR';
    case PROVIDER = 'PROVIDER';
    case ADMIN = 'ADMIN';
}
