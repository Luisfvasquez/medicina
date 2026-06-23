<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\JsonResponse;

class PatientConsultationController extends Controller
{
    public function index(): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $consultations = Consultation::where('patient_account_id', $patientAccount->id)
            ->with(['patient', 'user', 'clinicBranch', 'prescription', 'vitalSign', 'labRequest'])
            ->latest('date')
            ->paginate(20);

        return response()->json(['data' => $consultations]);
    }

    public function show(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $consultation = Consultation::where('patient_account_id', $patientAccount->id)
            ->with(['patient', 'user', 'clinicBranch', 'prescription', 'vitalSign', 'labRequest', 'followUps'])
            ->findOrFail($id);

        return response()->json(['data' => $consultation]);
    }
}
