<?php

namespace App\Http\Requests\Api\V1\PharmacyInventoryBatch;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePharmacyInventoryBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string',
            'status' => 'nullable|string',
            'documentUrls' => 'nullable|array',
            'documentUrls.*' => 'string',
            'items' => 'nullable|array',
            'items.*.uuid' => 'nullable|string',
            'items.*.medicationId' => 'nullable|string',
            'items.*.customActivePrinciple' => 'nullable|string',
            'items.*.brandName' => 'nullable|string',
            'items.*.stock' => 'required_with:items|integer|min:0',
            'items.*.batchNumber' => 'nullable|string',
            'items.*.expirationDate' => 'nullable|date',
            'items.*.unitPrice' => 'nullable|numeric|min:0',
        ];
    }
}
