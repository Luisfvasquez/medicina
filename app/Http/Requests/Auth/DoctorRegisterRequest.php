<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class DoctorRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'city_uuid' => 'nullable|uuid|exists:cities,uuid',
            'specialty_uuids' => 'required|array|min:1',
            'specialty_uuids.*' => 'uuid|exists:specialties,uuid',
            'medical_license' => 'required|file|mimes:pdf,jpg,png|max:10240',
        ];
    }
}
