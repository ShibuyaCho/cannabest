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
        Schema::create('purchases', function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->integer('company_id')->nullable();
            $table->string('bill_no', 55)->nullable();
            $table->integer('supplier_id')->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('note', 1000)->nullable();
            $table->decimal('total_amount', 25)->nullable();
            $table->decimal('tax', 25)->nullable();
            $table->decimal('discount', 25)->nullable();
            $table->string('user')->nullable();
            $table->string('updated_by')->nullable();
            $table->decimal('paid', 25)->nullable();
            $table->enum('paid_by', ['cash', 'cheque'])->nullable();
            $table->string('cheque_no', 20)->nullable();
            $table->date('created_at')->nullable();
            $table->date('updated_at')->nullable();

            $table->index(['id'], 'id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchases');
    }
};
