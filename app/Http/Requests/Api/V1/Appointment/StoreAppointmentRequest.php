<?php

namespace App\Http\Requests\Api\V1\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check() || auth('patient_api')->check();
    }

    public function rules(): array
    {
        return [
            'patient_id' => [
                'required',
                'uuid',
                function ($attribute, $value, $fail) {
                    $existsInPatients = \App\Models\Patient::where('uuid', $value)->exists();
                    $existsInAccounts = \App\Models\PatientAccount::where('uuid', $value)->exists();
                    if (!$existsInPatients && !$existsInAccounts) {
                        $fail('The selected patient id is invalid.');
                    }
                },
            ],
            'user_id' => 'required|uuid|exists:users,uuid',
            'clinic_branch_id' => 'nullable|uuid|exists:clinic_branches,uuid',
            'date' => 'required|date',
            'time' => 'required',
            'type' => 'required|string',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}
