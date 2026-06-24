<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'uuid',
        'prescription_id',
        'medication_id',
        'dose',
        'frequency',
        'duration',
        'quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }
}
