<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasPublicUuid;

class PharmacySetting extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'provider_id',
        'auto_quoting_enabled',
        'allow_partial_quotes',
        'default_currency',
        'custom_terms',
        'is_24_hours',
        'delivery_radius_km',
    ];

    protected function casts(): array
    {
        return [
            'auto_quoting_enabled' => 'boolean',
            'allow_partial_quotes' => 'boolean',
            'is_24_hours' => 'boolean',
            'delivery_radius_km' => 'decimal:2',
        ];
    }

    public function providerProfile()
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_id');
    }
}
