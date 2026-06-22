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
            'price' => 'numeric|min:0',
            'currency' => 'in:USD,VES',
            'availability' => 'nullable|string|max:255',
            'comments' => 'nullable|string',
        ];
    }
}
