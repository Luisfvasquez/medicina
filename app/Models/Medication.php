<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    use HasPublicUuid;

    protected $fillable = [
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
