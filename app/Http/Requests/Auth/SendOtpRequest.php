<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            // role es opcional — el backend auto-detecta buscando en patient_accounts y users
            'role'    => ['sometimes', 'in:DOCTOR,PATIENT,PROVIDER,ADMIN'],
            'channel' => ['required', 'in:WHATSAPP,EMAIL'],
        ];

        // Phone required when channel is WHATSAPP
        if ($this->input('channel') === 'WHATSAPP') {
            $rules['phone'] = ['required', 'regex:/^\+[1-9]\d{7,14}$/'];
        }

        // Email required when channel is EMAIL
        if ($this->input('channel') === 'EMAIL') {
            $rules['email'] = ['required', 'email'];
        }

        // When channel is not yet known, make both optional but at least one required
        if (!$this->has('channel')) {
            $rules['phone']  = ['sometimes', 'regex:/^\+[1-9]\d{7,14}$/'];
            $rules['email'] = ['sometimes', 'email'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'phone.regex'  => 'El formato del número telefónico de WhatsApp no es válido. Use formato E.164 (ej: +584121234567).',
            'email.email'  => 'El correo ingresado no tiene un dominio válido.',
            'role.in'      => 'El rol debe ser uno de: DOCTOR, PATIENT, PROVIDER, ADMIN.',
            'channel.in'   => 'El canal debe ser WHATSAPP o EMAIL.',
        ];
    }

    /**
     * Configure the validator instance.
     */
}

