<?php

namespace App\Http\Requests\Api\V1\VerificationDocument;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVerificationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'file_url' => 'nullable|url',
            'status' => 'nullable|in:PENDING,APPROVED,REJECTED',
            'comments' => 'nullable|string',
        ];
    }
}
