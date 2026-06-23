<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\MedicalDocument;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;

class VerifyController extends Controller
{
    public function verifyPrescription(string $publicToken): JsonResponse
    {
        $prescription = Prescription::where('public_token', $publicToken)
            ->with(['patient', 'user', 'clinicBranch', 'items.medication'])
            ->firstOrFail();

        return response()->json(['data' => [
            'type' => 'prescription',
            'valid' => true,
            'prescription' => [
                'id' => $prescription->uuid,
                'date' => $prescription->date,
                'expiration_date' => $prescription->expiration_date,
                'status' => $prescription->status->value,
                'doctor' => [
                    'name' => $prescription->user->full_name,
                    'professional_id' => $prescription->user->professional_id,
                    'specialty' => $prescription->user->specialty?->name,
                ],
                'patient' => [
                    'name' => $prescription->patient->full_name,
                    'national_id' => $prescription->patient->national_id,
                ],
                'clinic' => $prescription->clinicBranch?->name,
                'items' => $prescription->items->map(fn ($item) => [
                    'medication' => [
                        'name' => $item->medication->name,
                        'concentration' => $item->medication->concentration,
                        'presentation' => $item->medication->presentation,
                        'active_ingredient' => $item->medication->active_ingredient,
                    ],
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                    'duration' => $item->duration,
                    'quantity' => $item->quantity,
                    'instructions' => $item->instructions,
                ]),
                'notes' => $prescription->notes,
            ],
        ]]);
    }

    public function verifyDocument(string $publicToken): JsonResponse
    {
        $document = MedicalDocument::where('public_token', $publicToken)
            ->with(['patient', 'user', 'clinicBranch'])
            ->firstOrFail();

        return response()->json(['data' => [
            'type' => 'document',
            'valid' => true,
            'document' => [
                'id' => $document->uuid,
                'type' => $document->type->value,
                'date' => $document->created_at,
                'doctor' => [
                    'name' => $document->user->full_name,
                    'professional_id' => $document->user->professional_id,
                    'specialty' => $document->user->specialty?->name,
                ],
                'patient' => [
                    'name' => $document->patient->full_name,
                ],
                'clinic' => $document->clinicBranch?->name,
            ],
        ]]);
    }
}
