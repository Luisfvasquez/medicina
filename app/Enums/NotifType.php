<?php

namespace App\Enums;

enum NotifType: string
{
    case SYSTEM = 'SYSTEM';
    case NEW_QUOTE_REQUEST = 'NEW_QUOTE_REQUEST';
    case QUOTE_RECEIVED = 'QUOTE_RECEIVED';
    case FOLLOW_UP_ALERT = 'FOLLOW_UP_ALERT';
}
