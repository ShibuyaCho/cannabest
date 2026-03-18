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
            $table->unique(['organization_id', 'Label'], 'inventories_org_label_unique');
        });
    }

    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropUnique('inventories_org_label_unique');
        });
    }
};
