<?php

namespace App\Http\Requests\Api\V1\PharmacyInventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePharmacyInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'ean_code' => 'nullable|string|max:50',
            'active_ingredient' => 'nullable|string|max:255',
            'laboratory' => 'nullable|string|max:255',
            'sale_condition' => 'in:free,prescription,controlled',
            'stock' => 'integer|min:0',
            'min_stock_alert' => 'integer|min:0',
            'batch_number' => 'nullable|string|max:100',
            'expiration_date' => 'nullable|date',
            'location_rack' => 'nullable|string|max:100',
            'allows_fractioning' => 'boolean',
            'units_per_package' => 'integer|min:1',
            'fraction_unit_name' => 'string|max:50',
            'package_stock' => 'integer|min:0',
            'fraction_stock' => 'integer|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'prices_manual' => 'nullable|array',
        ];
    }
}
