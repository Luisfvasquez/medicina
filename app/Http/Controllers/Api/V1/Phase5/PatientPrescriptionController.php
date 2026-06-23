<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;

class PatientPrescriptionController extends Controller
{
    public function index(): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $prescriptions = Prescription::whereHas('patient', function ($q) use ($patientAccount) {
            $q->where('patient_account_id', $patientAccount->id);
        })
            ->with(['patient', 'user', 'clinicBranch', 'items.medication'])
            ->latest('date')
            ->paginate(20);

        return response()->json(['data' => $prescriptions]);
    }

    public function show(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $prescription = Prescription::whereHas('patient', function ($q) use ($patientAccount) {
            $q->where('patient_account_id', $patientAccount->id);
        })
            ->with(['patient', 'user', 'clinicBranch', 'items.medication', 'consultation'])
            ->findOrFail($id);

        return response()->json(['data' => $prescription]);
    }
}
