<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_inventory_batch_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pharmacy_inventory_batch_id')->constrained('pharmacy_inventory_batches')->onDelete('cascade');
            $table->foreignId('medication_id')->nullable()->constrained('medications')->onDelete('set null');
            
            $table->integer('stock');
            $table->string('batch_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            
            $table->string('active_ingredient')->nullable();
            $table->string('laboratory')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_inventory_batch_items');
    }
};
