<?php

namespace App\Http\Requests\Api\V1\FormTemplate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'schema_json' => 'sometimes|array',
            'specialty' => 'nullable|string|max:100',
        ];
    }
}
