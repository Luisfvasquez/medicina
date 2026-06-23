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
        Schema::create('patient_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('phone')->unique()->comment('Identificador global (WhatsApp)');
            $table->string('email')->nullable()->unique();
            $table->string('password_hash')->nullable();
            $table->string('full_name');
            $table->string('national_id')->nullable()->unique();
            $table->string('username')->nullable()->unique();
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null');
            $table->string('avatar_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_accounts');
    }
};
