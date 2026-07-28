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
        Schema::create('service_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_admission_id')->nullable()->constrained()->cascadeOnDelete();
            
            // This can also be used for ambulatory if we link to patient directly, but design says admission/EHR
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_service_id')->constrained()->cascadeOnDelete();
            
            $table->decimal('amount', 10, 2);
            $table->integer('quantity')->default(1);
            
            $table->foreignId('charged_by')->nullable()->constrained('clinic_staff')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_charges');
    }
};
