<?php

namespace App\Http\Requests\Api\V1\VitalSign;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVitalSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'weight' => 'sometimes|numeric',
            'height' => 'sometimes|numeric',
            'systolic_bp' => 'sometimes|integer',
            'diastolic_bp' => 'sometimes|integer',
            'heart_rate' => 'sometimes|integer',
            'respiratory_rate' => 'sometimes|numeric',
            'temperature' => 'sometimes|numeric',
            'oxygen_sat' => 'sometimes|integer',
            'date' => 'sometimes|date',
        ];
    }
}
