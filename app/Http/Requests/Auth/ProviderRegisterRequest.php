<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ProviderRegisterRequest extends FormRequest
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
            'nationalId' => 'nullable|string|max:50',
            'cityId' => 'nullable|uuid|exists:cities,uuid',
            'commercialName' => 'required|string|max:255',
            'providerType' => 'required|in:PHARMACY,LABORATORY',
            'rif' => 'required|string|max:50|unique:provider_profiles,rif',
            'businessDocument' => 'required|file|mimes:pdf,jpg,png|max:10240',
        ];
    }
}
