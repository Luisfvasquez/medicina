<?php

namespace App\Http\Requests\Api\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('gender')) {
            $this->merge([
                'gender' => strtoupper($this->gender),
            ]);
        }
    }

    public function rules(): array
    {
        $userId = auth('user_api')->id();
        
        // Resolve patient ID by the UUID route parameter
        $patientUuid = $this->route('patient');
        $patient = \App\Models\Patient::where('uuid', $patientUuid)->first();
        $patientId = $patient ? $patient->id : null;

        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'national_id' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('patients')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId)->whereNull('deleted_at');
                })->ignore($patientId),
            ],
            'birth_date' => 'sometimes|required|date',
            'gender' => 'sometimes|required|string|in:MALE,FEMALE,OTHER',
            'phone' => 'sometimes|required|string|max:50',
            'email' => 'sometimes|required|email|max:255',
            'address' => 'sometimes|required|string',
            'blood_type' => 'nullable|string|max:20',
            'allergies' => 'nullable|string',
            'chronic_conditions' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
        ];
    }
}
