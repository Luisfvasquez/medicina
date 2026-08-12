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

    protected \App\Services\PharmacyInventoryBatchService $batchService;

    public function __construct(\App\Services\PharmacyInventoryBatchService $batchService)
    {
        $this->batchService = $batchService;
    }

    public function store(\App\Http\Requests\Api\V1\PharmacyInventoryBatch\StorePharmacyInventoryBatchRequest $request)
    {
        $providerId = $request->user()->providerProfile?->id;

        if (!$providerId) {
            return response()->json(['message' => 'Provider profile not found'], 403);
        }

        $validated = $request->validated();
        $batch = $this->batchService->processBatch($providerId, $validated, $request->user());

        return response()->json([
            'message' => 'Lote guardado correctamente',
            'batch' => $batch
        ], 201);
    }

    public function show(Request $request, string $uuid)
    {
        $providerId = $request->user()->providerProfile?->id;
        if (!$providerId) {
            return response()->json(['message' => 'Provider profile not found'], 403);
        }

        $batch = PharmacyInventoryBatch::with(['batchItems.medication'])
            ->where('uuid', $uuid)
            ->where('provider_id', $providerId)
            ->firstOrFail();

        // Compatibility fix for frontend that expects 'items'
        $batchData = $batch->toArray();
        $batchData['items'] = $batchData['batch_items'] ?? [];

        return response()->json(['data' => $batchData]);
    }

    public function update(\App\Http\Requests\Api\V1\PharmacyInventoryBatch\UpdatePharmacyInventoryBatchRequest $request, string $uuid)
    {
        $providerId = $request->user()->providerProfile?->id;
        if (!$providerId) {
            return response()->json(['message' => 'Provider profile not found'], 403);
        }

        $validated = $request->validated();

        $batch = PharmacyInventoryBatch::with('batchItems')->where('uuid', $uuid)->where('provider_id', $providerId)->firstOrFail();
        
        $updatedBatch = $this->batchService->updateBatch($batch, $validated, $request->user());

        return response()->json([
            'message' => 'Lote actualizado correctamente',
            'batch' => $updatedBatch
        ]);
    }
}
