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
        Schema::create('reservations', function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('customer_id')->nullable();
            $table->string('name', 100)->nullable();
            $table->string('phone', 100)->nullable();
            $table->integer('guests')->nullable();
            $table->date('booking_date')->nullable();
            $table->time('booking_time')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->enum('status', ['Booked', 'Cancelled'])->default('Booked');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reservations');
    }
};
