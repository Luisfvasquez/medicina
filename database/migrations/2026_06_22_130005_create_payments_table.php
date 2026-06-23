<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            
            $table->decimal('amount', 10, 2);
            $table->string('method'); // CASH, CARD, TRANSFER, INSURANCE, OTHER
            $table->string('reference')->nullable(); // Stripe/Zelle ID
            $table->timestamp('paid_at')->useCurrent();
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
