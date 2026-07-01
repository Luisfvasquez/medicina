<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('identifier'); // phone (E.164) o email
            $table->enum('channel', ['WHATSAPP', 'EMAIL']);
            $table->enum('role', ['DOCTOR', 'PATIENT', 'PROVIDER', 'ADMIN']);
            $table->string('code_hash'); // SHA256 del código plano
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['identifier', 'role'], 'otp_codes_identifier_role_index');
            $table->index(['expires_at'], 'otp_codes_expires_at_index');
        });

        Schema::create('otp_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('identifier');
            $table->enum('role', ['DOCTOR', 'PATIENT', 'PROVIDER', 'ADMIN']);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();

            $table->unique(['identifier', 'role'], 'otp_attempts_identifier_role_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('otp_attempts');
    }
};
