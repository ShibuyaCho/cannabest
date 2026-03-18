<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('metrc_receipts')) {
            Schema::table('metrc_receipts', function (Blueprint $table) {
                if (! $this->hasIndex('metrc_receipts', 'mr_license_time')) {
                    $table->index(['license_number','sales_date_time'], 'mr_license_time');
                }
                if (! $this->hasIndex('metrc_receipts', 'mr_metrcid')) {
                    $table->index(['metrc_id'], 'mr_metrcid');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('metrc_receipts')) {
            Schema::table('metrc_receipts', function (Blueprint $table) {
                $this->dropIndexIfExists('metrc_receipts', 'mr_license_time');
                $this->dropIndexIfExists('metrc_receipts', 'mr_metrcid');
            });
        }
    }

    /** Helpers to avoid SQL errors on different drivers */
    private function hasIndex(string $table, string $index): bool
    {
        try { return Schema::getConnection()->getDoctrineSchemaManager()->listTableDetails($table)->hasIndex($index); }
        catch (\Throwable $e) { return false; }
    }
    private function dropIndexIfExists(string $table, string $index): void
    {
        try { Schema::table($table, fn(Blueprint $t) => $t->dropIndex($index)); } catch (\Throwable $e) {}
    }
};
