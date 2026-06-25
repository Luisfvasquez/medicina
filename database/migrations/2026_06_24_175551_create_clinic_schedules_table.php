<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('clinic_branch_id')->constrained()->onDelete('cascade');
            $table->enum('weekday', ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY']);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['clinic_branch_id', 'weekday']);
            $table->index(['clinic_branch_id', 'weekday'], 'idx_clinic_schedule_branch_weekday');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_schedules');
    }
};
