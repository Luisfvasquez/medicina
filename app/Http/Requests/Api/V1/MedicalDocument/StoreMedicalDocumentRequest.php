<?php

namespace App\Http\Requests\Api\V1\MedicalDocument;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|uuid|exists:patients,id',
            'clinic_branch_id' => 'nullable|uuid|exists:clinic_branches,id',
            'type' => 'required|in:CERTIFICATE,REFERRAL,REPORT',
            'content' => 'required|string',
        ];
    }
}
