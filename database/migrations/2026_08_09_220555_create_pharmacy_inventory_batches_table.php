<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_id')->constrained('provider_profiles')->onDelete('cascade');
            $table->json('document_urls')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('PROCESSED');
            $table->timestamps();
            
            $table->index(['provider_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_inventory_batches');
    }
};
