<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_offers', function (Blueprint $table) {
            // Change float to decimal(10,2) for precise monetary values
            // Step 1: Drop the float column
            $table->dropColumn('price');
            
            // Step 2: Add decimal column
            $table->decimal('price', 10, 2)->default(0)->after('provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('quote_offers', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->float('price')->default(0)->after('provider_id');
        });
    }
};
