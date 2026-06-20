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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->onDelete('set null');
            $table->foreignId('form_template_id')->nullable()->constrained('form_templates')->onDelete('set null')->comment('Saber con qué esquema se llenó esta historia');
            
            $table->datetime('date');
            
            $table->string('reason')->nullable();
            $table->text('physical_exam')->nullable();
            $table->string('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            
            $table->json('dynamic_data')->nullable()->comment('Aquí se guardan las respuestas (ej. {bpm: 85, perimetro: 35})');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
