<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LabRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExternalLabOrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'external_patient_name' => 'required|string|max:255',
            'external_patient_document' => 'nullable|string|max:50',
            'exams_list' => 'required|array|min:1',
            'instructions' => 'nullable|string',
        ]);

        $labRequest = LabRequest::create([
            'uuid' => (string) Str::uuid(),
            'is_external' => true,
            'external_patient_name' => $validated['external_patient_name'],
            'external_patient_document' => $validated['external_patient_document'] ?? null,
            'exams_list' => $validated['exams_list'],
            'instructions' => $validated['instructions'] ?? null,
            'is_completed' => false,
        ]);

        return response()->json([
            'message' => 'Orden de laboratorio externa ("Walk-in") registrada exitosamente.',
            'data' => $labRequest,
        ], 201);
    }
}
