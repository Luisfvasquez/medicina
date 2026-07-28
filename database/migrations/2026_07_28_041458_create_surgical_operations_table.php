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
        Schema::create('surgical_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            
            // Link to the service catalog
            $table->foreignId('clinic_service_id')->constrained()->cascadeOnDelete();
            
            // Might be linked to an admission or independent
            $table->foreignId('hospital_admission_id')->nullable()->constrained()->nullOnDelete();
            
            // Scheduling
            $table->dateTime('scheduled_at');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled
            
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
        Schema::dropIfExists('surgical_operations');
    }
};
