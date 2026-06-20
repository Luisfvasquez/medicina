<?php

namespace App\Http\Requests\Api\V1\MedicalBackground;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalBackgroundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'has_diabetes' => 'sometimes|boolean',
            'has_hypertension' => 'sometimes|boolean',
            'has_asthma' => 'sometimes|boolean',
            'other_conditions' => 'nullable|string',
            'past_hospitalizations' => 'nullable|string',
        ];
    }
}
