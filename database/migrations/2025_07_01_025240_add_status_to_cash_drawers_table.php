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
      Schema::table('cash_drawers', function (Blueprint $table) {
    $table->enum('status', ['active', 'inactive'])->default('active')->after('name');
});
    }

    /**
     * Reverse the migrations.
     *php artisan migrate
     * @return void
     */
    public function down()
    {
        Schema::table('cash_drawers', function (Blueprint $table) {
            //
        });
    }
};
