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
            'form_template_id' => 'nullable|uuid|exists:form_templates,uuid',
            'form_schema_snapshot' => 'nullable|array',
            'date' => 'sometimes|date',
            'status' => ['sometimes', Rule::enum(ConsultationStatus::class)],
            'reason' => 'nullable|string',
            'physical_exam' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'dynamic_data' => 'nullable|array',
            'services_performed' => 'nullable|array',
            'vitals' => 'nullable|array',
            'vitals.weight' => 'nullable|numeric',
            'vitals.height' => 'nullable|numeric',
            'vitals.systolic_bp' => 'nullable|integer',
            'vitals.diastolic_bp' => 'nullable|integer',
            'vitals.heart_rate' => 'nullable|integer',
            'vitals.respiratory_rate' => 'nullable|numeric',
            'vitals.temperature' => 'nullable|numeric',
            'vitals.oxygen_sat' => 'nullable|integer',
            'follow_up' => 'nullable|array',
            'follow_up.uuid' => 'nullable|uuid',
            'follow_up.scheduled_date' => 'sometimes|date',
            'follow_up.channel' => 'sometimes|string',
            'follow_up.message_template' => 'nullable|string',
        ];
    }
}
