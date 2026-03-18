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
        Schema::table('wholesale_products', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false);
        });
    }
    
    public function down()
    {
        Schema::table('wholesale_products', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
};
