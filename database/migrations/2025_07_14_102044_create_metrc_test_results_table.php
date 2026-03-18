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
         Schema::create('metrc_test_results', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('PackageId')->index();
        $table->string('TestTypeName');
        $table->decimal('TestResultLevel', 10, 2);
        // other fields as needed...
        $table->foreign('PackageId')->references('Id')->on('metrc_packages')->cascadeOnDelete();
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('metrc_test_results');
    }
};
