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
        Schema::create('inpatient_treatment_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_staff_id')->constrained('clinic_staff')->cascadeOnDelete();
            
            $table->text('note');
            $table->string('type')->default('progress'); // progress, nursing, physician
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inpatient_treatment_notes');
    }
};
