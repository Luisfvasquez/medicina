<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_id')->constrained('provider_profiles')->onDelete('cascade');
            
            $table->boolean('auto_quoting_enabled')->default(false); // Cotización manual vs automática
            $table->boolean('allow_partial_quotes')->default(true);
            $table->string('default_currency')->default('USD');
            $table->text('custom_terms')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_settings');
    }
};
