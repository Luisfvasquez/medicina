<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\SpecialtyController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\PublicCatalogController;
use App\Http\Controllers\Api\V1\Phase5\VerifyController;

Route::prefix('v1')->group(function () {
    Route::get('locations/countries', [LocationController::class, 'countries']);
    Route::get('locations/countries/{countryUuid}/cities', [LocationController::class, 'countryCities']);
    Route::get('locations/cities', [LocationController::class, 'cities']);
    Route::get('specialties', [SpecialtyController::class, 'index']);

    // Services routes
    Route::get('services/global', [ServiceController::class, 'globalIndex']);
    Route::get('services/provider/{providerUuid}', [ServiceController::class, 'providerIndex']);
    Route::get('services/provider/{providerUuid}/stats', [ServiceController::class, 'providerStats']);

    // Public Catalog (no auth required)
    Route::get('public/doctors', [PublicCatalogController::class, 'doctors']);
    Route::get('public/pharmacies', [PublicCatalogController::class, 'pharmacies']);
    Route::get('public/clinics', [PublicCatalogController::class, 'clinics']);
    Route::get('public/doctors/{doctor}/availability', [PublicCatalogController::class, 'doctorAvailability']);

    // Phase 5: Public Verification (no auth)
    Route::prefix('verify')->group(function () {
        Route::get('prescription/{publicToken}', [VerifyController::class, 'verifyPrescription']);
        Route::get('document/{publicToken}', [VerifyController::class, 'verifyDocument']);
    });
});
