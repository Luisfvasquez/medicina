<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalSupplySetting extends Model
{
    protected $fillable = [
        'provider_profile_id',
        'is_24_hours',
        'working_days',
        'opening_time',
        'closing_time',
        'auto_matching_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_24_hours' => 'boolean',
            'working_days' => 'array',
            'auto_matching_enabled' => 'boolean',
        ];
    }

    public function providerProfile()
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
