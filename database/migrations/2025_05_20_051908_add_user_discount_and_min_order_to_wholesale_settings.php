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
        Schema::table('wholesale_settings', function (Blueprint $table) {
            $table->decimal('user_discount_percentage', 5, 2)->nullable()->after('value');
            $table->decimal('min_order_amount', 10, 2)->nullable()->after('user_discount_percentage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wholesale_settings', function (Blueprint $table) {
            $table->dropColumn(['user_discount_percentage', 'min_order_amount']);
        });
    }
};
