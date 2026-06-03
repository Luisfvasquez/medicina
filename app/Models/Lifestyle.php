<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lifestyle extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'patient_id',
        'smoking_status',
        'alcohol_consumption',
        'activity_level',
        'diet_type',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
