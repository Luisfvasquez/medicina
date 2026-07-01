<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required_without:email',
                'regex:/^\+[1-9]\d{7,14}$/',
            ],
            'email' => [
                'required_without:phone',
                'email',
            ],
            'code'  => ['required', 'digits:6'],
            'role'  => ['sometimes', 'in:DOCTOR,PATIENT,PROVIDER,ADMIN'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required_without' => 'El teléfono o correo es requerido.',
            'phone.regex'            => 'El formato del número telefónico no es válido.',
            'email.required_without'  => 'El teléfono o correo es requerido.',
            'email.email'             => 'El correo ingresado no tiene un formato válido.',
            'code.required'           => 'El código de verificación es requerido.',
            'code.digits'            => 'El código debe ser de exactamente 6 dígitos.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Al menos phone o email debe estar presente
            if (empty($this->input('phone')) && empty($this->input('email'))) {
                $validator->errors()->add('phone', 'Debe proporcionar teléfono o correo electrónico.');
            }
        });
    }
}
