<?php

namespace App\Http\Requests\Api\V1\QuoteOffer;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'provider_id' => 'required|uuid|exists:provider_profiles,id',
            'price' => 'required|numeric|min:0',
            'currency' => 'in:USD,VES',
            'availability' => 'nullable|string|max:255',
            'comments' => 'nullable|string',
        ];
    }
}
