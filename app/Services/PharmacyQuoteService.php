<?php

namespace App\Services;

use App\Models\QuoteOffer;
use App\Models\QuoteOfferItem;
use App\Models\PharmacySetting;
use Illuminate\Support\Str;

class PharmacyQuoteService
{
    /**
     * Crear una oferta de cotización manual o ad-hoc (con o sin inventario previo)
     */
    public function createQuoteOffer(int $quoteRequestId, int $providerId, array $data): QuoteOffer
    {
        $offer = QuoteOffer::create([
            'uuid' => (string) Str::uuid(),
            'quote_request_id' => $quoteRequestId,
            'provider_id' => $providerId,
            'price' => $data['total_price_base'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'availability' => $data['availability'] ?? 'in_stock',
            'comments' => $data['comments'] ?? null,
        ]);

        foreach ($data['items'] as $itemData) {
            QuoteOfferItem::create([
                'uuid' => (string) Str::uuid(),
                'quote_offer_id' => $offer->id,
                'prescription_item_id' => $itemData['prescription_item_id'] ?? null,
                'pharmacy_inventory_id' => $itemData['pharmacy_inventory_id'] ?? null, // Nullable para ítems ad-hoc
                'custom_product_name' => $itemData['custom_product_name'] ?? null,
                'is_substituted' => $itemData['is_substituted'] ?? false,
                'substituted_inventory_id' => $itemData['substituted_inventory_id'] ?? null,
                'substitution_reason' => $itemData['substitution_reason'] ?? null,
                'sell_format' => $itemData['sell_format'] ?? 'package',
                'quantity' => $itemData['quantity'] ?? 1,
                'prices_manual' => $itemData['prices_manual'] ?? null, // Precios ingresados manualmente {"VES": 200, "USD": 40, "EUR": 34}
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        return $offer->load('quoteOfferItems');
    }

    /**
     * Consultar si la farmacia tiene habilitada cotización automática
     */
    public function isAutoQuotingEnabled(int $providerId): bool
    {
        $setting = PharmacySetting::where('provider_id', $providerId)->first();
        return $setting ? $setting->auto_quoting_enabled : false;
    }
}
