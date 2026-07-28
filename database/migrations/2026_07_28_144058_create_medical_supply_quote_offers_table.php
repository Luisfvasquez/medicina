<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medical_supply_quote_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_supply_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->json('items_detail')->nullable();
            $table->string('status')->default('PENDING'); // PENDING, ACCEPTED, REJECTED, FULFILLED
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_supply_quote_offers');
    }
};
