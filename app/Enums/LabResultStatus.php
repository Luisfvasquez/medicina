<?php

namespace App\Enums;

enum LabResultStatus: string
{
    case PENDING = 'PENDING';
    case COMPLETED = 'COMPLETED';
    case ABNORMAL = 'ABNORMAL';
    case CANCELLED = 'CANCELLED';
}
