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
            'patient_uuid' => 'sometimes|uuid|exists:patients,uuid',
            'appointment_id' => 'nullable|uuid|exists:appointments,id',
            'appointment_uuid' => 'sometimes|uuid|exists:appointments,uuid',
            'clinic_branch_id' => 'sometimes|uuid|exists:clinic_branches,id',
            'clinic_branch_uuid' => 'sometimes|uuid|exists:clinic_branches,uuid',
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
