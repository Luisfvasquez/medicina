<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyInventory extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'provider_id',
        'medication_id',
        'stock',
        'min_stock_alert',
        'batch_number',
        'expiration_date',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'min_stock_alert' => 'integer',
            'expiration_date' => 'date',
            'unit_price' => 'decimal:2',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_id');
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock_alert;
    }

    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }
}
