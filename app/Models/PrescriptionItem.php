<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'prescription_id',
        'medication',
        'dosage',
        'frequency',
        'duration',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }
}
