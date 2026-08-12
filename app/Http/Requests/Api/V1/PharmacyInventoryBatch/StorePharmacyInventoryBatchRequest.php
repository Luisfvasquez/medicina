<?php

namespace App\Http\Requests\Api\V1\PharmacyInventoryBatch;

use Illuminate\Foundation\Http\FormRequest;

class StorePharmacyInventoryBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('user_api')->check();
    }

    public function rules(): array
    {
        return [
            'batch.documentUrls' => 'nullable|array',
            'batch.documentUrls.*' => 'string',
            'batch.notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.medicationId' => 'nullable|string|exists:medications,uuid',
            'items.*.customActivePrinciple' => 'nullable|string',
            'items.*.brandName' => 'nullable|string',
            'items.*.stock' => 'required|integer|min:0',
            'items.*.batchNumber' => 'nullable|string',
            'items.*.expirationDate' => 'nullable|date',
            'items.*.unitPrice' => 'nullable|numeric|min:0',
        ];
    }
}
