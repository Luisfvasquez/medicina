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
        Schema::create('quote_offers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('quote_request_id')->constrained('quote_requests')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('provider_profiles')->onDelete('cascade');
            
            $table->float('price');
            $table->string('currency')->default('USD');
            $table->string('availability')->nullable();
            $table->string('comments')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_offers');
    }
};
