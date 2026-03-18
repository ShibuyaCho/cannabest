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
        Schema::table('metrc_test_results', function (Blueprint $table) {
            $table->string('LabFacilityName')->nullable()->after('TestResultLevel');
            $table->string('LabFacilityLicenseNumber')->nullable()->after('LabFacilityName');
            $table->dateTime('DateTested')->nullable()->after('LabFacilityLicenseNumber');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('metrc_test_results', function (Blueprint $table) {
            $table->dropColumn(['LabFacilityName', 'LabFacilityLicenseNumber', 'DateTested']);
        });
    }
};
