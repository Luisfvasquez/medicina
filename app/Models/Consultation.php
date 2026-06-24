<?php

namespace App\Models;

use App\Enums\ConsultationStatus;
use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasPublicUuid, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'patient_id',
        'patient_account_id',
        'appointment_id',
        'clinic_branch_id',
        'form_template_id',
        'date',
        'status',
        'reason',
        'physical_exam',
        'diagnosis',
        'treatment_plan',
        'dynamic_data',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'status' => ConsultationStatus::class,
            'dynamic_data' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function patientAccount()
    {
        return $this->belongsTo(PatientAccount::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function clinicBranch()
    {
        return $this->belongsTo(ClinicBranch::class);
    }

    public function formTemplate()
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function vitalSign()
    {
        return $this->hasOne(VitalSign::class);
    }

    public function labRequest()
    {
        return $this->hasOne(LabRequest::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }
}
