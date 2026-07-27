<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_appointments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('lab_quote_offer_id')->nullable()->constrained('lab_quote_offers')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('patient_account_id')->nullable()->constrained('patient_accounts')->nullOnDelete();
            $table->foreignId('provider_profile_id')->nullable()->constrained('provider_profiles')->nullOnDelete();
            $table->date('scheduled_date');
            $table->string('time_slot')->nullable();
            $table->enum('status', ['reserved', 'confirmed', 'sample_taken', 'completed', 'cancelled'])->default('reserved');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_appointments');
    }
};
