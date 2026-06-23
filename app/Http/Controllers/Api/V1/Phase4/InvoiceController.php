<?php

namespace App\Http\Controllers\Api\V1\Phase4;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\Invoice\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('user_api')->user();

        $invoices = Invoice::with(['patient', 'user', 'clinicBranch', 'items'])
            ->when($user->role === 'DOCTOR', fn($q) => $q->where('user_id', $user->id))
            ->when($user->role === 'PATIENT', fn($q) => $q->where('patient_id', $user->patient->id ?? null))
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $invoices]);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth('user_api')->id();

        // Calculate totals if items provided
        if (!empty($data['items'])) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemTotal = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                $subtotal += $itemTotal;
            }
            $data['subtotal'] = $subtotal;
            $data['total'] = $subtotal + ($data['tax'] ?? 0) - ($data['discount'] ?? 0);
        }

        $invoice = Invoice::create($data);

        // Create invoice items
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $item['invoice_id'] = $invoice->id;
                $item['total'] = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                InvoiceItem::create($item);
            }
        }

        // HIPAA: Log CREATE
        AuditLog::logCreate(auth('user_api')->user(), 'Invoice', $invoice->id, $data, $invoice->patient_id);

        return response()->json(['data' => $invoice->load(['patient', 'user', 'clinicBranch', 'items'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $user = auth('user_api')->user();
        $invoice = Invoice::with(['patient', 'user', 'clinicBranch', 'items', 'payments'])->findOrFail($id);

        // HIPAA: Log VIEW
        AuditLog::logView($user, 'Invoice', $invoice->id, $invoice->patient_id);

        // Authorization check
        if ($user->role === 'DOCTOR' && $invoice->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        if ($user->role === 'PATIENT' && $invoice->patient_id !== ($user->patient->id ?? null)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $invoice]);
    }

    public function update(UpdateInvoiceRequest $request, string $id): JsonResponse
    {
        $user = auth('user_api')->user();
        $invoice = Invoice::findOrFail($id);

        // Only DRAFT invoices can be updated
        if ($invoice->status !== 'DRAFT') {
            return response()->json(['error' => 'Cannot update invoice with status: ' . $invoice->status], 422);
        }

        $oldData = $invoice->toArray();
        $data = $request->validated();

        // Recalculate totals if items changed
        if (!empty($data['items'])) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemTotal = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                $subtotal += $itemTotal;
            }
            $data['subtotal'] = $subtotal;
            $data['total'] = $subtotal + ($data['tax'] ?? 0) - ($data['discount'] ?? 0);

            // Delete old items and create new ones
            $invoice->items()->delete();
            foreach ($data['items'] as $item) {
                $item['invoice_id'] = $invoice->id;
                $item['total'] = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                InvoiceItem::create($item);
            }
        }

        $invoice->update($data);

        // HIPAA: Log UPDATE
        AuditLog::logUpdate($user, 'Invoice', $invoice->id, $oldData, $data, $invoice->patient_id);

        return response()->json(['data' => $invoice->load(['patient', 'user', 'clinicBranch', 'items'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = auth('user_api')->user();
        $invoice = Invoice::findOrFail($id);

        // Only DRAFT invoices can be deleted (CASCADE will remove items and payments)
        if ($invoice->status !== 'DRAFT') {
            return response()->json(['error' => 'Cannot delete invoice with status: ' . $invoice->status], 422);
        }

        if ($user->role !== 'ADMIN' && $invoice->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $oldData = $invoice->toArray();
        $invoice->delete();

        // HIPAA: Log DELETE
        AuditLog::logDelete($user, 'Invoice', $id, $oldData);

        return response()->json(null, 204);
    }

    public function send(string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status !== 'DRAFT') {
            return response()->json(['error' => 'Can only send DRAFT invoices'], 422);
        }

        $invoice->update(['status' => 'SENT']);

        return response()->json(['data' => $invoice->load(['patient', 'items'])]);
    }
}
