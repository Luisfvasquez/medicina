<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

class PatientInvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $invoices = Invoice::where('patient_account_id', $patientAccount->id)
            ->with(['user', 'clinicBranch'])
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $invoices]);
    }

    public function show(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $invoice = Invoice::where('patient_account_id', $patientAccount->id)
            ->with(['user', 'clinicBranch', 'items', 'payments'])
            ->findOrFail($id);

        return response()->json(['data' => $invoice]);
    }

    public function payments(string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $invoice = Invoice::where('patient_account_id', $patientAccount->id)
            ->findOrFail($id);

        return response()->json(['data' => $invoice->payments]);
    }
}
