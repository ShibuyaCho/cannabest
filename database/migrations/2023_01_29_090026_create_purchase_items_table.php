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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('purchase_id')->nullable()->index('sale_id');
            $table->integer('product_id')->nullable()->index('product_id');
            $table->integer('quantity')->nullable();
            $table->integer('units')->default(1);
            $table->double('unit_price', 10, 2)->nullable();
            $table->double('gross_total', 10, 2)->nullable();
            $table->double('sold_price', 10, 2)->nullable();
            $table->date('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_items');
    }
};
