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
        Schema::create('hold_order', function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('company_id')->nullable();
            $table->integer('room_id')->nullable();
            $table->integer('table_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('comment')->nullable();
            $table->string('customer_name')->nullable();
            $table->text('cart')->nullable();
            $table->integer('status')->default(0);
            $table->integer('total_amount')->default(0);
            $table->double('discount', 10, 2)->nullable();
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
        Schema::dropIfExists('hold_order');
    }
};
