<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /* --- helpers --------------------------------------------------------- */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $db = DB::getDatabaseName();
            $rows = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$db, $table, $index]
            );
            return !empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function up(): void
    {
        /* Case 1: table missing → create it */
        if (!Schema::hasTable('metrc_receipts')) {
            Schema::create('metrc_receipts', function (Blueprint $table) {
                $table->bigIncrements('id');

                // Scope/ownership
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();

                // METRC identifiers & numbers
                $table->unsignedBigInteger('metrc_id');
                $table->string('receipt_number', 50)->nullable()->index();
                $table->string('external_receipt_number', 100)->nullable()->index();
                $table->string('license_number', 50)->nullable()->index();

                // Timing & money (METRC DateSold stored in UTC)
                $table->dateTime('sales_date_time')->nullable()->index();
                $table->decimal('total_price', 12, 2)->nullable();
                $table->boolean('is_final')->default(false)->index();

                // Raw payload for audits/debugging
                $table->json('payload')->nullable();

                $table->timestamps();

                // Avoid duplicates per org
                $table->unique(['organization_id', 'metrc_id'], 'metrc_receipts_org_metrc_unique');
                $table->index(['organization_id', 'sales_date_time'], 'metrc_receipts_org_salesdate_idx');
            });

            return; // created fresh
        }

        /* Case 2: table exists → patch anything missing */
        Schema::table('metrc_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('metrc_receipts', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('metrc_receipts', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->index()->after('organization_id');
            }
            if (!Schema::hasColumn('metrc_receipts', 'metrc_id')) {
                $table->unsignedBigInteger('metrc_id')->after('user_id');
            }
            if (!Schema::hasColumn('metrc_receipts', 'receipt_number')) {
                $table->string('receipt_number', 50)->nullable()->index()->after('metrc_id');
            }
            if (!Schema::hasColumn('metrc_receipts', 'external_receipt_number')) {
                $table->string('external_receipt_number', 100)->nullable()->index()->after('receipt_number');
            }
            if (!Schema::hasColumn('metrc_receipts', 'license_number')) {
                $table->string('license_number', 50)->nullable()->index()->after('external_receipt_number');
            }
            if (!Schema::hasColumn('metrc_receipts', 'sales_date_time')) {
                $table->dateTime('sales_date_time')->nullable()->index()->after('license_number');
            }
            if (!Schema::hasColumn('metrc_receipts', 'total_price')) {
                $table->decimal('total_price', 12, 2)->nullable()->after('sales_date_time');
            }
            if (!Schema::hasColumn('metrc_receipts', 'is_final')) {
                $table->boolean('is_final')->default(false)->index()->after('total_price');
            }
            if (!Schema::hasColumn('metrc_receipts', 'payload')) {
                $table->json('payload')->nullable()->after('is_final');
            }
            if (!Schema::hasColumn('metrc_receipts', 'created_at')) {
                $table->timestamps();
            }
        });

        // Ensure the composite unique + helpful composite index exist
        if (!$this->indexExists('metrc_receipts', 'metrc_receipts_org_metrc_unique')) {
            try {
                Schema::table('metrc_receipts', function (Blueprint $table) {
                    $table->unique(['organization_id', 'metrc_id'], 'metrc_receipts_org_metrc_unique');
                });
            } catch (\Throwable $e) { /* safe no-op */ }
        }
        if (!$this->indexExists('metrc_receipts', 'metrc_receipts_org_salesdate_idx')) {
            try {
                Schema::table('metrc_receipts', function (Blueprint $table) {
                    $table->index(['organization_id', 'sales_date_time'], 'metrc_receipts_org_salesdate_idx');
                });
            } catch (\Throwable $e) { /* safe no-op */ }
        }
    }

    public function down(): void
    {
        // Non-destructive: if this migration created the table, you can drop it manually.
        // Otherwise, we’ll only try to remove the unique/index we added.
        try {
            if ($this->indexExists('metrc_receipts', 'metrc_receipts_org_metrc_unique')) {
                Schema::table('metrc_receipts', function (Blueprint $table) {
                    $table->dropUnique('metrc_receipts_org_metrc_unique');
                });
            }
        } catch (\Throwable $e) {}
        try {
            if ($this->indexExists('metrc_receipts', 'metrc_receipts_org_salesdate_idx')) {
                Schema::table('metrc_receipts', function (Blueprint $table) {
                    $table->dropIndex('metrc_receipts_org_salesdate_idx');
                });
            }
        } catch (\Throwable $e) {}
    }
};
