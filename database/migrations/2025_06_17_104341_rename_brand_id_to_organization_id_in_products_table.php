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
        Schema::table('products', function (Blueprint $table) {
            // First, drop the foreign key constraint if it exists
            $table->dropForeign(['brand_id']);
            
            // Rename the column
            $table->renameColumn('brand_id', 'organization_id');
            
            // Add a new foreign key constraint
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['organization_id']);
            
            // Rename the column back
            $table->renameColumn('organization_id', 'brand_id');
            
            // Re-add the original foreign key constraint if needed
            $table->foreign('brand_id')
                  ->references('id')
                  ->on('brands')
                  ->onDelete('set null');
        });
    }
};
