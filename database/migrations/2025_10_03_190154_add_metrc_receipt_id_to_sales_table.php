<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'metrc_receipt_id')) {
                $table->unsignedBigInteger('metrc_receipt_id')->nullable()->index()->after('id');
            }
        });

        // Best-effort FK (safe to skip if engine/version doesn’t support it)
        try {
            Schema::table('sales', function (Blueprint $table) {
                // avoid duplicate FKs
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $sm->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
                $doctrineTable = $sm->listTableDetails('sales');
                if (!$doctrineTable->hasForeignKey('sales_metrc_receipt_id_foreign')) {
                    $table->foreign('metrc_receipt_id')
                          ->references('id')->on('metrc_receipts')
                          ->nullOnDelete();
                }
            });
        } catch (\Throwable $e) {
            // ignore if doctrine not installed or FK cannot be added
        }
    }

    public function down(): void
    {
        try {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropForeign(['metrc_receipt_id']);
            });
        } catch (\Throwable $e) {}

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'metrc_receipt_id')) {
                $table->dropColumn('metrc_receipt_id');
            }
        });
    }
};
