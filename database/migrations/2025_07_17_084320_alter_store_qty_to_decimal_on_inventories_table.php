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
            // change from unsignedBigInteger to decimal(10,2)
            $table->decimal('storeQty', 10, 2)
                  ->default(0)
                  ->change();
        });
    }

    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            // revert back to unsignedBigInteger
            $table->unsignedBigInteger('storeQty')
                  ->default(0)
                  ->change();
        });
    }
};
