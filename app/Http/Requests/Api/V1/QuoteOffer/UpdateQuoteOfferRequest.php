<?php

namespace App\Http\Requests\Api\V1\QuoteOffer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'total_price_base' => 'required|numeric|min:0',
            'currency' => 'string|max:5',
            'availability' => 'string|nullable',
            'comments' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.prescription_item_id' => 'nullable|exists:prescription_items,id',
            'items.*.pharmacy_inventory_id' => 'nullable|exists:pharmacy_inventories,id',
            'items.*.custom_product_name' => 'nullable|string|max:255',
            'items.*.is_substituted' => 'boolean',
            'items.*.substituted_inventory_id' => 'nullable|exists:pharmacy_inventories,id',
            'items.*.substitution_reason' => 'nullable|string',
            'items.*.sell_format' => 'in:package,fraction',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.prices_manual' => 'nullable|array',
            'items.*.notes' => 'nullable|string',
        ];
    }
}
