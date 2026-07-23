<?php

namespace App\Http\Requests\Api\V1\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\AppointmentStatus;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check() || auth('patient_api')->check();
    }

    public function rules(): array
    {
        return [
            'patient_id' => [
                'sometimes',
                'uuid',
                function ($attribute, $value, $fail) {
                    $existsInPatients = \App\Models\Patient::where('uuid', $value)->exists();
                    $existsInAccounts = \App\Models\PatientAccount::where('uuid', $value)->exists();
                    if (!$existsInPatients && !$existsInAccounts) {
                        $fail('The selected patient id is invalid.');
                    }
                },
            ],
            'user_id' => 'sometimes|uuid|exists:users,uuid',
            'clinic_branch_id' => 'nullable|uuid|exists:clinic_branches,uuid',
            'date' => 'sometimes|date',
            'time' => 'sometimes',
            'type' => 'sometimes|string',
            'status' => ['sometimes', Rule::enum(AppointmentStatus::class)],
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}
