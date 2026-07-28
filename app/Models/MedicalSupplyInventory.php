<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalSupplyInventory extends Model
{
    protected $fillable = [
        'provider_profile_id',
        'item_name',
        'sku',
        'price_usd',
        'price_bs',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_usd' => 'decimal:2',
            'price_bs' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function providerProfile()
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
