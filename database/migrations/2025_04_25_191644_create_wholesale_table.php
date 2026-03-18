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
    public function up(): void
    {
        Schema::create('wholesale', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 100)->nullable();
            $table->string('brandNames')->nullable();
            $table->string('api_key', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable(); 
            $table->string('e-mail')->nullable();          
            // store your products as a JSON array of objects with the fields you listed
            $table->json('products')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wholesale');
    }
};
