<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MedicalSupply\SupplyDashboardController;
use App\Http\Controllers\MedicalSupply\SupplySettingController;
use App\Http\Controllers\MedicalSupply\SupplyStaffController;
use App\Http\Controllers\MedicalSupply\SupplyInventoryController;
use App\Http\Controllers\MedicalSupply\SupplyQuoteController;

Route::prefix('v1')->middleware(['auth:user_api', 'user.status'])->group(function () {
    // ─── MEDICAL SUPPLY MODULE ROUTES ───────────────────────
    Route::prefix('medical-supply')->group(function () {
        // Dashboard
        Route::get('dashboard/stats', [SupplyDashboardController::class, 'stats']);
        Route::get('dashboard/top-demanded', [SupplyDashboardController::class, 'topDemanded']);

        // Settings
        Route::get('settings', [SupplySettingController::class, 'show']);
        Route::put('settings', [SupplySettingController::class, 'update']);

        // Staff (Users/Roles)
        Route::get('staff', [SupplyStaffController::class, 'index']);
        Route::post('staff', [SupplyStaffController::class, 'store']);
        Route::delete('staff/{id}', [SupplyStaffController::class, 'destroy']);

        // Inventory
        Route::get('inventory', [SupplyInventoryController::class, 'index']);
        Route::post('inventory', [SupplyInventoryController::class, 'store']);
        Route::get('inventory/{id}', [SupplyInventoryController::class, 'show']);
        Route::put('inventory/{id}', [SupplyInventoryController::class, 'update']);
        Route::delete('inventory/{id}', [SupplyInventoryController::class, 'destroy']);

        // Quotes
        Route::post('quotes', [SupplyQuoteController::class, 'store']);
        Route::post('quotes/auto-match/{order_id}', [SupplyQuoteController::class, 'autoMatch']);
    });
});
