<?php

namespace App\Http\Requests\Api\V1\FormTemplate;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'schema_json' => 'required|array',
            'user_id' => 'nullable|uuid|exists:users,id',
            'specialty' => 'nullable|string|max:100',
        ];
    }
}
