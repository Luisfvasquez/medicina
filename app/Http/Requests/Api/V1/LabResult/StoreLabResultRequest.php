<?php

namespace App\Http\Requests\Api\V1\LabResult;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'lab_request_id' => 'required|uuid|exists:lab_requests,id',
            'patient_id' => 'required|uuid|exists:patients,id',
            'file_url' => 'nullable|url',
            'result_json' => 'nullable|array',
            'notes' => 'nullable|string',
            'status' => 'in:PENDING,COMPLETED,ABNORMAL,CANCELLED',
            'performed_at' => 'nullable|date',
        ];
    }
}
