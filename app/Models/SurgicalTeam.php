<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurgicalTeam extends Model
{
    protected $fillable = [
        'surgical_operation_id',
        'clinic_staff_id',
        'role',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function operation(): BelongsTo
    {
        return $this->belongsTo(SurgicalOperation::class, 'surgical_operation_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(ClinicStaff::class, 'clinic_staff_id');
    }
}
