<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PharmacyInventoryBatch;
use App\Models\PharmacyInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmacyInventoryBatchController extends Controller
{
    public function index(Request $request)
    {
        $providerId = $request->user()->providerProfile?->id;

        if (!$providerId) {
            return response()->json(['message' => 'Provider profile not found'], 403);
        }

        $batches = PharmacyInventoryBatch::where('provider_id', $providerId)
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($batches);
    }

    public function metrics(Request $request)
    {
        $providerId = $request->user()->providerProfile?->id;

        if (!$providerId) {
            return response()->json(['message' => 'Provider profile not found'], 403);
        }

        $now = \Carbon\Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // 1. Lotes Ingresados (Mes)
        $currentMonthBatches = PharmacyInventoryBatch::where('provider_id', $providerId)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $lastMonthBatches = PharmacyInventoryBatch::where('provider_id', $providerId)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();
            
        $batchDiff = $currentMonthBatches - $lastMonthBatches;
        $batchTrend = $batchDiff >= 0 ? "+$batchDiff respecto mes anterior" : "$batchDiff respecto mes anterior";

        // 2. Productos Cargados
        $totalProducts = PharmacyInventory::where('provider_id', $providerId)->count();

        // 3. Valor de Inventario (considerando precios_manual o unit_price por defecto)
        $inventoryValue = PharmacyInventory::where('provider_id', $providerId)
            ->selectRaw("SUM(stock * COALESCE(CAST(prices_manual->>'VES' AS NUMERIC), unit_price, 0)) as total")
            ->value('total') ?? 0;

        return response()->json([
            'batches_this_month' => [
                'value' => $currentMonthBatches,
                'trend' => $batchTrend,
            ],
            'total_products' => [
                'value' => number_format($totalProducts),
                'trend' => 'Total acumulado',
            ],
            'inventory_value' => [
                'value' => 'Bs ' . number_format($inventoryValue, 2, ',', '.'),
                'trend' => 'Valor actualizado',
            ]
        ]);
    }

    public function store(Request $request)
    {
        $providerId = $request->user()->providerProfile?->id;

        if (!$providerId) {
            return response()->json(['message' => 'Provider profile not found'], 403);
        }

        $validated = $request->validate([
            'batch.documentUrls' => 'nullable|array',
            'batch.documentUrls.*' => 'url',
            'batch.notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.medicationId' => 'nullable|string|exists:medications,uuid',
            'items.*.customActivePrinciple' => 'nullable|string',
            'items.*.brandName' => 'nullable|string',
            'items.*.stock' => 'required|integer|min:0',
            'items.*.batchNumber' => 'nullable|string',
            'items.*.expirationDate' => 'nullable|date',
            'items.*.unitPrice' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $providerId) {
            $batchData = $validated['batch'] ?? [];
            
            $batch = PharmacyInventoryBatch::create([
                'provider_id' => $providerId,
                'document_urls' => $batchData['documentUrls'] ?? null,
                'notes' => $batchData['notes'] ?? null,
                'status' => 'PROCESSED',
            ]);

            foreach ($validated['items'] as $item) {
                // Determine medication_id from uuid if provided
                $medicationId = null;
                if (!empty($item['medicationId'])) {
                    $medication = \App\Models\Medication::where('uuid', $item['medicationId'])->first();
                    if ($medication) {
                        $medicationId = $medication->id;
                    }
                }

                // If medication exists for same provider & batch number, update it.
                // Otherwise create it.
                
                // For PharmacyInventory we need medication_id, provider_id, and batch_number as unique identifiers
                $attributes = [
                    'provider_id' => $providerId,
                    'medication_id' => $medicationId,
                    'batch_number' => $item['batchNumber'] ?? null,
                ];
                
                // When custom principles are used, medicationId is null. In this case, we need to match by active_ingredient/brand name too
                if (is_null($medicationId)) {
                    $attributes['active_ingredient'] = $item['customActivePrinciple'] ?? null;
                    $attributes['laboratory'] = $item['brandName'] ?? null;
                }

                $inventory = PharmacyInventory::where($attributes)->first();
                
                if ($inventory) {
                    $inventory->update([
                        'pharmacy_inventory_batch_id' => $batch->id,
                        'stock' => $inventory->stock + (int)$item['stock'],
                        'package_stock' => $inventory->package_stock + (int)$item['stock'],
                        'expiration_date' => $item['expirationDate'] ?? $inventory->expiration_date,
                        'unit_price' => $item['unitPrice'] ?? $inventory->unit_price,
                        'prices_manual' => [
                            'USD' => $inventory->prices_manual['USD'] ?? 0,
                            'VES' => $item['unitPrice'] ?? ($inventory->prices_manual['VES'] ?? 0),
                        ],
                    ]);
                } else {
                    PharmacyInventory::create(array_merge($attributes, [
                        'pharmacy_inventory_batch_id' => $batch->id,
                        'stock' => (int)$item['stock'],
                        'package_stock' => (int)$item['stock'],
                        'fraction_stock' => 0,
                        'min_stock_alert' => 10, // default
                        'expiration_date' => $item['expirationDate'] ?? null,
                        'unit_price' => $item['unitPrice'] ?? null,
                        'prices_manual' => [
                            'USD' => 0,
                            'VES' => $item['unitPrice'] ?? 0,
                        ],
                    ]));
                }
            }

            return response()->json([
                'message' => 'Lote guardado correctamente',
                'batch' => $batch->loadCount('items')
            ], 201);
        });
    }
}
