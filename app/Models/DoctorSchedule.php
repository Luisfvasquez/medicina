<?php

namespace App\Models;

use App\Enums\Weekday;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'clinic_branch_id',
        'weekday',
        'start_time',
        'end_time',
        'appointment_duration',
        'max_per_slot',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => Weekday::class,
            'appointment_duration' => 'integer',
            'max_per_slot' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ClinicBranch::class, 'clinic_branch_id');
    }

    public function getSlotsCountAttribute(): int
    {
        $startMinutes = (int) substr($this->start_time, 0, 2) * 60 + (int) substr($this->start_time, 3, 2);
        $endMinutes = (int) substr($this->end_time, 0, 2) * 60 + (int) substr($this->end_time, 3, 2);

        return (int) floor(($endMinutes - $startMinutes) / $this->appointment_duration);
    }
}
