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
        Schema::create('specialties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_specialty', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Dado que Users usa id() auto-incremental por defecto en Laravel, pero el script
            // de LUCA dice UUID. Si users ya usa BIGINT en Laravel, lo enlazaremos así:
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('specialty_id')->constrained('specialties')->onDelete('cascade');
            $table->unique(['user_id', 'specialty_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_specialty');
        Schema::dropIfExists('specialties');
    }
};
