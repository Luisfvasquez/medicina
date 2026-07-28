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
        Schema::create('inpatient_medication_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_admission_id')->constrained()->cascadeOnDelete();
            
            // Medication can be a raw string or reference if there's a meds catalog
            $table->string('medication_name'); 
            $table->string('dosage');
            $table->string('route'); // oral, IV, etc.
            
            $table->dateTime('scheduled_time');
            $table->dateTime('administered_at')->nullable();
            
            $table->foreignId('administered_by')->nullable()->constrained('clinic_staff')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, administered, skipped
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inpatient_medication_schedules');
    }
};
