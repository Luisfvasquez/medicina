<?php

namespace App\Http\Requests\Api\V1\MedicalDocument;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'type' => 'in:CERTIFICATE,REFERRAL,REPORT',
            'content' => 'string',
        ];
    }
}
