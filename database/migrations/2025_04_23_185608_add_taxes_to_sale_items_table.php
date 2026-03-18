<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('ExciseTax', 10, 2)->nullable();
            $table->decimal('CityTax', 10, 2)->nullable();
            $table->decimal('CountyTax', 10, 2)->nullable();
            $table->decimal('MunicipalTax', 10, 2)->nullable();
            $table->decimal('SalesTax', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['ExciseTax', 'CityTax', 'CountyTax', 'MunicipalTax']);
        });
    }
};
