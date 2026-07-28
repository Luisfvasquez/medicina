<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upsell_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_id')->constrained('provider_profiles')->onDelete('cascade');
            
            $table->string('trigger_active_ingredient')->nullable(); // Monodroga gatillo (ej: amoxicilina)
            $table->foreignId('recommended_inventory_id')->constrained('pharmacy_inventories')->onDelete('cascade'); // Producto OTC recomendado
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->string('recommendation_reason')->nullable(); // Ej: "Probiótico recomendado al tomar antibiótico"
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upsell_rules');
    }
};
