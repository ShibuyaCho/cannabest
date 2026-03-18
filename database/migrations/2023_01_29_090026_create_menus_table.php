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
        Schema::create('menus', function (Blueprint $table) {
            $table->comment('');
            $table->integer('menu_id', true);
            $table->integer('parent_id')->default(0);
            $table->string('link');
            $table->string('title');
            $table->boolean('active')->default(false);
            $table->integer('order_by');
            $table->string('translate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
    }
};
