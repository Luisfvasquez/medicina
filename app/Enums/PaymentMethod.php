<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'CASH';
    case CARD = 'CARD';
    case TRANSFER = 'TRANSFER';
    case INSURANCE = 'INSURANCE';
    case OTHER = 'OTHER';
}
