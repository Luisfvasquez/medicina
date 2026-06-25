<?php

namespace App\Enums;

enum ExceptionType: string
{
    case VACATION = 'VACATION';
    case DAY_OFF = 'DAY_OFF';
    case CUSTOM_HOURS = 'CUSTOM_HOURS';

    public function label(): string
    {
        return match($this) {
            self::VACATION => 'Vacaciones',
            self::DAY_OFF => 'Día libre',
            self::CUSTOM_HOURS => 'Horario especial',
        };
    }
}
