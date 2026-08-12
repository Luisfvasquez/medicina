<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Phase4\InvoiceController;
use App\Http\Controllers\Api\V1\Phase4\InvoiceItemController;
use App\Http\Controllers\Api\V1\Phase4\PaymentController;
use App\Http\Controllers\Api\V1\Phase5\PdfExportController;

Route::prefix('v1')->middleware(['auth:user_api', 'user.status'])->group(function () {
    // Phase 4: Invoices
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::post('invoices', [InvoiceController::class, 'store'])->middleware('idempotent');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::put('invoices/{invoice}', [InvoiceController::class, 'update']);
    Route::patch('invoices/{invoice}', [InvoiceController::class, 'update']);
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy']);
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
    Route::get('invoices/{invoice}/pdf', [PdfExportController::class, 'invoice']);

    // Phase 4: Invoice Items (nested under invoices)
    Route::get('invoices/{invoice}/items', [InvoiceItemController::class, 'index']);
    Route::post('invoices/{invoice}/items', [InvoiceItemController::class, 'store'])->middleware('idempotent');
    Route::get('invoices/{invoice}/items/{item}', [InvoiceItemController::class, 'show']);
    Route::delete('invoices/{invoice}/items/{item}', [InvoiceItemController::class, 'destroy']);

    // Phase 4: Payments (nested under invoices)
    Route::get('invoices/{invoice}/payments', [PaymentController::class, 'index']);
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->middleware('idempotent');
    Route::get('invoices/{invoice}/payments/{payment}', [PaymentController::class, 'show']);
    Route::delete('invoices/{invoice}/payments/{payment}', [PaymentController::class, 'destroy']);
});
