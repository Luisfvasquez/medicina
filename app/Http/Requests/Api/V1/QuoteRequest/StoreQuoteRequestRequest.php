<?php

namespace App\Http\Requests\Api\V1\QuoteRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'prescription_id' => 'required|uuid|exists:prescriptions,id',
            'patient_id' => 'required|uuid|exists:patients,id',
            'city_id' => 'nullable|uuid|exists:cities,id',
        ];
    }
}
