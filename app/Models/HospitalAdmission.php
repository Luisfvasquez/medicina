<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HospitalAdmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clinic_branch_id',
        'patient_id',
        'hospital_bed_id',
        'admitting_doctor_id',
        'admission_date',
        'discharge_date',
        'status',
        'reason_for_admission',
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ClinicBranch::class, 'clinic_branch_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(HospitalBed::class, 'hospital_bed_id');
    }

    public function admittingDoctor(): BelongsTo
    {
        return $this->belongsTo(ClinicStaff::class, 'admitting_doctor_id');
    }

    public function treatmentNotes(): HasMany
    {
        return $this->hasMany(InpatientTreatmentNote::class);
    }

    public function medicationSchedules(): HasMany
    {
        return $this->hasMany(InpatientMedicationSchedule::class);
    }

    public function serviceCharges(): HasMany
    {
        return $this->hasMany(ServiceCharge::class);
    }
}
