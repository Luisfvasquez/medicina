<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;

class PatientAppointmentController extends Controller
{
    public function index(): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $appointments = Appointment::whereHas('patient', function ($q) use ($patientAccount) {
            $q->where('patient_account_id', $patientAccount->id);
        })
            ->with(['patient', 'doctor', 'clinicBranch'])
            ->latest('date')
            ->paginate(20);

        return response()->json(['data' => $appointments]);
    }

    public function show(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $appointment = Appointment::whereHas('patient', function ($q) use ($patientAccount) {
            $q->where('patient_account_id', $patientAccount->id);
        })
            ->with(['patient', 'doctor', 'clinicBranch', 'consultation'])
            ->findOrFail($id);

        return response()->json(['data' => $appointment]);
    }
}
