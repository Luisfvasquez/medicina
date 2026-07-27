<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            $table->foreignId('quote_offer_id')->nullable()->constrained('quote_offers')->onDelete('set null');
            $table->foreignId('provider_id')->constrained('provider_profiles')->onDelete('cascade');
            $table->foreignId('patient_account_id')->nullable()->constrained('patient_accounts')->onDelete('set null');
            
            $table->enum('status', ['pending', 'confirmed', 'dispensed', 'cancelled'])->default('pending');
            $table->json('selected_currency_payment')->nullable(); // Moneda y montos abonados
            $table->boolean('stock_deducted')->default(false); // Flag para asegurar que el descuento de stock ocurra SOLO al confirmar
            $table->timestamp('confirmed_at')->nullable();
            
            $table->timestamps();
        });

        Schema::create('pharmacy_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_order_id')->constrained('pharmacy_orders')->onDelete('cascade');
            $table->foreignId('pharmacy_inventory_id')->nullable()->constrained('pharmacy_inventories')->onDelete('set null');
            $table->string('product_name');
            $table->enum('sell_format', ['package', 'fraction'])->default('package');
            $table->integer('quantity');
            $table->json('unit_prices_manual')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_order_items');
        Schema::dropIfExists('pharmacy_orders');
    }
};
