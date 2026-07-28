<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clinic_branch_id',
        'patient_id',
        'clinic_service_id',
        'assigned_staff_id',
        'status',
        'notes',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ClinicBranch::class, 'clinic_branch_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ClinicService::class, 'clinic_service_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(ClinicStaff::class, 'assigned_staff_id');
    }
}
