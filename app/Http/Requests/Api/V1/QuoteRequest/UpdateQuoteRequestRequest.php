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
            'status' => 'required|in:OPEN,CLOSED',
        ];
    }
}
