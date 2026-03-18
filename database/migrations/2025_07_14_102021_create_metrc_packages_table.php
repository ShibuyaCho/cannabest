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
        Schema::create('metrc_packages', function (Blueprint $table) {
        $table->unsignedBigInteger('Id')->primary();
        $table->string('Label')->index();
        // add any other fields you care to cache, e.g. UnitWeight, ItemFromFacilityName, etc.
        $table->json('payload')->nullable(); // raw JSON if you want
        $table->timestamp('LastModified')->nullable();
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('metrc_packages');
    }
};
