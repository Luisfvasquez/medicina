<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\MedicalDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PatientMedicalDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $query = MedicalDocument::where('patient_account_id', $patientAccount->id)
            ->with(['user', 'clinicBranch']);

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $documents = $query->latest()->paginate(20);

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
