<?php

namespace App\Services;

use App\Models\PharmacyInventoryBatch;
use App\Models\PharmacyInventory;
use App\Models\User;
use App\Models\Notification;
use App\Enums\UserRole;
use App\Enums\NotifType;
use Illuminate\Support\Facades\DB;

class PharmacyInventoryBatchService
{
    public function processBatch(int $providerId, array $validated, $user): PharmacyInventoryBatch
    {
        return DB::transaction(function () use ($validated, $providerId, $user) {
            $batchData = $validated['batch'] ?? [];
            
            $batch = PharmacyInventoryBatch::create([
                'provider_id' => $providerId,
                'document_urls' => $batchData['documentUrls'] ?? null,
                'notes' => $batchData['notes'] ?? null,
                'status' => 'PROCESSED',
            ]);

            foreach ($validated['items'] as $item) {
                $medicationId = null;
                if (!empty($item['medicationId'])) {
                    $medication = \App\Models\Medication::where('uuid', $item['medicationId'])->first();
                    if ($medication) {
                        $medicationId = $medication->id;
                    }
                }
                
                $batch->batchItems()->create([
                    'medication_id' => $medicationId,
                    'stock' => (int)$item['stock'],
                    'batch_number' => $item['batchNumber'] ?? null,
                    'expiration_date' => $item['expirationDate'] ?? null,
                    'unit_price' => $item['unitPrice'] ?? null,
                    'active_ingredient' => $item['customActivePrinciple'] ?? null,
                    'laboratory' => $item['brandName'] ?? null,
                ]);

                $attributes = [
                    'provider_id' => $providerId,
                    'medication_id' => $medicationId,
                    'batch_number' => $item['batchNumber'] ?? null,
                ];
                
                if (is_null($medicationId)) {
                    $attributes['active_ingredient'] = $item['customActivePrinciple'] ?? null;
                    $attributes['laboratory'] = $item['brandName'] ?? null;

                    $adminIds = User::where('role', UserRole::ADMIN)->pluck('id');
                    $now = now();
                    $notifications = $adminIds->map(function ($adminId) use ($item, $now) {
                        return [
                            'user_id' => $adminId,
                            'type' => NotifType::NEW_MEDICATION_REQUEST,
                            'title' => 'Nuevo Medicamento Detectado',
                            'message' => 'Una farmacia ha cargado un medicamento no catalogado: ' . ($item['customActivePrinciple'] ?? 'Desconocido') . ' - ' . ($item['brandName'] ?? 'Desconocido'),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })->toArray();
                    
                    if (!empty($notifications)) {
                        Notification::insert($notifications);
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
                        'min_stock_alert' => 10,
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

            return $batch->loadCount('batchItems');
        });
    }

    public function updateBatch(PharmacyInventoryBatch $batch, array $validated, $user): PharmacyInventoryBatch
    {
        return DB::transaction(function () use ($batch, $validated, $user) {
            $oldData = $batch->toArray();
            
            $batch->update([
                'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $batch->notes,
                'status' => array_key_exists('status', $validated) ? $validated['status'] : $batch->status,
                'document_urls' => array_key_exists('documentUrls', $validated) ? $validated['documentUrls'] : $batch->document_urls,
            ]);

            if (isset($validated['items'])) {
                $incomingItemUuids = collect($validated['items'])->pluck('uuid')->filter()->toArray();
                
                foreach ($batch->batchItems as $existingItem) {
                    if (!in_array($existingItem->uuid, $incomingItemUuids)) {
                        $attributes = [
                            'provider_id' => $batch->provider_id,
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

                    $attributes = [
                        'provider_id' => $batch->provider_id,
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

            return $batch->load('batchItems.medication');
        });
    }
}
