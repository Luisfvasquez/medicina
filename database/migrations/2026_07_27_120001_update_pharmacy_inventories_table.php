<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            $table->string('ean_code')->nullable()->after('medication_id');
            $table->string('active_ingredient')->nullable()->after('ean_code'); // Monodroga
            $table->string('laboratory')->nullable()->after('active_ingredient');
            $table->enum('sale_condition', ['free', 'prescription', 'controlled'])->default('prescription')->after('laboratory');
            $table->string('location_rack')->nullable()->after('expiration_date');
            
            // Atributos de Fraccionamiento (Venta Detallada / Blíster / Unidad)
            $table->boolean('allows_fractioning')->default(false)->after('location_rack');
            $table->integer('units_per_package')->default(1)->after('allows_fractioning');
            $table->string('fraction_unit_name')->default('unidad')->after('units_per_package'); // Blíster, comprimido, ampolla
            $table->integer('package_stock')->default(0)->after('fraction_unit_name');
            $table->integer('fraction_stock')->default(0)->after('package_stock');
            
            // Precios Manuales Multimoneda (JSON con importes ingresados manualmente por la farmacia)
            $table->json('prices_manual')->nullable()->after('unit_price'); // {"VES": 200, "USD": 40, "EUR": 34, "fraction_VES": 20, "fraction_USD": 4}
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_inventories', function (Blueprint $table) {
            $table->dropColumn([
                'ean_code',
                'active_ingredient',
                'laboratory',
                'sale_condition',
                'location_rack',
                'allows_fractioning',
                'units_per_package',
                'fraction_unit_name',
                'package_stock',
                'fraction_stock',
                'prices_manual',
            ]);
        });
    }
};
