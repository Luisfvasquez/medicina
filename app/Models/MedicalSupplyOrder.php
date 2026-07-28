<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalSupplyOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clinic_branch_id',
        'patient_id',
        'surgical_operation_id',
        'prescribing_doctor_id',
        'provider_profile_id',
        'supplies_list',
        'medical_house_name',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'supplies_list' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ClinicBranch::class, 'clinic_branch_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(SurgicalOperation::class, 'surgical_operation_id');
    }

    public function prescribingDoctor(): BelongsTo
    {
        return $this->belongsTo(ClinicStaff::class, 'prescribing_doctor_id');
    }
}
