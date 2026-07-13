<?php

namespace App\Http\Requests\Api\V1\FollowUp;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\FollowStatus;
use Illuminate\Validation\Rule;

class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'uuid' => 'nullable|uuid',
            'user_id' => 'sometimes|uuid|exists:users,id',
            'patient_id' => 'sometimes|uuid|exists:patients,id',
            'patient_uuid' => 'required|uuid|exists:patients,uuid',
            'consultation_id' => 'nullable|uuid|exists:consultations,id',
            'consultation_uuid' => 'nullable|uuid|exists:consultations,uuid',
            'scheduled_date' => 'required|date',
            'status' => ['sometimes', Rule::enum(FollowStatus::class)],
            'response' => 'nullable|string',
            'channel' => 'sometimes|string',
            'message_template' => 'nullable|string',
        ];
    }
}
