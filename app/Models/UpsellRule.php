<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasPublicUuid;

class UpsellRule extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'provider_id',
        'trigger_active_ingredient',
        'recommended_inventory_id',
        'discount_percentage',
        'recommendation_reason',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function providerProfile()
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_id');
    }

    public function recommendedInventory()
    {
        return $this->belongsTo(PharmacyInventory::class, 'recommended_inventory_id');
    }
}
