<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lifestyle extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
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
