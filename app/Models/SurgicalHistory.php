<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurgicalHistory extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'patient_id',
        'procedure',
        'date',
        'hospital',
        'notes',
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
