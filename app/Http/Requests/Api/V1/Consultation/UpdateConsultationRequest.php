<?php

namespace App\Http\Requests\Api\V1\Consultation;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ConsultationStatus;
use Illuminate\Validation\Rule;

class UpdateConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|uuid|exists:users,id',
            'patient_id' => 'sometimes|uuid|exists:patients,id',
            'appointment_id' => 'nullable|uuid|exists:appointments,id',
            'clinic_branch_id' => 'sometimes|uuid|exists:clinic_branches,id',
            'form_template_id' => 'nullable|uuid|exists:form_templates,id',
            'date' => 'sometimes|date',
            'status' => ['sometimes', Rule::enum(ConsultationStatus::class)],
            'reason' => 'nullable|string',
            'physical_exam' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'dynamic_data' => 'nullable|array',
        ];
    }
}
