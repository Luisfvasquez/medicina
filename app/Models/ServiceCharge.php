<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceCharge extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hospital_admission_id',
        'patient_id',
        'clinic_service_id',
        'amount',
        'quantity',
        'charged_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(HospitalAdmission::class, 'hospital_admission_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ClinicService::class, 'clinic_service_id');
    }

    public function chargedBy(): BelongsTo
    {
        return $this->belongsTo(ClinicStaff::class, 'charged_by');
    }
}
