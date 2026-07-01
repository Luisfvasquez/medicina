<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email'    => 'El correo ingresado no tiene un formato válido.',
            'password.required' => 'La contraseña es requerida.',
            'password.min'   => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }
}
