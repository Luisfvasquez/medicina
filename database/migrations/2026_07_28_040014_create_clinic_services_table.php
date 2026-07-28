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
        Schema::create('clinic_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_branch_id')->constrained()->cascadeOnDelete();
            
            $table->string('name'); // e.g. "Sutura de 3 a 5 puntos", "Nebulización"
            $table->text('description')->nullable();
            
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            
            // Opcionalmente se puede asociar a un departamento
            $table->foreignId('clinic_department_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_services');
    }
};
