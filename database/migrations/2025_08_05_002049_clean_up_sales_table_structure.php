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
            // Drop unused columns
            $drop = ['company_id', 'vat', 'comments', 'comment', 'room_id', 'table_id'];
            foreach ($drop as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Add missing useful fields
            if (!Schema::hasColumn('sales', 'customer_type')) {
                $table->string('customer_type')->default('consumer')->after('customer_id');
            }

            if (!Schema::hasColumn('sales', 'med_number')) {
                $table->string('med_number')->nullable()->after('customer_type');
            }

            if (!Schema::hasColumn('sales', 'caregiver_number')) {
                $table->string('caregiver_number')->nullable()->after('med_number');
            }
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            // Re-add dropped columns (optional)
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('vat')->nullable();
            $table->text('comments')->nullable();
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('table_id')->nullable();

            // Drop added fields
            $table->dropColumn(['customer_type', 'med_number', 'caregiver_number']);
        });
    }
};
