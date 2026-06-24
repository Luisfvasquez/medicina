<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalBackground extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'patient_id',
        'has_diabetes',
        'has_hypertension',
        'has_asthma',
        'other_conditions',
        'past_hospitalizations',
    ];

    protected function casts(): array
    {
        return [
            'has_diabetes' => 'boolean',
            'has_hypertension' => 'boolean',
            'has_asthma' => 'boolean',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
