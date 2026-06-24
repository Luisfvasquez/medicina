<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyHistory extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

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
