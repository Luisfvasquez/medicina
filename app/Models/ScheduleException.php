<?php

namespace App\Models;

use App\Enums\ExceptionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleException extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'exception_date',
        'exception_type',
        'custom_start_time',
        'custom_end_time',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'exception_date' => 'date',
            'exception_type' => ExceptionType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isVacation(): bool
    {
        return $this->exception_type === ExceptionType::VACATION;
    }

    public function isDayOff(): bool
    {
        return $this->exception_type === ExceptionType::DAY_OFF;
    }

    public function isCustomHours(): bool
    {
        return $this->exception_type === ExceptionType::CUSTOM_HOURS;
    }
}
