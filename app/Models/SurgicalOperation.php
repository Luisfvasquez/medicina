<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurgicalOperation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clinic_branch_id',
        'patient_id',
        'clinic_service_id',
        'hospital_admission_id',
        'scheduled_at',
        'started_at',
        'completed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function admission(): BelongsTo
    {
        return $this->belongsTo(HospitalAdmission::class, 'hospital_admission_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(SurgicalTeam::class);
    }

    public function supplyOrders(): HasMany
    {
        return $this->hasMany(MedicalSupplyOrder::class);
    }
}
