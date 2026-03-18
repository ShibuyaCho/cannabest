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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->comment('');
            $table->increments('id');
            $table->integer('company_id')->nullable();
            $table->integer('sale_id');
            $table->integer('product_id');
            $table->decimal('price');
            $table->integer('quantity');
            $table->integer('units')->nullable()->default(1);
            $table->integer('p_qty')->default(0);
            $table->string('size', 20)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sale_items');
    }
};
