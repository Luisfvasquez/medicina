<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_offer_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('quote_offer_id')->constrained('quote_offers')->onDelete('cascade');
            
            $table->foreignId('prescription_item_id')->nullable()->constrained('prescription_items')->onDelete('set null');
            $table->foreignId('pharmacy_inventory_id')->nullable()->constrained('pharmacy_inventories')->onDelete('set null'); // Nullable para cotización libre/ad-hoc
            $table->string('custom_product_name')->nullable(); // Para cotización ad-hoc sin inventario previo
            
            $table->boolean('is_substituted')->default(false);
            $table->foreignId('substituted_inventory_id')->nullable()->constrained('pharmacy_inventories')->onDelete('set null');
            $table->string('substitution_reason')->nullable();
            
            $table->enum('sell_format', ['package', 'fraction'])->default('package');
            $table->integer('quantity')->default(1);
            
            $table->json('prices_manual')->nullable(); // Precios ingresados manualmente {"VES": 200, "USD": 40, "EUR": 34}
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_offer_items');
    }
};
