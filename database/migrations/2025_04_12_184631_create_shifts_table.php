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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cashier_id');
            $table->dateTime('shift_start_time');
            $table->dateTime('shift_stop_time')->nullable();
            $table->boolean('is_complete')->default(false);
            $table->string('status')->default('open'); // open, closed, cancelled
            $table->decimal('total_sales', 10, 2)->default(0.00); // optional field
            $table->timestamps();
            $table->foreign('cashier_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shifts');
    }
};
