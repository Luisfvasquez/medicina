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
            'id' => 'sometimes|string',
            'name' => 'sometimes|string|max:255',
            'specialty' => 'nullable|string|max:100',
            'canvas' => 'sometimes|array',
            'settings' => 'nullable|array',
            'status' => 'nullable|string|in:draft,published',
            'description' => 'nullable|string',
            'version' => 'nullable|string',
        ];
    }
}
