<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasPublicUuid;

class TemplateItem extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'template_id',
        'medication_id',
        'dose',
        'frequency',
        'duration',
        'notes',
    ];

    protected $with = ['medication'];

    protected $appends = ['medication_uuid'];

    public function getMedicationUuidAttribute()
    {
        return $this->medication?->uuid;
    }

    public function template()
    {
        return $this->belongsTo(PrescriptionTemplate::class, 'template_id');
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }
}
