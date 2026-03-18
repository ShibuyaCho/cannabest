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
        Schema::table('wholesale_orders', function (Blueprint $table) {
            // First, add the new columns as nullable
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
        });

        // Handle existing data
        $orders = DB::table('wholesale_orders')->get();
        foreach ($orders as $order) {
            $user = DB::table('users')->find($order->user_id);
            if ($user) {
                DB::table('wholesale_orders')
                    ->where('id', $order->id)
                    ->update([
                        'organization_id' => $user->organization_id,
                        'created_by_user_id' => $user->id
                    ]);
            }
        }

        Schema::table('wholesale_orders', function (Blueprint $table) {
            // Now that we've handled existing data, we can drop the old column and add constraints
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');

            // Make the new columns non-nullable
            $table->unsignedBigInteger('organization_id')->nullable(false)->change();
            $table->unsignedBigInteger('created_by_user_id')->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('wholesale_orders', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn(['organization_id', 'created_by_user_id']);
            
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Restore user_id data from created_by_user_id
        $orders = DB::table('wholesale_orders')->get();
        foreach ($orders as $order) {
            DB::table('wholesale_orders')
                ->where('id', $order->id)
                ->update(['user_id' => $order->created_by_user_id]);
        }

        Schema::table('wholesale_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
