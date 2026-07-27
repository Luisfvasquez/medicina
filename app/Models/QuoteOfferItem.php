<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasPublicUuid;

class QuoteOfferItem extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'quote_offer_id',
        'prescription_item_id',
        'pharmacy_inventory_id',
        'custom_product_name',
        'is_substituted',
        'substituted_inventory_id',
        'substitution_reason',
        'sell_format',
        'quantity',
        'prices_manual',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_substituted' => 'boolean',
            'quantity' => 'integer',
            'prices_manual' => 'array',
        ];
    }

    public function quoteOffer()
    {
        return $this->belongsTo(QuoteOffer::class);
    }

    public function prescriptionItem()
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function inventory()
    {
        return $this->belongsTo(PharmacyInventory::class, 'pharmacy_inventory_id');
    }

    public function substitutedInventory()
    {
        return $this->belongsTo(PharmacyInventory::class, 'substituted_inventory_id');
    }
}
