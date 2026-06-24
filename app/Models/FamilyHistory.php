<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyHistory extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'uuid',
        'patient_id',
        'condition',
        'relationship',
        'note',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
