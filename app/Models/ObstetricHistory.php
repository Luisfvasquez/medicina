<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObstetricHistory extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'patient_id',
        'last_period_date',
        'pregnancies',
        'births',
        'cesareans',
        'abortions',
        'contraceptive_method',
    ];

    protected function casts(): array
    {
        return [
            'last_period_date' => 'datetime',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
