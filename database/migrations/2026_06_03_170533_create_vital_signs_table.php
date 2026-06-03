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
        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('consultation_id')->unique()->constrained('consultations')->onDelete('cascade');
            
            $table->float('weight')->nullable();
            $table->float('height')->nullable();
            $table->integer('systolic_bp')->nullable();
            $table->integer('diastolic_bp')->nullable();
            $table->integer('heart_rate')->nullable();
            $table->float('temperature')->nullable();
            $table->integer('oxygen_sat')->nullable();
            
            $table->datetime('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vital_signs');
    }
};
