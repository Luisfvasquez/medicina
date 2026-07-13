<?php

namespace App\Http\Requests\Api\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
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

        return [
            'uuid' => 'nullable|uuid',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('patients')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId)->whereNull('deleted_at');
                }),
            ],
            'birth_date' => 'required|date',
            'gender' => 'required|string|in:MALE,FEMALE,OTHER',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'address' => 'required|string',
            'blood_type' => 'nullable|string|max:20',
            'allergies' => 'nullable|string',
            'chronic_conditions' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
        ];
    }
}
