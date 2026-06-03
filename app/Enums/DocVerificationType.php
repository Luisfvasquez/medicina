<?php

namespace App\Enums;

enum DocVerificationType: string
{
    case MEDICAL_LICENSE = 'MEDICAL_LICENSE';
    case NATIONAL_ID = 'NATIONAL_ID';
    case BUSINESS_RIF = 'BUSINESS_RIF';
    case COMMERCIAL_REGISTER = 'COMMERCIAL_REGISTER';
}
