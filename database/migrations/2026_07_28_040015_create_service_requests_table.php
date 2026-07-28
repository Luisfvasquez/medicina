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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_branch_id')->constrained()->cascadeOnDelete();
            
            // Quién lo solicita
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            
            // Qué servicio se solicita
            $table->foreignId('clinic_service_id')->constrained()->cascadeOnDelete();
            
            // A quién se le asigna (enfermera / doctor del staff de la clínica)
            $table->foreignId('assigned_staff_id')->nullable()->constrained('clinic_staff')->nullOnDelete();
            
            $table->string('status')->default('pending'); // pending, assigned, in_progress, completed, cancelled
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
