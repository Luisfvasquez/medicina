<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function storePayment(Request $request, string $id): JsonResponse
    {
        $patientAccount = auth('patient_api')->user();

        $invoice = Invoice::where('patient_account_id', $patientAccount->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|in:CASH,CARD,TRANSFER,INSURANCE,OTHER',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'paid_at' => 'nullable|date',
            'receipt' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $folder = sprintf('payment_receipts/%s', $invoice->uuid);
            $receiptPath = Storage::disk('public')->putFileAs($folder, $file, $safeName);
        }

        $payment = Payment::create([
            'uuid' => (string) Str::uuid(),
            'invoice_id' => $invoice->id,
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'reference' => $validated['reference'] ?? null,
            'paid_at' => $validated['paid_at'] ?? now(),
            'notes' => $validated['notes'] ?? null,
            'receipt_path' => $receiptPath,
        ]);

        // Recalcular saldo de la factura
        $totalPaid = $invoice->payments()->sum('amount');
        if ($totalPaid >= $invoice->total) {
            $invoice->update(['status' => 'PAID']);
        } elseif ($totalPaid > 0) {
            $invoice->update(['status' => 'PARTIALLY_PAID']);
        }

        return response()->json([
            'message' => 'Payment reported successfully',
            'data' => $payment->load('invoice'),
        ], 201);
    }
}
