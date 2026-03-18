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
        Schema::create('customer_invoices', function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('ship_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('po_number', 55)->nullable();
            $table->string('terms', 500)->nullable();
            $table->integer('bill_customer')->nullable();
            $table->string('bill_address')->nullable();
            $table->string('bill_city', 100)->nullable();
            $table->string('bill_state', 100)->nullable();
            $table->string('bill_zip', 10)->nullable();
            $table->string('bill_country', 100)->nullable();
            $table->integer('ship_customer')->nullable();
            $table->string('ship_address', 500)->nullable();
            $table->string('ship_city', 100)->nullable();
            $table->string('ship_state', 100)->nullable();
            $table->string('ship_zip', 10)->nullable();
            $table->string('ship_country', 100)->nullable();
            $table->double('total_amount', 10, 2)->nullable();
            $table->double('tax', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->double('discount', 10, 2)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_invoices');
    }
};
