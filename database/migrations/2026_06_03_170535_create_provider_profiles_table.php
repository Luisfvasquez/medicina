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
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            
            $table->string('type')->comment('Farmacia o Laboratorio');
            $table->string('commercial_name')->nullable();
            $table->string('rif')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_open')->default(false);
            $table->boolean('is_verified')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_profiles');
    }
};
