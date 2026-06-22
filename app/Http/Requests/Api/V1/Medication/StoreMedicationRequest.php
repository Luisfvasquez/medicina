<?php

namespace App\Http\Requests\Api\V1\Medication;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|uuid|exists:users,id', // NULL = Vademécum Global, NOT NULL = Medicamento privado del doctor
            'active_principle' => 'required|string|max:255',
            'concentration' => 'required|string|max:255',
            'presentation' => 'required|string|in:CAPSULA,TABLETA,JARABE,GOTAS,AMPOLLA,CREMA',
            'administration_route' => 'required|string|in:ORAL,INTRAVENOSA,TOPICA,INTRAMUSCULAR,SUBCUTANEA,RECTAL,INHALATORIA,SUBLINGUAL,TRANSDERMICA',
            'commercial_name' => 'nullable|string|max:255',
            'requires_prescription' => 'boolean',
            'contraindications' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
