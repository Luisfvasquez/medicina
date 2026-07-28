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
        Schema::create('hospital_beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospitalization_room_id')->constrained()->cascadeOnDelete();
            
            $table->string('bed_number');
            $table->string('status')->default('available'); // available, occupied, cleaning, maintenance
            $table->decimal('daily_rate', 10, 2)->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospital_beds');
    }
};
