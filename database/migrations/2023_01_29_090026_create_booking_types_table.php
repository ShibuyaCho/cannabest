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
        Schema::create('booking_types', function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->string('name');
            $table->double('price', 10, 2);
            $table->integer('qty')->default(0);
            $table->double('hourly_price', 10, 2)->default(0);
            $table->integer('hours')->default(1);
            $table->enum('type', ['fixed', 'hourly', 'daily', 'weekly', 'monthly']);
            $table->integer('available')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('booking_types');
    }
};
