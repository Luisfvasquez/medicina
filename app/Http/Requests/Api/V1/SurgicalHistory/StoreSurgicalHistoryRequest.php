<?php

namespace App\Http\Requests\Api\V1\SurgicalHistory;

use Illuminate\Foundation\Http\FormRequest;

class StoreSurgicalHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'procedure' => 'required|string|max:255',
            'date' => 'nullable|date',
            'hospital' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
