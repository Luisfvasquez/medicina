<?php

namespace App\Http\Requests\Api\V1\LabRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'patient_uuid' => 'required|uuid|exists:patients,uuid',
            'consultation_uuid' => 'nullable|uuid|exists:consultations,uuid',
            'exams_list' => 'required|array',
            'instructions' => 'nullable|string',
            'is_completed' => 'nullable|boolean',
        ];
    }
}
