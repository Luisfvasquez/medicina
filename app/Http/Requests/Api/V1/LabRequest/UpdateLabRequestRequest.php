<?php

namespace App\Http\Requests\Api\V1\LabRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLabRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'exams_list' => 'sometimes|array',
            'instructions' => 'nullable|string',
            'is_completed' => 'sometimes|boolean',
        ];
    }
}
