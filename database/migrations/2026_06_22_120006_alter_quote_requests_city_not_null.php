<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // city_id is already UUID in quote_requests - just make it NOT NULL
        // The column exists and is uuid type, we just need to add the NOT NULL constraint
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->uuid('city_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->uuid('city_id')->nullable()->change();
        });
    }
};
