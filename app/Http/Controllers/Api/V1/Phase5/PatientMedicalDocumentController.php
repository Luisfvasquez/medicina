<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\MedicalDocument;
use Illuminate\Http\JsonResponse;

class PatientMedicalDocumentController extends Controller
{
    public function index(): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $documents = MedicalDocument::where('patient_account_id', $patientAccount->id)
            ->with(['user', 'clinicBranch'])
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $documents]);
    }

    public function show(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $document = MedicalDocument::where('patient_account_id', $patientAccount->id)
            ->with(['user', 'clinicBranch'])
            ->findOrFail($id);

        return response()->json(['data' => $document]);
    }
}
