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
        Schema::create('customer_invoice_items', function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('invoice_id')->nullable()->index('sale_id');
            $table->integer('product_id')->nullable()->index('product_id');
            $table->string('product_name', 200)->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('units')->default(1);
            $table->double('unit_price', 10, 2)->nullable();
            $table->double('gross_total', 10, 2)->nullable();
            $table->double('sold_price', 10, 2)->nullable();
            $table->date('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_invoice_items');
    }
};
