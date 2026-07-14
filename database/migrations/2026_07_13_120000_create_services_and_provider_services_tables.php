<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('category'); // IMAGING, LAB, PROCEDURE, CONSULTATION, THERAPY, OTHER
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2)->default(0.00);
            $table->string('code')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('provider_services', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->unsignedBigInteger('provider_id');
            $table->string('provider_type'); // Polymorphic: App\Models\User (Doctor) o App\Models\ClinicBranch (Clinic)
            $table->decimal('price', 10, 2);
            $table->integer('duration_minutes');
            $table->boolean('is_standalone_bookable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('custom_name')->nullable();
            $table->text('custom_description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['provider_id', 'provider_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_services');
        Schema::dropIfExists('services');
    }
};
