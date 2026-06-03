<?php

namespace App\Enums;

enum ClinicRole: string
{
    case OWNER = 'OWNER';
    case ADMIN = 'ADMIN';
    case DOCTOR = 'DOCTOR';
    case RECEPTIONIST = 'RECEPTIONIST';
}
