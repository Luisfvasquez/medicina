<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InpatientTreatmentNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hospital_admission_id',
        'clinic_staff_id',
        'note',
        'type',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(HospitalAdmission::class, 'hospital_admission_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(ClinicStaff::class, 'clinic_staff_id');
    }
}
