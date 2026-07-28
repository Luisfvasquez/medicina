<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InpatientMedicationSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hospital_admission_id',
        'medication_name',
        'dosage',
        'route',
        'scheduled_time',
        'administered_at',
        'administered_by',
        'status',
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
        'administered_at' => 'datetime',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(HospitalAdmission::class, 'hospital_admission_id');
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(ClinicStaff::class, 'administered_by');
    }
}
