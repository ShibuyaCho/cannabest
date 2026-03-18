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
        Schema::create('dashboard_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('layout');
            $table->json('widgets');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dashboard_configs');
    }
};
