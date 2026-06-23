<?php

namespace App\Http\Requests\Api\V1\VerificationDocument;

use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:MEDICAL_LICENSE,NATIONAL_ID,BUSINESS_RIF',
            'file_url' => 'required|url',
        ];
    }
}
