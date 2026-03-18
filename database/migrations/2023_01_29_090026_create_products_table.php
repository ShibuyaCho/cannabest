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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            
            // Basic Product Info
            $table->string('name');
            $table->string('extraName');
            $table->string('sku');
            
            // Pricing and Cost
            $table->decimal('original_price', 10, 2)->default(0);
            $table->decimal('original_cost', 10, 2)->default(0);
            $table->integer('weight');
           
                 
            $table->unsignedInteger('sales_count')->default(0);


            // Extra Fields
            $table->text('description')->nullable();
            $table->softDeletes();
            
            // Additional category info if needed (this could be a duplicate or additional label)
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            
            $table->string('UnitThcContent')->nullable();
            $table->string('UnitCbdContent')->nullable();
            
            // Timestamps
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
        Schema::dropIfExists('products');
    }
};
