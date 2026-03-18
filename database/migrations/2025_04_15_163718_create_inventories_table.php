<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventories', function (Blueprint $table) {
            
            $table->string('package_id')->nullable();
            $table->decimal('unit_cbd_percent', 5)->nullable();
            $table->decimal('unit_thc_percent', 5)->nullable();
            $table->decimal('unit_volume', 10)->nullable();
            $table->decimal('unit_weight', 10)->nullable();
            $table->string('serving_size')->nullable();
           
           
            $table->timestamp('SalesDateTime')->useCurrent();
            $table->string('ExternalReceiptNumber', 50);
            $table->string('SalesCustomerType', 50);
            $table->string('PatientLicenseNumber', 50);
            $table->string('CaregiverLicenseNumber', 50);
            $table->string('IdentificationMethod', 50);
            $table->string('PatientRegistrationLocationId', 50);
            $table->string('TotalAmount', 50);
            $table->string('UnitThcContentUnitOfMeasure', 50);
            $table->string('UnitWeightUnitOfMeasure', 50);
            $table->string('InvoiceNumber', 50);
            $table->decimal('ExciseTax');
            $table->decimal('CityTax');
            $table->decimal('CountyTax');
            $table->decimal('MunicipalTax');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventories');
    }
};
