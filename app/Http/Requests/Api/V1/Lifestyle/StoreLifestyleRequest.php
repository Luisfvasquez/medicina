<?php

namespace App\Http\Requests\Api\V1\Lifestyle;

use Illuminate\Foundation\Http\FormRequest;

class StoreLifestyleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'smoking_status' => 'nullable|string|max:50',
            'alcohol_consumption' => 'nullable|string|max:50',
            'activity_level' => 'nullable|string|max:50',
            'diet_type' => 'nullable|string|max:50',
        ];
    }
}
