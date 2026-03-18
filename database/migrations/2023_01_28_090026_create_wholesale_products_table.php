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
        Schema::create('wholesale_products', function (Blueprint $table) {
            $table->id();
           
            
            // Basic Product Info
            $table->string('name');
            $table->string('extraName');
            $table->string('brandName');
            
            
            // Pricing and weight
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('weight');
           
            // Image
            $table->string('image')->nullable();
            
            // Extra Fields
            $table->text('description')->nullable();
            
            // Additional category info
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('UnitThcContent')->nullable();
            $table->string('UnitCbdContent')->nullable();
            
            // Timestamps and soft delete
            $table->timestamps();
            $table->softDeletes();

            // Foreign key relationships
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wholesale_products');
    }
};
