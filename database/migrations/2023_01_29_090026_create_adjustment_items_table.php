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
        Schema::create('adjustment_items', function (Blueprint $table) {
            $table->comment('');
            $table->increments('id');
            $table->integer('adjustment_id');
            $table->integer('product_id');
            $table->integer('adjustment');
            $table->integer('diff');
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
        Schema::dropIfExists('adjustment_items');
    }
};
