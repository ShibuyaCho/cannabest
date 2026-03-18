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
            // 1) type of discount (fixed vs percent)
            $table->string('order_discount_type')
                  ->nullable();
                 

            // 2) numeric discount amount
            $table->decimal('order_discount_value', 15, 2)
                  ->default(0)
                  ->after('order_discount_type');

            // 3) optional reason
            $table->text('order_discount_reason')
                  ->nullable()
                  ->after('order_discount_value');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'order_discount_reason',
                'order_discount_value',
                'order_discount_type',
            ]);
        });
    }
};
