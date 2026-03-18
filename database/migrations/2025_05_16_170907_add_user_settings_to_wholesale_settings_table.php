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
        DB::table('wholesale_settings')->insert([
            ['key' => 'user_name', 'label' => 'User Name', 'value' => ''],
            ['key' => 'user_email', 'label' => 'User Email', 'value' => ''],
            ['key' => 'user_phone', 'label' => 'User Phone', 'value' => ''],
        ]);
    }
    
    public function down()
    {
        DB::table('wholesale_settings')->whereIn('key', ['user_name', 'user_email', 'user_phone'])->delete();
    }
};
