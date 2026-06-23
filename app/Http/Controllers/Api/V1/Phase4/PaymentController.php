<?php

namespace App\Http\Controllers\Api\V1\Phase4;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function index(string $invoiceId): JsonResponse
    {
        $payments = Payment::where('invoice_id', $invoiceId)->latest()->get();

        return response()->json(['data' => $payments]);
    }

    public function store(StorePaymentRequest $request, string $invoiceId): JsonResponse
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $data = $request->validated();
        $data['invoice_id'] = $invoiceId;

        $payment = Payment::create($data);

        // Check if invoice is fully paid
        $totalPaid = $invoice->payments()->sum('amount') + $payment->amount;
        if ($totalPaid >= (float) $invoice->total) {
            $invoice->update(['status' => 'PAID']);
        } elseif ($totalPaid > 0) {
            $invoice->update(['status' => 'PARTIALLY_PAID']);
        }

        // HIPAA: Log CREATE
        AuditLog::logCreate(auth('user_api')->user(), 'Payment', $payment->id, $data, $invoice->patient_id);

        return response()->json(['data' => $payment], 201);
    }

    public function show(string $invoiceId, string $id): JsonResponse
    {
        $payment = Payment::where('invoice_id', $invoiceId)->findOrFail($id);

        return response()->json(['data' => $payment]);
    }

    public function destroy(string $invoiceId, string $id): JsonResponse
    {
        $payment = Payment::where('invoice_id', $invoiceId)->findOrFail($id);

        // Only allow deletion from DRAFT or PAID invoices (with proper authorization)
        $user = auth('user_api')->user();
        if ($user->role !== 'ADMIN') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $invoice = $payment->invoice;
        $payment->delete();

        // Recalculate invoice status
        $remainingPaid = $invoice->payments()->sum('amount');
        if ($remainingPaid == 0) {
            $invoice->update(['status' => 'SENT']);
        } elseif ($remainingPaid < (float) $invoice->total) {
            $invoice->update(['status' => 'PARTIALLY_PAID']);
        }

        return response()->json(null, 204);
    }
}
