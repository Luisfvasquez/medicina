<?php

namespace App\Http\Requests\Api\V1\Lifestyle;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLifestyleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'smoking_status' => 'sometimes|string|max:50',
            'alcohol_consumption' => 'sometimes|string|max:50',
            'activity_level' => 'sometimes|string|max:50',
            'diet_type' => 'sometimes|string|max:50',
        ];
    }
}
