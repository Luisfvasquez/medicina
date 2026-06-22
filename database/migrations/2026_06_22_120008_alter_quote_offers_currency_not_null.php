<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_offers', function (Blueprint $table) {
            // currency should be NOT NULL with default 'USD' per schema
            $table->string('currency')
                  ->nullable(false)
                  ->default('USD')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('quote_offers', function (Blueprint $table) {
            $table->string('currency')
                  ->nullable()
                  ->default(null)
                  ->change();
        });
    }
};
