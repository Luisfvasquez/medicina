<?php

namespace App\Http\Requests\Api\V1\PrescriptionTemplate;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.medication_id' => 'nullable|uuid|exists:medications,uuid',
            'items.*.dose' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string',
        ];
    }
}
