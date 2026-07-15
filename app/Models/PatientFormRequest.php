<?php

namespace App\Models;

use App\Traits\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class PatientFormRequest extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'patient_id',
        'user_id',
        'clinic_id',
        'form_template_id',
        'status',
        'completed_at',
        'patient_record_id',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function formTemplate()
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function patientRecord()
    {
        return $this->belongsTo(PatientRecord::class);
    }
}
