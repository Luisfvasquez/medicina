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
            'user_id' => 'required|uuid|exists:users,id',
            'patient_id' => 'required|uuid|exists:patients,id',
            'consultation_id' => 'nullable|uuid|exists:consultations,id',
            'scheduled_date' => 'required|date',
            'status' => ['sometimes', Rule::enum(FollowStatus::class)],
            'response' => 'nullable|string',
        ];
    }
}
