<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Phase3\QuoteRequestController;
use App\Http\Controllers\Api\V1\Phase3\QuoteOfferController;

Route::prefix('v1')->middleware(['auth:user_api', 'user.status'])->group(function () {
    // Phase 3: Quote Requests
    Route::get('quote-requests', [QuoteRequestController::class, 'index']);
    Route::post('quote-requests', [QuoteRequestController::class, 'store'])->middleware('idempotent');
    Route::get('quote-requests/{quote_request}', [QuoteRequestController::class, 'show']);
    Route::put('quote-requests/{quote_request}', [QuoteRequestController::class, 'update']);
    Route::patch('quote-requests/{quote_request}', [QuoteRequestController::class, 'update']);
    Route::delete('quote-requests/{quote_request}', [QuoteRequestController::class, 'destroy']);

    // Phase 3: Quote Offers (nested under quote-requests)
    Route::get('quote-requests/{quote_request}/offers', [QuoteOfferController::class, 'index']);
    Route::post('quote-requests/{quote_request}/offers', [QuoteOfferController::class, 'store'])->middleware('idempotent');
    Route::get('quote-requests/{quote_request}/offers/{offer}', [QuoteOfferController::class, 'show']);
    Route::put('quote-requests/{quote_request}/offers/{offer}', [QuoteOfferController::class, 'update']);
    Route::patch('quote-requests/{quote_request}/offers/{offer}', [QuoteOfferController::class, 'update']);
    Route::delete('quote-requests/{quote_request}/offers/{offer}', [QuoteOfferController::class, 'destroy']);
});
