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
        Schema::create('sales', function (Blueprint $table) {
            $table->comment('');
            $table->increments('id');
            $table->integer('customer_id')->nullable()->default(0);
            $table->integer('company_id')->nullable();
            $table->integer('cashier_id')->nullable();
            $table->integer('vat')->nullable();
            $table->string('comments')->nullable();
            $table->timestamps();
            $table->boolean('status')->nullable()->default(true)->comment('1:completed, 0 canceled');
            $table->double('amount', 10, 2)->default(0);
            $table->double('discount', 10, 2)->nullable()->default(0);
            $table->double('state_tax', 10, 2)->nullable()->default(0);
            $table->double('county_tax', 10, 2)->nullable()->default(0);
            $table->double('city_tax', 10, 2)->nullable()->default(0);
            $table->string('name', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('comment', 500)->nullable();
            $table->string('type', 20)->nullable()->default('pos');
            $table->enum('payment_with', ['card', 'cash', 'paypal'])->nullable()->default('cash');
            $table->double('total_given', 10, 2)->nullable();
            $table->double('subtotal', 10, 2)->nullable();
            $table->double('change', 10, 2)->nullable();
            $table->integer('room_id')->nullable();
            $table->integer('table_id')->nullable();
            $table->string('payment_success', 11)->default('No');
            $table->boolean('ismetricsend')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales');
    }
};
