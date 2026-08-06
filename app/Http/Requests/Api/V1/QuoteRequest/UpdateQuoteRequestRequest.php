<?php

namespace App\Http\Requests\Api\V1\QuoteRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:OPEN,CLOSED',
            'latitude' => 'sometimes|numeric|between:-90,90|nullable',
            'longitude' => 'sometimes|numeric|between:-180,180|nullable',
            'search_radius_km' => 'sometimes|numeric|min:1',
        ];
    }
}
