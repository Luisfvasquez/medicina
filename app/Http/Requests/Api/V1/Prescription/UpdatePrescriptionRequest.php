<?php

namespace App\Http\Requests\Api\V1\Prescription;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'clinic_branch_id' => 'nullable|uuid|exists:clinic_branches,id',
            'date' => 'date',
            'expiration_date' => 'date|after:date',
            'notes' => 'nullable|string',
            'status' => 'in:ACTIVE,CANCELLED,EXPIRED',
        ];
    }
}
