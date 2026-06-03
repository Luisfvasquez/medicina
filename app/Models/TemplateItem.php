<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateItem extends Model
{
    use \App\Traits\HasPublicUuid;

    protected $fillable = [
        'template_id',
        'medication',
        'dosage',
        'frequency',
        'duration',
    ];

    public function template()
    {
        return $this->belongsTo(PrescriptionTemplate::class, 'template_id');
    }
}
