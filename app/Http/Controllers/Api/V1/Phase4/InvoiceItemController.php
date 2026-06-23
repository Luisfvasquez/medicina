<?php

namespace App\Http\Controllers\Api\V1\Phase4;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\InvoiceItem\StoreInvoiceItemRequest;
use App\Models\InvoiceItem;
use Illuminate\Http\JsonResponse;

class InvoiceItemController extends Controller
{
    public function index(string $invoiceId): JsonResponse
    {
        $items = InvoiceItem::where('invoice_id', $invoiceId)->get();

        return response()->json(['data' => $items]);
    }

    public function store(StoreInvoiceItemRequest $request, string $invoiceId): JsonResponse
    {
        $data = $request->validated();
        $data['invoice_id'] = $invoiceId;
        $data['total'] = ($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0);

        $item = InvoiceItem::create($data);

        return response()->json(['data' => $item], 201);
    }

    public function show(string $invoiceId, string $id): JsonResponse
    {
        $item = InvoiceItem::where('invoice_id', $invoiceId)->findOrFail($id);

        return response()->json(['data' => $item]);
    }

    public function destroy(string $invoiceId, string $id): JsonResponse
    {
        $item = InvoiceItem::where('invoice_id', $invoiceId)->findOrFail($id);

        // Only allow if parent invoice is DRAFT
        if ($item->invoice->status !== 'DRAFT') {
            return response()->json(['error' => 'Cannot delete item from non-DRAFT invoice'], 422);
        }

        $item->delete();

        return response()->json(null, 204);
    }
}
