<?php

namespace App\Enums;

enum DocType: string
{
    case CERTIFICATE = 'CERTIFICATE';
    case REFERRAL = 'REFERRAL';
    case REPORT = 'REPORT';
}
