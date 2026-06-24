<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medication extends Model
{
    use \App\Traits\HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'active_principle',
        'concentration',
        'presentation',
        'administration_route',
        'commercial_name',
        'requires_prescription',
        'contraindications',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_prescription' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prescriptionItems()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function templateItems()
    {
        return $this->hasMany(TemplateItem::class);
    }
}
