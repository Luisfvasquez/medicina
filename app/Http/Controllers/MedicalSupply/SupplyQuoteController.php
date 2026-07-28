<?php

namespace App\Http\Controllers\MedicalSupply;

use App\Http\Controllers\Controller;
use App\Models\MedicalSupplyQuoteOffer;
use App\Models\MedicalSupplyOrder;
use App\Models\MedicalSupplyStaff;
use App\Models\MedicalSupplySetting;
use App\Models\MedicalSupplyInventory;
use Illuminate\Http\Request;

class SupplyQuoteController extends Controller
{
    /**
     * Store a newly created quote manually.
     */
    public function store(Request $request)
    {
        $staff = MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'medical_supply_order_id' => 'required|exists:medical_supply_orders,id',
            'total_price' => 'required|numeric|min:0',
            'currency' => 'required|string|in:USD,BS,EUR',
            'items_detail' => 'nullable|array',
        ]);

        $quote = MedicalSupplyQuoteOffer::updateOrCreate(
            [
                'medical_supply_order_id' => $validated['medical_supply_order_id'],
                'provider_profile_id' => $staff->provider_profile_id,
            ],
            [
                'total_price' => $validated['total_price'],
                'currency' => $validated['currency'],
                'items_detail' => $validated['items_detail'],
                'status' => 'PENDING',
            ]
        );

        return response()->json($quote, 201);
    }

    /**
     * Auto match orders based on inventory. 
     * Usually called internally or via a cron/job when a new order comes in.
     */
    public function autoMatch(Request $request, $orderId)
    {
        $order = MedicalSupplyOrder::findOrFail($orderId);
        
        // Find providers with auto matching enabled
        $settings = MedicalSupplySetting::where('auto_matching_enabled', true)->get();
        $generatedQuotes = [];

        foreach ($settings as $setting) {
            $providerId = $setting->provider_profile_id;
            
            // Very simplified auto-matching logic for MVP:
            // Match items in order's supplies_list with inventory sku or name.
            $matchedTotal = 0;
            $itemsDetail = [];
            $allMatched = true;

            $requiredSupplies = $order->supplies_list ?? [];
            
            foreach ($requiredSupplies as $supplyName) {
                $inventoryItem = MedicalSupplyInventory::where('provider_profile_id', $providerId)
                    ->where('is_active', true)
                    ->where('stock', '>', 0)
                    ->where(function($q) use ($supplyName) {
                        $q->where('item_name', 'like', "%{$supplyName}%")
                          ->orWhere('sku', $supplyName);
                    })->first();

                if ($inventoryItem) {
                    $matchedTotal += $inventoryItem->price_usd; // Defaulting auto-match to USD
                    $itemsDetail[] = [
                        'item_name' => $inventoryItem->item_name,
                        'price' => $inventoryItem->price_usd,
                        'currency' => 'USD',
                        'inventory_id' => $inventoryItem->id
                    ];
                } else {
                    $allMatched = false;
                    break;
                }
            }

            if ($allMatched && count($requiredSupplies) > 0) {
                $quote = MedicalSupplyQuoteOffer::updateOrCreate(
                    [
                        'medical_supply_order_id' => $order->id,
                        'provider_profile_id' => $providerId,
                    ],
                    [
                        'total_price' => $matchedTotal,
                        'currency' => 'USD',
                        'items_detail' => $itemsDetail,
                        'status' => 'PENDING',
                    ]
                );
                $generatedQuotes[] = $quote;
            }
        }

        return response()->json(['message' => 'Auto-match completed', 'quotes_generated' => count($generatedQuotes)], 200);
    }
}
