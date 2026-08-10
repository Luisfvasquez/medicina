<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PharmacyInventory;
use App\Models\PharmacyOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PharmacyAnalyticsController extends Controller
{
    public function analytics(Request $request)
    {
        $user = $request->user();
        $providerId = $user->providerProfile->id ?? null;

        if (!$providerId) {
            return response()->json(['message' => 'No provider profile found'], 403);
        }

        $saleCondition = $request->query('sale_condition'); // free, prescription, controlled, all
        $expirationStatus = $request->query('expiration_status'); // valid, expiring, expired, all

        // Base query for inventory metrics
        $inventoryQuery = PharmacyInventory::where('provider_id', $providerId);

        if ($saleCondition && $saleCondition !== 'all') {
            $inventoryQuery->where('sale_condition', $saleCondition);
        }

        if ($expirationStatus && $expirationStatus !== 'all') {
            $now = Carbon::now();
            $in30Days = Carbon::now()->addDays(30);

            if ($expirationStatus === 'valid') {
                $inventoryQuery->where(function($q) use ($in30Days) {
                    $q->whereNull('expiration_date')
                      ->orWhere('expiration_date', '>', $in30Days);
                });
            } elseif ($expirationStatus === 'expiring') {
                $inventoryQuery->whereNotNull('expiration_date')
                      ->where('expiration_date', '<=', $in30Days)
                      ->where('expiration_date', '>=', $now);
            } elseif ($expirationStatus === 'expired') {
                $inventoryQuery->whereNotNull('expiration_date')
                      ->where('expiration_date', '<', $now);
            }
        }

        // 1. Overview Metrics
        // Clone query since we'll use it multiple times
        $totalItems = (clone $inventoryQuery)->count();
        
        $activeBatches = (clone $inventoryQuery)
            ->whereNotNull('batch_number')
            ->distinct('batch_number')
            ->count('batch_number');

        // Always show how many are expiring out of the filtered subset
        $expiringAlerts = (clone $inventoryQuery)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', Carbon::now()->addDays(30))
            ->where('expiration_date', '>=', Carbon::now())
            ->count();

        // Calculate Inventory Value
        $inventoryValue = (clone $inventoryQuery)
            ->sum(DB::raw('package_stock * COALESCE(unit_price, 0)'));

        // Processing Batches (Pending/In-preparation orders) - Unaffected by inventory filters
        $processingBatches = PharmacyOrder::where('provider_id', $providerId)
            ->whereIn('status', ['pending', 'en-preparacion', 'pendiente'])
            ->count();

        // 2. Stock Distribution (Sale Condition)
        $distributionRaw = (clone $inventoryQuery)
            ->select('sale_condition', DB::raw('count(*) as count'))
            ->groupBy('sale_condition')
            ->get();

        $stockDistribution = [];
        $colorMap = [
            'free' => '#10B981', // Emerald
            'prescription' => '#F59E0B', // Amber
            'controlled' => '#EF4444', // Red
        ];
        
        $nameMap = [
            'free' => 'Venta Libre',
            'prescription' => 'Con Récipe',
            'controlled' => 'Psicotrópicos',
        ];

        foreach ($distributionRaw as $dist) {
            $condition = $dist->sale_condition ?? 'free';
            $stockDistribution[] = [
                'name' => $nameMap[$condition] ?? 'Otros',
                'value' => (int) $dist->count,
                'color' => $colorMap[$condition] ?? '#94A3B8'
            ];
        }

        // 3. Top Brands (Laboratory)
        $brandsRaw = (clone $inventoryQuery)
            ->whereNotNull('laboratory')
            ->select('laboratory', DB::raw('count(*) as count'))
            ->groupBy('laboratory')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        $topBrands = [];
        foreach ($brandsRaw as $brand) {
            $percentage = $totalItems > 0 ? round(($brand->count / $totalItems) * 100) : 0;
            $topBrands[] = [
                'name' => $brand->laboratory,
                'count' => (int) $brand->count,
                'percentage' => $percentage,
            ];
        }

        // 4. Batch Ingestion over last 6 months
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
        
        // Fix for PostgreSQL EXTRACT function instead of MONTH()
        $dbDriver = DB::connection()->getDriverName();
        $monthExtraction = $dbDriver === 'pgsql' ? 'EXTRACT(MONTH FROM created_at)' : 'MONTH(created_at)';
        
        $ingestionRaw = (clone $inventoryQuery)
            ->where('created_at', '>=', $sixMonthsAgo)
            ->select(
                DB::raw("{$monthExtraction} as month"),
                DB::raw('count(DISTINCT batch_number) as lotes'),
                DB::raw('count(*) as productos')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $batchIngestion = [];
        $monthNames = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        // Fill empty months
        for ($i = 5; $i >= 0; $i--) {
            $targetMonth = Carbon::now()->subMonths($i)->month;
            $found = $ingestionRaw->firstWhere('month', $targetMonth);
            
            $batchIngestion[] = [
                'mes' => $monthNames[$targetMonth],
                'lotes' => $found ? (int) $found->lotes : 0,
                'productos' => $found ? (int) $found->productos : 0,
            ];
        }

        return response()->json([
            'overview' => [
                'inventory_value' => (float) $inventoryValue,
                'inventory_value_trend' => 0,
                'total_items' => $totalItems,
                'active_batches' => $activeBatches,
                'expiring_alerts' => $expiringAlerts,
                'expiring_days_threshold' => 30,
                'processing_batches' => $processingBatches,
                'pending_validations' => 0,
            ],
            'stock_distribution' => $stockDistribution,
            'top_brands' => $topBrands,
            'batch_ingestion' => $batchIngestion,
        ]);
    }
}
