<?php

namespace App\Http\Requests\Api\V1\SurgicalHistory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSurgicalHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'procedure' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'hospital' => 'sometimes|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
