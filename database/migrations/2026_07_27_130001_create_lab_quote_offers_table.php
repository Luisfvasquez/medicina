<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_quote_offers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('lab_request_id')->nullable()->constrained('lab_requests')->nullOnDelete();
            $table->foreignId('provider_profile_id')->nullable()->constrained('provider_profiles')->nullOnDelete();
            $table->decimal('total_price_base', 10, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->json('prices_manual')->nullable();
            $table->json('items_detail')->nullable();
            $table->text('comments')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_quote_offers');
    }
};
