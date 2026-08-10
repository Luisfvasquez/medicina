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
            'batch.documentUrls.*' => 'string',
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

        return DB::transaction(function () use ($validated, $providerId, $request) {
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
                
                // Track item explicitly in the batch items table
                $batch->batchItems()->create([
                    'medication_id' => $medicationId,
                    'stock' => (int)$item['stock'],
                    'batch_number' => $item['batchNumber'] ?? null,
                    'expiration_date' => $item['expirationDate'] ?? null,
                    'unit_price' => $item['unitPrice'] ?? null,
                    'active_ingredient' => $item['customActivePrinciple'] ?? null,
                    'laboratory' => $item['brandName'] ?? null,
                ]);

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

                    // Notify admins about the new medication request
                    $admins = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->get();
                    foreach ($admins as $admin) {
                        \App\Models\Notification::create([
                            'user_id' => $admin->id,
                            'type' => \App\Enums\NotifType::NEW_MEDICATION_REQUEST,
                            'title' => 'Nuevo Medicamento Detectado',
                            'message' => 'Una farmacia ha cargado un medicamento no catalogado: ' . ($item['customActivePrinciple'] ?? 'Desconocido') . ' - ' . ($item['brandName'] ?? 'Desconocido'),
                        ]);
                    }
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
                        'active_ingredient' => $item['customActivePrinciple'] ?? $inventory->active_ingredient,
                        'laboratory' => $item['brandName'] ?? $inventory->laboratory,
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
                        'active_ingredient' => $item['customActivePrinciple'] ?? null,
                        'laboratory' => $item['brandName'] ?? null,
                    ]));
                }
            }

            \App\Models\AuditLog::logCreate(
                $request->user(),
                'PharmacyInventoryBatch',
                $batch->id,
                ['document_urls' => $batch->document_urls, 'notes' => $batch->notes, 'items_count' => count($validated['items'])]
            );

            return response()->json([
                'message' => 'Lote guardado correctamente',
                'batch' => $batch->loadCount('batchItems')
            ], 201);
        });
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

    public function update(Request $request, string $uuid)
    {
        $providerId = $request->user()->providerProfile?->id;
        if (!$providerId) {
            return response()->json(['message' => 'Provider profile not found'], 403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'status' => 'nullable|string',
            'documentUrls' => 'nullable|array',
            'documentUrls.*' => 'string',
            'items' => 'nullable|array',
            'items.*.uuid' => 'nullable|string',
            'items.*.medicationId' => 'nullable|string',
            'items.*.customActivePrinciple' => 'nullable|string',
            'items.*.brandName' => 'nullable|string',
            'items.*.stock' => 'required_with:items|integer|min:0',
            'items.*.batchNumber' => 'nullable|string',
            'items.*.expirationDate' => 'nullable|date',
            'items.*.unitPrice' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $uuid, $providerId, $request) {
            $batch = PharmacyInventoryBatch::with('batchItems')->where('uuid', $uuid)->where('provider_id', $providerId)->firstOrFail();
            $oldData = $batch->toArray();
            
            $batch->update([
                'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $batch->notes,
                'status' => array_key_exists('status', $validated) ? $validated['status'] : $batch->status,
                'document_urls' => array_key_exists('documentUrls', $validated) ? $validated['documentUrls'] : $batch->document_urls,
            ]);

            if (isset($validated['items'])) {
                $incomingItemUuids = collect($validated['items'])->pluck('uuid')->filter()->toArray();
                
                // Identify deleted items
                foreach ($batch->batchItems as $existingItem) {
                    if (!in_array($existingItem->uuid, $incomingItemUuids)) {
                        $attributes = [
                            'provider_id' => $providerId,
                            'medication_id' => $existingItem->medication_id,
                            'batch_number' => $existingItem->batch_number,
                        ];
                        if (is_null($existingItem->medication_id)) {
                            $attributes['active_ingredient'] = $existingItem->active_ingredient;
                            $attributes['laboratory'] = $existingItem->laboratory;
                        }
                        
                        $inventory = PharmacyInventory::where($attributes)->first();
                        if ($inventory) {
                            $inventory->stock = max(0, $inventory->stock - $existingItem->stock);
                            $inventory->package_stock = max(0, $inventory->package_stock - $existingItem->stock);
                            $inventory->save();
                        }
                        
                        $existingItem->delete();
                    }
                }

                // Process updated / new items
                foreach ($validated['items'] as $itemData) {
                    $medicationId = null;
                    if (!empty($itemData['medicationId'])) {
                        $med = \App\Models\Medication::where('uuid', $itemData['medicationId'])->first();
                        if ($med) $medicationId = $med->id;
                    }

                    $batchItem = null;
                    $diffStock = 0;

                    if (!empty($itemData['uuid'])) {
                        $batchItem = $batch->batchItems()->where('uuid', $itemData['uuid'])->first();
                    }

                    if ($batchItem) {
                        $diffStock = (int)$itemData['stock'] - $batchItem->stock;
                        $batchItem->update([
                            'stock' => (int)$itemData['stock'],
                            'batch_number' => $itemData['batchNumber'] ?? null,
                            'expiration_date' => $itemData['expirationDate'] ?? null,
                            'unit_price' => $itemData['unitPrice'] ?? null,
                        ]);
                    } else {
                        // New item added to batch during edit
                        $diffStock = (int)$itemData['stock'];
                        $batchItem = $batch->batchItems()->create([
                            'medication_id' => $medicationId,
                            'stock' => (int)$itemData['stock'],
                            'batch_number' => $itemData['batchNumber'] ?? null,
                            'expiration_date' => $itemData['expirationDate'] ?? null,
                            'unit_price' => $itemData['unitPrice'] ?? null,
                            'active_ingredient' => $itemData['customActivePrinciple'] ?? null,
                            'laboratory' => $itemData['brandName'] ?? null,
                        ]);
                    }

                    // Update PharmacyInventory
                    $attributes = [
                        'provider_id' => $providerId,
                        'medication_id' => $medicationId,
                        'batch_number' => $itemData['batchNumber'] ?? null,
                    ];
                    if (is_null($medicationId)) {
                        $attributes['active_ingredient'] = $itemData['customActivePrinciple'] ?? null;
                        $attributes['laboratory'] = $itemData['brandName'] ?? null;
                    }
                    
                    $inventory = PharmacyInventory::where($attributes)->first();
                    if ($inventory) {
                        $inventory->stock = max(0, $inventory->stock + $diffStock);
                        $inventory->package_stock = max(0, $inventory->package_stock + $diffStock);
                        $inventory->unit_price = $itemData['unitPrice'] ?? $inventory->unit_price;
                        $inventory->expiration_date = $itemData['expirationDate'] ?? $inventory->expiration_date;
                        $prices_manual = $inventory->prices_manual ?? [];
                        if (isset($itemData['unitPrice'])) {
                            $prices_manual['VES'] = $itemData['unitPrice'];
                        }
                        $inventory->prices_manual = $prices_manual;
                        
                        $inventory->save();
                    } else {
                        PharmacyInventory::create(array_merge($attributes, [
                            'pharmacy_inventory_batch_id' => $batch->id,
                            'stock' => max(0, $diffStock),
                            'package_stock' => max(0, $diffStock),
                            'fraction_stock' => 0,
                            'min_stock_alert' => 10,
                            'expiration_date' => $itemData['expirationDate'] ?? null,
                            'unit_price' => $itemData['unitPrice'] ?? null,
                            'prices_manual' => ['USD' => 0, 'VES' => $itemData['unitPrice'] ?? 0],
                            'active_ingredient' => $itemData['customActivePrinciple'] ?? null,
                            'laboratory' => $itemData['brandName'] ?? null,
                        ]));
                    }
                }
            }

            \App\Models\AuditLog::logUpdate(
                $request->user(),
                'PharmacyInventoryBatch',
                $batch->id,
                $oldData,
                $batch->fresh('batchItems')->toArray()
            );

            return response()->json([
                'message' => 'Lote actualizado correctamente',
                'batch' => $batch->load('batchItems.medication')
            ]);
        });
    }
}
