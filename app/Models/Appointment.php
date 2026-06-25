<?php

namespace App\Models;

use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'patient_id',
        'user_id',
        'clinic_branch_id',
        'date',
        'time',
        'slot_time',
        'type',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => \App\Enums\AppointmentStatus::class,
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clinicBranch()
    {
        return $this->belongsTo(ClinicBranch::class);
    }

    public function consultation()
    {
        return $this->hasOne(Consultation::class);
    }
}
