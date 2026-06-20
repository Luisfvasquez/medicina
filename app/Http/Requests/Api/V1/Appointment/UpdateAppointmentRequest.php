<?php

namespace App\Http\Requests\Api\V1\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\AppointmentStatus;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'sometimes|uuid|exists:patients,id',
            'user_id' => 'sometimes|uuid|exists:users,id',
            'clinic_branch_id' => 'nullable|uuid|exists:clinic_branches,id',
            'date' => 'sometimes|date',
            'time' => 'sometimes',
            'type' => 'sometimes|string',
            'status' => ['sometimes', Rule::enum(AppointmentStatus::class)],
            'notes' => 'nullable|string',
        ];
    }
}
