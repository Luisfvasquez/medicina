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
        Schema::create('verification_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Usuario que sube el documento');
            
            $table->string('type');
            $table->string('file_url')->comment('Link al archivo en S3/Google Cloud');
            $table->string('status')->default(\App\Enums\VerificationStatus::PENDING->value);
            $table->text('comments')->nullable()->comment('Razón del rechazo si aplica');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_documents');
    }
};
