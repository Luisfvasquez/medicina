<?php

namespace App\Http\Requests\Api\V1\Vaccination;

use Illuminate\Foundation\Http\FormRequest;

class StoreVaccinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'vaccine' => 'required|string|max:255',
            'dose_number' => 'required|integer|min:1',
            'date' => 'nullable|date',
        ];
    }
}
