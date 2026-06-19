<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PatientRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:patient_accounts,email',
            'phone' => 'required|string|max:20|unique:patient_accounts,phone',
            'password' => 'nullable|string|min:8',
            'national_id' => 'nullable|string|max:50|unique:patient_accounts,national_id',
            'username' => 'nullable|string|max:50|unique:patient_accounts,username',
            'city_id' => 'nullable|uuid|exists:cities,id',
        ];
    }
}
