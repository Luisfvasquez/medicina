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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('consultation_id')->unique()->nullable()->constrained('consultations')->onDelete('set null');
            $table->foreignId('clinic_id')->nullable();
            
            $table->datetime('date');
            $table->datetime('expiration_date')->nullable();
            $table->string('notes')->nullable();
            
            $table->string('public_token')->unique();
            $table->string('status')->default(\App\Enums\RxStatus::ACTIVE->value);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
