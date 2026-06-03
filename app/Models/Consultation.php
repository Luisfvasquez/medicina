<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use \App\Traits\HasPublicUuid, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'user_id',
        'patient_id',
        'clinic_id',
        'form_template_id',
        'date',
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

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
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
}
