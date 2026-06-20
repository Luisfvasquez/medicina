<?php

namespace App\Http\Requests\Api\V1\MedicalBackground;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalBackgroundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'has_diabetes' => 'nullable|boolean',
            'has_hypertension' => 'nullable|boolean',
            'has_asthma' => 'nullable|boolean',
            'other_conditions' => 'nullable|string',
            'past_hospitalizations' => 'nullable|string',
        ];
    }
}
