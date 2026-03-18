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
       // Outbox for local changes
    Schema::create('sync_outbox', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->enum('action', ['create','update','delete']);
        $table->json('payload');
        $table->timestamp('created_at')->useCurrent();
        $table->timestamp('synced_at')->nullable();
    });

    // Meta table to track last pull timestamp
    Schema::create('sync_meta', function (Blueprint $table) {
        $table->string('key')->primary();
        $table->string('value');
    });
}

public function down()
{
    Schema::dropIfExists('sync_outbox');
    Schema::dropIfExists('sync_meta');
}
};
