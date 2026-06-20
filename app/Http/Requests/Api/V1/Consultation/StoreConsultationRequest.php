<?php

namespace App\Http\Requests\Api\V1\Consultation;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
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
            'appointment_id' => 'nullable|uuid|exists:appointments,id',
            'clinic_branch_id' => 'nullable|uuid|exists:clinic_branches,id',
            'form_template_id' => 'nullable|uuid|exists:form_templates,id',
            'date' => 'required|date',
            'reason' => 'nullable|string',
            'physical_exam' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'dynamic_data' => 'nullable|array',
        ];
    }
}
