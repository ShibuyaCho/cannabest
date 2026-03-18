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
        Schema::create('permission_role', function (Blueprint $table) {
            $table->comment('');
            $table->unsignedInteger('permission_id');
            $table->unsignedBigInteger('role_id')->index('permission_role_role_id_foreign');

            $table->primary(['permission_id', 'role_id']);
        });

        DB::statement("INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES
        (1, 1),
        (1, 2),
        (2, 1),
        (2, 2),
        (2, 3),
        (3, 1),
        (3, 2),
        (4, 1),
        (4, 2),
        (5, 1),
        (5, 2),
        (6, 1),
        (6, 2),
        (7, 1),
        (7, 2),
        (8, 1),
        (8, 2),
        (9, 1),
        (9, 2),
        (10, 1),
        (10, 2),
        (11, 1),
        (11, 2),
        (12, 1),
        (12, 2),
        (13, 1),
        (13, 2),
        (14, 1),
        (14, 2),
        (15, 1),
        (15, 2),
        (16, 1),
        (16, 2),
        (17, 1),
        (17, 2),
        (18, 1),
        (18, 2),
        (19, 1),
        (19, 2),
        (20, 1),
        (20, 2),
        (21, 1),
        (21, 2),
        (22, 1),
        (22, 2),
        (23, 1),
        (23, 2),
        (24, 1),
        (24, 2),
        (25, 1),
        (25, 2),
        (26, 1),
        (26, 2),
        (27, 1),
        (27, 2),
        (28, 1),
        (28, 2),
        (29, 1),
        (29, 2),
        (30, 1),
        (30, 2),
        (31, 1),
        (31, 2),
        (32, 1),
        (32, 2),
        (33, 1),
        (33, 2),
        (34, 1),
        (34, 2),
        (35, 1),
        (35, 2);");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permission_role');
    }
};
