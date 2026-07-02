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
            'fullName' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'cityId' => 'nullable|uuid|exists:cities,uuid',
            'specialtyIds' => 'required|array|min:1',
            'specialtyIds.*' => 'exists:specialties,id',
            'medicalLicense' => 'required|file|mimes:pdf,jpg,png|max:10240',
        ];
    }
}
