<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\JsonResponse;

class PatientQuoteRequestController extends Controller
{
    public function index(): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $requests = QuoteRequest::whereHas('patient', function ($q) use ($patientAccount) {
            $q->where('patient_account_id', $patientAccount->id);
        })
            ->with(['prescription', 'city', 'offers.pharmacy'])
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $requests]);
    }

    public function show(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $request = QuoteRequest::whereHas('patient', function ($q) use ($patientAccount) {
            $q->where('patient_account_id', $patientAccount->id);
        })
            ->with(['prescription.items.medication', 'city', 'offers.pharmacy'])
            ->findOrFail($id);

        return response()->json(['data' => $request]);
    }

    public function offers(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $request = QuoteRequest::whereHas('patient', function ($q) use ($patientAccount) {
            $q->where('patient_account_id', $patientAccount->id);
        })
            ->with(['offers.pharmacy'])
            ->findOrFail($id);

        return response()->json(['data' => $request->offers]);
    }
}
