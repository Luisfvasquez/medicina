<?php

namespace App\Enums;

enum FollowStatus: string
{
    case PENDING = 'PENDING';
    case SENT = 'SENT';
    case RESPONDED = 'RESPONDED';
}
