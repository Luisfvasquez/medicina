<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\LabResult;
use Illuminate\Http\JsonResponse;

class PatientLabResultController extends Controller
{
    public function index(): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $results = LabResult::whereHas('patient', function ($q) use ($patientAccount) {
            $q->where('patient_account_id', $patientAccount->id);
        })
            ->with(['labRequest', 'reviewedBy'])
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $results]);
    }

    public function show(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $result = LabResult::whereHas('patient', function ($q) use ($patientAccount) {
            $q->where('patient_account_id', $patientAccount->id);
        })
            ->with(['labRequest', 'reviewedBy', 'patient'])
            ->findOrFail($id);

        return response()->json(['data' => $result]);
    }
}
