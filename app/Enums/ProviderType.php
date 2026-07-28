<?php

namespace App\Enums;

enum ProviderType: string
{
    case PHARMACY = 'PHARMACY';
    case LABORATORY = 'LABORATORY';
    case MEDICAL_SUPPLY = 'MEDICAL_SUPPLY';
}
