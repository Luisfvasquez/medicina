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
        Schema::create('obstetric_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->unique()->constrained('patients')->onDelete('cascade');
            
            $table->datetime('last_period_date')->nullable();
            $table->integer('pregnancies')->default(0);
            $table->integer('births')->default(0);
            $table->integer('cesareans')->default(0);
            $table->integer('abortions')->default(0);
            $table->string('contraceptive_method')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obstetric_histories');
    }
};
