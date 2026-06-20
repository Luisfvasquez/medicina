<?php

namespace App\Http\Requests\Api\V1\FollowUp;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\FollowStatus;
use Illuminate\Validation\Rule;

class UpdateFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|uuid|exists:users,id',
            'patient_id' => 'sometimes|uuid|exists:patients,id',
            'consultation_id' => 'nullable|uuid|exists:consultations,id',
            'scheduled_date' => 'sometimes|date',
            'status' => ['sometimes', Rule::enum(FollowStatus::class)],
            'response' => 'nullable|string',
        ];
    }
}
