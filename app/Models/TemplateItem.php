<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function template()
    {
        return $this->belongsTo(PrescriptionTemplate::class, 'template_id');
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }
}
