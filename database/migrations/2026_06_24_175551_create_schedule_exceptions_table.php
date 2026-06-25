<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('exception_date');
            $table->enum('exception_type', ['VACATION', 'DAY_OFF', 'CUSTOM_HOURS']);
            $table->time('custom_start_time')->nullable();
            $table->time('custom_end_time')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'exception_date']);
            $table->index(['user_id', 'exception_date'], 'idx_schedule_exception_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_exceptions');
    }
};
