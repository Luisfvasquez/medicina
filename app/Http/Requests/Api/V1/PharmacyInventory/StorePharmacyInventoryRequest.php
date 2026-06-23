<?php

namespace App\Http\Requests\Api\V1\PharmacyInventory;

use Illuminate\Foundation\Http\FormRequest;

class StorePharmacyInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'provider_id' => 'required|uuid|exists:provider_profiles,id',
            'medication_id' => 'required|uuid|exists:medications,id',
            'stock' => 'required|integer|min:0',
            'min_stock_alert' => 'nullable|integer|min:0',
            'batch_number' => 'nullable|string|max:100',
            'expiration_date' => 'nullable|date|after:today',
            'unit_price' => 'nullable|numeric|min:0',
        ];
    }
}
