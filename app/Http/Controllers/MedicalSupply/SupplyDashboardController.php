<?php

namespace App\Http\Controllers\MedicalSupply;

use App\Http\Controllers\Controller;
use App\Models\MedicalSupplyStaff;
use App\Models\MedicalSupplyQuoteOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplyDashboardController extends Controller
{
    /**
     * Get dashboard stats (Ventas, Tasa de Rechazo)
     */
    public function stats(Request $request)
    {
        $staff = MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();
        
        if ($staff->role->value !== 'MANAGER') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profileId = $staff->provider_profile_id;

        $totalQuotes = MedicalSupplyQuoteOffer::where('provider_profile_id', $profileId)->count();
        $acceptedQuotes = MedicalSupplyQuoteOffer::where('provider_profile_id', $profileId)->where('status', 'ACCEPTED')->count();
        $rejectedQuotes = MedicalSupplyQuoteOffer::where('provider_profile_id', $profileId)->where('status', 'REJECTED')->count();
        
        $salesUsd = MedicalSupplyQuoteOffer::where('provider_profile_id', $profileId)
            ->where('status', 'FULFILLED')
            ->where('currency', 'USD')
            ->sum('total_price');

        $salesBs = MedicalSupplyQuoteOffer::where('provider_profile_id', $profileId)
            ->where('status', 'FULFILLED')
            ->where('currency', 'BS')
            ->sum('total_price');

        return response()->json([
            'total_quotes' => $totalQuotes,
            'acceptance_rate' => $totalQuotes > 0 ? round(($acceptedQuotes / $totalQuotes) * 100, 2) . '%' : '0%',
            'rejection_rate' => $totalQuotes > 0 ? round(($rejectedQuotes / $totalQuotes) * 100, 2) . '%' : '0%',
            'total_sales_usd' => $salesUsd,
            'total_sales_bs' => $salesBs,
        ]);
    }

    /**
     * Get top demanded items.
     * Extracts items from the JSON items_detail of FULFILLED quotes.
     */
    public function topDemanded(Request $request)
    {
        $staff = MedicalSupplyStaff::where('user_id', $request->user()->id)->firstOrFail();
        $profileId = $staff->provider_profile_id;

        // In a real application with heavy traffic, parsing JSON in PHP or querying JSON in SQL might be slow.
        // For this implementation, we will query FULFILLED quotes and aggregate manually in memory, assuming low volume,
        // or we could use DB::raw to extract from JSON.

        $fulfilledQuotes = MedicalSupplyQuoteOffer::where('provider_profile_id', $profileId)
            ->where('status', 'FULFILLED')
            ->get();

        $itemCounts = [];

        foreach ($fulfilledQuotes as $quote) {
            $items = $quote->items_detail ?? [];
            foreach ($items as $item) {
                $name = $item['item_name'] ?? 'Unknown';
                if (!isset($itemCounts[$name])) {
                    $itemCounts[$name] = 0;
                }
                $itemCounts[$name]++;
            }
        }

        arsort($itemCounts);
        $topDemanded = array_slice($itemCounts, 0, 10, true);

        return response()->json($topDemanded);
    }
}
