<?php

namespace App\Http\Requests\Api\V1\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|uuid|exists:patients,uuid',
            'user_id' => 'required|uuid|exists:users,uuid',
            'clinic_branch_id' => 'nullable|uuid|exists:clinic_branches,uuid',
            'date' => 'required|date',
            'time' => 'required',
            'type' => 'required|string',
            'notes' => 'nullable|string',
        ];
    }
}
