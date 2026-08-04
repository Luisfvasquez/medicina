<?php

namespace App\Models;

use App\Enums\ConsultationStatus;
use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasPublicUuid, \Illuminate\Database\Eloquent\SoftDeletes, \App\Traits\SyncsPatientDataBindings;

    protected $fillable = [
        'uuid',
        'user_id',
        'patient_id',
        'patient_account_id',
        'appointment_id',
        'clinic_branch_id',
        'form_template_id',
        'form_schema_snapshot',
        'date',
        'status',
        'reason',
        'physical_exam',
        'diagnosis',
        'treatment_plan',
        'dynamic_data',
        'services_performed',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'status' => ConsultationStatus::class,
            'dynamic_data' => 'array',
            'services_performed' => 'array',
            'form_schema_snapshot' => 'array',
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

    public function labRequests()
    {
        return $this->hasMany(LabRequest::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        if (!empty($this->form_schema_snapshot)) {
            if (empty($array['form_template'])) {
                $array['form_template'] = [
                    'uuid' => null,
                    'name' => 'Plantilla Histórica',
                    'schema_json' => $this->form_schema_snapshot,
                ];
            } else {
                $array['form_template']['schema_json'] = $this->form_schema_snapshot;
            }
        }
        
        return $array;
    }


}
