<?php

namespace App\Http\Requests\Api\V1\Vaccination;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVaccinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'vaccine' => 'sometimes|string|max:255',
            'dose_number' => 'sometimes|integer|min:1',
            'date' => 'sometimes|date',
        ];
    }
}
