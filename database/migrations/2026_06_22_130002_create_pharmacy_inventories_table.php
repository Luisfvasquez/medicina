<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_inventories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            $table->foreignId('provider_id')->constrained('provider_profiles')->onDelete('cascade');
            $table->foreignId('medication_id')->constrained('medications')->onDelete('cascade');
            
            $table->integer('stock')->default(0);
            $table->integer('min_stock_alert')->default(10);
            $table->string('batch_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            
            $table->timestamps();
            
            // Unique constraint: no duplicate batches for same provider/medication
            $table->unique(['provider_id', 'medication_id', 'batch_number'], 'pharmacy_inventory_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_inventories');
    }
};
