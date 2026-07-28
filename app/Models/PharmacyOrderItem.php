<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyOrderItem extends Model
{
    protected $fillable = [
        'pharmacy_order_id',
        'pharmacy_inventory_id',
        'product_name',
        'sell_format',
        'quantity',
        'unit_prices_manual',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_prices_manual' => 'array',
        ];
    }

    public function order()
    {
        return $this->belongsTo(PharmacyOrder::class, 'pharmacy_order_id');
    }

    public function inventory()
    {
        return $this->belongsTo(PharmacyInventory::class, 'pharmacy_inventory_id');
    }
}
