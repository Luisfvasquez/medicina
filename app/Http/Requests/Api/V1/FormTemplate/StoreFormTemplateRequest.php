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
            'id' => 'required|string',
            'name' => 'required|string|max:255',
            'specialty' => 'nullable|string|max:100',
            'document_category' => 'nullable|string|max:50',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'target_role' => 'nullable|array',
            'target_role.*' => 'string|in:doctor,nurse,admin',
            'applicable_to' => 'nullable|array',
            'applicable_to.*' => 'string|in:adult,pediatric,obstetric,all',
            'canvas' => 'required|array',
            'settings' => 'nullable|array',
            'status' => 'nullable|string|in:draft,published',
            'description' => 'nullable|string',
            'version' => 'nullable|string',
        ];
    }
}
