<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalSupplyQuoteOffer extends Model
{
    protected $fillable = [
        'medical_supply_order_id',
        'provider_profile_id',
        'total_price',
        'currency',
        'items_detail',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'items_detail' => 'array',
        ];
    }

    public function medicalSupplyOrder()
    {
        return $this->belongsTo(MedicalSupplyOrder::class);
    }

    public function providerProfile()
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
