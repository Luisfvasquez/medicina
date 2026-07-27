<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LabResult;
use App\Services\LabResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabResultController extends Controller
{
    public function __construct(protected LabResultService $resultService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lab_request_id' => 'nullable|integer|exists:lab_requests,id',
            'patient_id' => 'nullable|integer|exists:patients,id',
            'file_url' => 'nullable|string',
            'result_json' => 'nullable|array',
            'attachments_json' => 'nullable|array',
            'notes' => 'nullable|string',
            'performed_at' => 'nullable|date',
        ]);

        $result = $this->resultService->uploadResult($validated, $request->user()?->id);

        return response()->json([
            'message' => 'Resultado de laboratorio cargado exitosamente. Notificación por email procesada.',
            'data' => $result,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $results = LabResult::with(['patient', 'labRequest'])
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return response()->json($results);
    }
}
