<?php

namespace App\Http\Requests\Api\V1\LabResult;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLabResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'file_url' => 'nullable|url',
            'result_json' => 'nullable|array',
            'notes' => 'nullable|string',
            'reviewed_by' => 'nullable|uuid|exists:users,id',
            'reviewed_at' => 'nullable|date',
            'status' => 'in:PENDING,COMPLETED,ABNORMAL,CANCELLED',
            'performed_at' => 'nullable|date',
        ];
    }
}
