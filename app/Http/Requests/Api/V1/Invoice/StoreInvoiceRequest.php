<?php

namespace App\Http\Requests\Api\V1\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
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
            'consultation_id' => 'nullable|uuid|exists:consultations,id',
            'prescription_id' => 'nullable|uuid|exists:prescriptions,id',
            'subtotal' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'status' => 'in:DRAFT,SENT,PAID,PARTIALLY_PAID,OVERDUE,CANCELLED',
            'due_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ];
    }
}
