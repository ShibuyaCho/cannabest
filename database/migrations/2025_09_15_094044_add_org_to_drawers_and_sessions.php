<?php

// database/migrations/2025_09_15_000000_add_org_to_drawers_and_sessions.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('cash_drawers','organization_id')) {
            Schema::table('cash_drawers', function (Blueprint $table) {
                $table->unsignedBigInteger('organization_id')->index()->after('id');
            });
        }
        if (!Schema::hasColumn('drawer_sessions','organization_id')) {
            Schema::table('drawer_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('organization_id')->index()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cash_drawers','organization_id')) {
            Schema::table('cash_drawers', fn(Blueprint $t) => $t->dropColumn('organization_id'));
        }
        if (Schema::hasColumn('drawer_sessions','organization_id')) {
            Schema::table('drawer_sessions', fn(Blueprint $t) => $t->dropColumn('organization_id'));
        }
    }
};
