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
        Schema::create('hospital_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hospital_bed_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admitting_doctor_id')->nullable()->constrained('clinic_staff')->nullOnDelete();
            
            $table->dateTime('admission_date');
            $table->dateTime('discharge_date')->nullable();
            
            $table->string('status')->default('admitted'); // admitted, discharged, transferred
            $table->text('reason_for_admission')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospital_admissions');
    }
};
