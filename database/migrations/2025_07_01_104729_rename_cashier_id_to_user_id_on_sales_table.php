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
    Schema::table('sales', function (Blueprint $table) {
        $table->renameColumn('cashier_id', 'user_id');
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
   public function down()
{
    Schema::table('sales', function (Blueprint $table) {
        $table->renameColumn('user_id', 'cashier_id');
    });
}
};
