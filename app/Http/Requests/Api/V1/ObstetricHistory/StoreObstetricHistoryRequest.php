<?php

namespace App\Http\Requests\Api\V1\ObstetricHistory;

use Illuminate\Foundation\Http\FormRequest;

class StoreObstetricHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'last_period_date' => 'nullable|date',
            'pregnancies' => 'nullable|integer',
            'births' => 'nullable|integer',
            'cesareans' => 'nullable|integer',
            'abortions' => 'nullable|integer',
            'contraceptive_method' => 'nullable|string|max:100',
        ];
    }
}
