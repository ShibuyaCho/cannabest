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
        Schema::table('sales', function (Blueprint $table) {
            // nullable in case you have historical records you’ll backfill
            $table->foreignId('drawer_session_id')
                  ->nullable()
                  ->constrained('drawer_sessions')
                  ->after('id');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('drawer_session_id');
        });
    
    }
};
