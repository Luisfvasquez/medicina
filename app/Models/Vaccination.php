<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vaccination extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'uuid',
        'patient_id',
        'vaccine',
        'dose_number',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
