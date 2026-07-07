<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\LabResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientLabResultController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $query = LabResult::whereHas('patient', function ($q) use ($patientAccount) {
            $q->where('patient_account_id', $patientAccount->id);
        })
            ->with(['labRequest', 'reviewedBy']);

        // Filtro por estado del resultado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Buscador por texto libre (notas o tipo de examen)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', '%' . $search . '%')
                  ->orWhereHas('labRequest', function ($qr) use ($search) {
                      $qr->where('exams_list', 'like', '%' . $search . '%')
                         ->orWhere('instructions', 'like', '%' . $search . '%');
                  });
            });
        }

        $results = $query->latest()->paginate(20);

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
