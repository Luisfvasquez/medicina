<?php

namespace App\Http\Requests\Api\V1\VitalSign;

use Illuminate\Foundation\Http\FormRequest;

class StoreVitalSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'weight' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'systolic_bp' => 'nullable|integer',
            'diastolic_bp' => 'nullable|integer',
            'heart_rate' => 'nullable|integer',
            'respiratory_rate' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'oxygen_sat' => 'nullable|integer',
            'date' => 'nullable|date',
        ];
    }
}
