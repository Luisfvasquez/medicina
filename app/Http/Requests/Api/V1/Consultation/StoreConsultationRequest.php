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
            'user_id' => 'nullable|uuid|exists:users,id',
            'patient_uuid' => 'required|uuid|exists:patients,uuid',
            'appointment_uuid' => 'nullable|uuid|exists:appointments,uuid',
            'clinic_branch_uuid' => 'nullable|uuid|exists:clinic_branches,uuid',
            'form_template_id' => 'nullable|uuid|exists:form_templates,id',
            'date' => 'nullable|date',
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
