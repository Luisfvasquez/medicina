<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabRequest extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $appends = ['consultation_uuid'];

    protected $fillable = [
        'uuid',
        'user_id',
        'patient_id',
        'consultation_id',
        'exams_list',
        'instructions',
        'is_completed',
    ];

    public function getConsultationUuidAttribute()
    {
        return $this->consultation?->uuid;
    }

    protected function casts(): array
    {
        return [
            'exams_list' => 'array',
            'is_completed' => 'boolean',
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

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}
