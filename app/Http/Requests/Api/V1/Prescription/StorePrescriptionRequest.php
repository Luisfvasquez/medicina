<?php

namespace App\Http\Requests\Api\V1\Prescription;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|uuid|exists:users,id',
            'patient_id' => 'required|uuid|exists:patients,id',
            'consultation_id' => 'nullable|uuid|exists:consultations,id',
            'clinic_branch_id' => 'nullable|uuid|exists:clinic_branches,id',
            'date' => 'required|date',
            'expiration_date' => 'required|date|after:date',
            'notes' => 'nullable|string',
            'status' => 'in:ACTIVE,CANCELLED,EXPIRED',
            'items' => 'nullable|array',
            'items.*.medication_id' => 'nullable|uuid|exists:medications,id',
            'items.*.dose' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:255',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ];
    }
}
