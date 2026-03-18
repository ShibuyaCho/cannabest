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
        Schema::create('bookings', function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('customer_id')->nullable();
            $table->string('bookings', 500)->nullable();
            $table->date('booking_date')->nullable();
            $table->time('booking_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('payment_status', ['Pending', 'Paid', 'Cancelled'])->default('Pending');
            $table->string('name', 100)->nullable();
            $table->string('phone', 100)->nullable();
            $table->text('comments')->nullable();
            $table->double('amount', 10, 2)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};
