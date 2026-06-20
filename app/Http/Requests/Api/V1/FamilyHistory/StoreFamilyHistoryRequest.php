<?php

namespace App\Http\Requests\Api\V1\FamilyHistory;

use Illuminate\Foundation\Http\FormRequest;

class StoreFamilyHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'condition' => 'required|string|max:255',
            'relationship' => 'nullable|string|max:100',
            'note' => 'nullable|string',
        ];
    }
}
