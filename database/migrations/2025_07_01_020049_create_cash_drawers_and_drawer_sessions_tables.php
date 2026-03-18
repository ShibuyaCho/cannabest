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
    Schema::create('cash_drawers', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // e.g., "Register 1"
        $table->timestamps();
    });

    Schema::create('drawer_sessions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cash_drawer_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->decimal('starting_amount', 10, 2);
        $table->decimal('closing_amount', 10, 2)->nullable();
        $table->timestamp('opened_at');
        $table->timestamp('closed_at')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });
}

};
