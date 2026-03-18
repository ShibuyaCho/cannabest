<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('inventories', 'status_subtype')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->string('status_subtype', 64)->nullable()->index();
                // helpful for org-wide queries like your /inventories/subtypes endpoint
                $table->index(['organization_id', 'status_subtype'], 'inv_org_subtype_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            if (Schema::hasColumn('inventories', 'status_subtype')) {
                $table->dropIndex(['status_subtype']);                 // drops the single-column index
                $table->dropIndex('inv_org_subtype_idx');              // drops the composite index
                $table->dropColumn('status_subtype');
            }
        });
    }
};
