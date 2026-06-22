<?php

namespace App\Http\Requests\Api\V1\Medication;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'active_principle' => 'string|max:255',
            'concentration' => 'string|max:255',
            'presentation' => 'string|max:255',
            'administration_route' => 'string|max:255',
            'commercial_name' => 'nullable|string|max:255',
            'requires_prescription' => 'boolean',
            'contraindications' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
