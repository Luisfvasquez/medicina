<?php

namespace App\Services;

use App\Models\PharmacyOrder;
use App\Models\PharmacyOrderItem;
use App\Models\PharmacyInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PharmacyOrderService
{
    /**
     * Confirmar orden de compra del cliente y ejecutar el descuento diferido de stock atómicamente
     */
    public function confirmOrder(int $orderId): PharmacyOrder
    {
        return DB::transaction(function () use ($orderId) {
            $order = PharmacyOrder::with('items')->findOrFail($orderId);

            if ($order->stock_deducted || $order->status === 'confirmed') {
                return $order;
            }

            // Descontar inventario únicamente para ítems asociados a inventario registrado
            foreach ($order->items as $item) {
                if ($item->pharmacy_inventory_id) {
                    $inventory = PharmacyInventory::find($item->pharmacy_inventory_id);
                    if ($inventory) {
                        $inventory->deductStock($item->quantity, $item->sell_format);
                    }
                }
            }

            $order->update([
                'status' => 'confirmed',
                'stock_deducted' => true,
                'confirmed_at' => now(),
            ]);

            return $order;
        });
    }
}
