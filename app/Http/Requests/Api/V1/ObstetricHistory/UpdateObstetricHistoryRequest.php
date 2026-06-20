<?php

namespace App\Http\Requests\Api\V1\ObstetricHistory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateObstetricHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'last_period_date' => 'sometimes|date',
            'pregnancies' => 'sometimes|integer',
            'births' => 'sometimes|integer',
            'cesareans' => 'sometimes|integer',
            'abortions' => 'sometimes|integer',
            'contraceptive_method' => 'nullable|string|max:100',
        ];
    }
}
