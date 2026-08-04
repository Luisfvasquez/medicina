<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientConsultationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $query = Consultation::where('patient_account_id', $patientAccount->id)
            ->with(['patient', 'user.specialties', 'clinicBranch', 'prescription', 'vitalSign', 'labRequests']);

        // Filtro por especialidad del médico
        if ($request->filled('specialty')) {
            $query->whereHas('user.specialties', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->specialty . '%')
                  ->orWhere('id', $request->specialty);
            });
        }

        // Buscador por texto libre (diagnóstico, motivo o médico)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('diagnosis', 'like', '%' . $search . '%')
                  ->orWhere('reason', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('first_name', 'like', '%' . $search . '%')
                         ->orWhere('last_name', 'like', '%' . $search . '%')
                         ->orWhere('email', 'like', '%' . $search . '%');
                  });
            });
        }

        $consultations = $query->latest('date')->paginate(20);

        return response()->json(['data' => $consultations]);
    }

    public function show(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $consultation = Consultation::where('patient_account_id', $patientAccount->id)
            ->with(['patient', 'user', 'clinicBranch', 'prescription', 'vitalSign', 'labRequests', 'followUps'])
            ->findOrFail($id);

        return response()->json(['data' => $consultation]);
    }
}
