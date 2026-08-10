<?php

$batches = App\Models\PharmacyInventoryBatch::all();

foreach ($batches as $batch) {
    // Check if it already has items
    if ($batch->batchItems()->count() > 0) {
        continue;
    }

    // Get old items from PharmacyInventory
    $oldItems = App\Models\PharmacyInventory::where('pharmacy_inventory_batch_id', $batch->id)->get();

    foreach ($oldItems as $item) {
        App\Models\PharmacyInventoryBatchItem::create([
            'pharmacy_inventory_batch_id' => $batch->id,
            'medication_id' => $item->medication_id,
            'stock' => $item->stock,
            'batch_number' => $item->batch_number,
            'expiration_date' => $item->expiration_date,
            'unit_price' => $item->unit_price, // Or fetch from prices_manual
            'active_ingredient' => $item->active_ingredient,
            'laboratory' => $item->laboratory,
        ]);
    }
}

echo "Backfill completed.\n";
