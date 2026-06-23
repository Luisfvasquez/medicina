<?php

namespace App\Enums;

enum AuditAction: string
{
    case VIEW = 'VIEW';
    case CREATE = 'CREATE';
    case UPDATE = 'UPDATE';
    case DELETE = 'DELETE';
    case EXPORT = 'EXPORT';
    case PRINT = 'PRINT';
}
