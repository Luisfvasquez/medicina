<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Phase4\PharmacyInventoryController;
use App\Http\Controllers\Api\V1\PharmacyInventoryBatchController;
use App\Http\Controllers\Api\V1\PharmacySettingsController;
use App\Http\Controllers\Api\V1\PharmacyInventoryController as V1PharmacyInventoryController;
use App\Http\Controllers\Api\V1\PharmacyQuoteController;
use App\Http\Controllers\Api\V1\PharmacyDashboardController;
use App\Http\Controllers\Api\V1\PharmacyAnalyticsController;
use App\Http\Controllers\Api\V1\PharmacyOrderController;

Route::prefix('v1')->middleware(['auth:user_api', 'user.status'])->group(function () {
    // Phase 4: Pharmacy Inventory (alerts BEFORE wildcard to avoid route shadowing)
    Route::get('pharmacy-inventories', [PharmacyInventoryController::class, 'index']);
    Route::post('pharmacy-inventories', [PharmacyInventoryController::class, 'store'])->middleware('idempotent');
    Route::get('pharmacy-inventories/alerts/low-stock', [PharmacyInventoryController::class, 'lowStockAlerts']);
    Route::get('pharmacy-inventories/alerts/expired', [PharmacyInventoryController::class, 'expired']);
    
    // Batches & Invoices
    Route::get('pharmacy/inventory/batches/metrics', [PharmacyInventoryBatchController::class, 'metrics']);
    Route::get('pharmacy/inventory/batches', [PharmacyInventoryBatchController::class, 'index']);
    Route::post('pharmacy/inventory/batches', [PharmacyInventoryBatchController::class, 'store'])->middleware('idempotent');
    Route::get('pharmacy/inventory/batches/{batch}', [PharmacyInventoryBatchController::class, 'show']);
    Route::put('pharmacy/inventory/batches/{batch}', [PharmacyInventoryBatchController::class, 'update']);

    Route::get('pharmacy-inventories/{pharmacy_inventory}', [PharmacyInventoryController::class, 'show']);
    Route::put('pharmacy-inventories/{pharmacy_inventory}', [PharmacyInventoryController::class, 'update']);
    Route::patch('pharmacy-inventories/{pharmacy_inventory}', [PharmacyInventoryController::class, 'update']);
    Route::delete('pharmacy-inventories/{pharmacy_inventory}', [PharmacyInventoryController::class, 'destroy']);

    // ─── PHARMAKO / PHARMACY MODULE ROUTES ───────────────────
    Route::prefix('pharmacy')->group(function () {
        // Settings (Manual vs Auto quoting mode & Schedule)
        Route::get('settings', [PharmacySettingsController::class, 'show']);
        Route::put('settings', [PharmacySettingsController::class, 'update']);

        // Inventory & Special Reports
        Route::get('inventory', [V1PharmacyInventoryController::class, 'index']);
        Route::post('inventory', [V1PharmacyInventoryController::class, 'store']);
        Route::put('inventory/{id}', [V1PharmacyInventoryController::class, 'update']);
        Route::get('inventory/reports/expirations', [V1PharmacyInventoryController::class, 'expirationsReport']);
        Route::get('inventory/reports/controlled-books', [V1PharmacyInventoryController::class, 'controlledBookReport']);

        // Quote Requests & Offers (Ad-hoc, manual substitution, multi-currency)
        Route::get('quote-requests', [PharmacyQuoteController::class, 'indexRequests']);
        Route::post('quote-requests/{id}/offers', [PharmacyQuoteController::class, 'storeOffer']);
        Route::put('quote-requests/{id}/offers/{offerId}', [PharmacyQuoteController::class, 'updateOffer']);
        Route::get('upsell-suggestions', [PharmacyQuoteController::class, 'upsellSuggestions']);

        // Pharmacy Dashboard Summary & Analytics
        Route::get('dashboard/summary', [PharmacyDashboardController::class, 'summary']);
        Route::get('analytics', [PharmacyAnalyticsController::class, 'analytics']);

        // Purchase Order & Deferred Stock Deduction
        Route::post('orders/{id}/confirm-purchase', [PharmacyOrderController::class, 'confirmPurchase']);
    });
});
