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
        Schema::create('wholesale_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wholesale_order_id')->constrained('wholesale_orders')->onDelete('cascade');
            $table->foreignId('wholesale_product_id')->constrained('wholesale_products')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wholesale_order_items');
    }
};
