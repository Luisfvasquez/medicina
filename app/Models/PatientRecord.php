<?php

namespace App\Models;

use App\Traits\HasPublicUuid;
use App\Traits\SyncsPatientDataBindings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientRecord extends Model
{
    use HasPublicUuid, SoftDeletes, SyncsPatientDataBindings;

    protected $fillable = [
        'uuid',
        'patient_id',
        'user_id',
        'clinic_id',
        'form_template_id',
        'form_schema_snapshot',
        'dynamic_data',
    ];

    protected function casts(): array
    {
        return [
            'form_schema_snapshot' => 'array',
            'dynamic_data' => 'array',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->comment('Doctor/Personal creador o solicitante');
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function formTemplate()
    {
        return $this->belongsTo(FormTemplate::class);
    }
}
