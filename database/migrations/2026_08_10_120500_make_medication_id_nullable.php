<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            $table->dropForeign(['medication_id']);
            $table->unsignedBigInteger('medication_id')->nullable()->change();
            $table->foreign('medication_id')->references('id')->on('medications')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            $table->dropForeign(['medication_id']);
            $table->unsignedBigInteger('medication_id')->nullable(false)->change();
            $table->foreign('medication_id')->references('id')->on('medications')->onDelete('cascade');
        });
    }
};
