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
        Schema::create('medical_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->unique()->constrained('patients')->onDelete('cascade');
            
            $table->boolean('has_diabetes')->default(false);
            $table->boolean('has_hypertension')->default(false);
            $table->boolean('has_asthma')->default(false);
            $table->text('other_conditions')->nullable();
            $table->text('past_hospitalizations')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_backgrounds');
    }
};
