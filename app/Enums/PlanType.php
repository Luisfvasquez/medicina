<?php

namespace App\Enums;

enum PlanType: string
{
    case FREE = 'FREE';
    case PRO = 'PRO';
    case ENTERPRISE = 'ENTERPRISE';
}
