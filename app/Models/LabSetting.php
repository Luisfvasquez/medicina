<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabSetting extends Model
{
    protected $fillable = [
        'provider_profile_id',
        'daily_max_slots',
        'auto_quoting_enabled',
        'default_currency',
        'instructions_for_patient',
    ];

    protected function casts(): array
    {
        return [
            'auto_quoting_enabled' => 'boolean',
            'daily_max_slots' => 'integer',
        ];
    }

    public function providerProfile()
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
