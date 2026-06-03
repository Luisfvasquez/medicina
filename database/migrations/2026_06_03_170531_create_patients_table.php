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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Dueño del expediente (Doctor)');
            $table->foreignId('patient_account_id')->constrained('patient_accounts')->onDelete('cascade')->comment('Link a Cuenta Global');
            
            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_id')->nullable();
            $table->datetime('birth_date');
            $table->string('gender')->default(\App\Enums\Gender::OTHER->value);
            
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            
            $table->string('access_code')->unique()->nullable();
            $table->datetime('last_login')->nullable();
            
            $table->string('blood_type')->nullable();
            $table->string('allergies')->nullable();
            $table->string('chronic_conditions')->nullable();
            $table->text('private_notes')->nullable()->comment('Solo visible por el doctor');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
