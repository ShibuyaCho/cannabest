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
           // Only add the new fields that are not already present
    $table->string('UnitCbdContent')->nullable()->after('ProductLabel');
    $table->string('UnitThcContent')->nullable()->after('UnitCbdContent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn([
                 'UnitCbdContent', 'UnitThcContent'
            ]);
        });
    }
};
