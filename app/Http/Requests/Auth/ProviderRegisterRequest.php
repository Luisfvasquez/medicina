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
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|in:PHARMACY,LABORATORY',
            'tax_id' => 'required|string|max:50', // RIF
            'business_document' => 'required|file|mimes:pdf,jpg,png|max:10240',
        ];
    }
}
