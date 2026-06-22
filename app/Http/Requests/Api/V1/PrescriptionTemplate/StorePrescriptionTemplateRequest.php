<?php

namespace App\Http\Requests\Api\V1\PrescriptionTemplate;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'items' => 'nullable|array',
            'items.*.medication_id' => 'nullable|uuid|exists:medications,id',
            'items.*.dose' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string',
        ];
    }
}
