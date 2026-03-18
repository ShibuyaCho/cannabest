<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /** USERS: index organization_id (only if column exists) */
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'organization_id')) {
            Schema::table('users', function (Blueprint $t) {
                // Will be used by controller to scope by org via users
                $t->index('organization_id', 'users_org_idx');
            });
        }

        /** METRC RECEIPTS: org/time, license/time, metrc_id (each only if columns exist) */
        if (Schema::hasTable('metrc_receipts')) {
            Schema::table('metrc_receipts', function (Blueprint $t) {
                if (Schema::hasColumn('metrc_receipts', 'organization_id') &&
                    Schema::hasColumn('metrc_receipts', 'sales_date_time')) {
                    $t->index(['organization_id', 'sales_date_time'], 'metrc_rcpt_org_time_idx');
                }
                if (Schema::hasColumn('metrc_receipts', 'license_number') &&
                    Schema::hasColumn('metrc_receipts', 'sales_date_time')) {
                    $t->index(['license_number', 'sales_date_time'], 'metrc_rcpt_license_time_idx');
                }
                if (Schema::hasColumn('metrc_receipts', 'metrc_id')) {
                    $t->index('metrc_id', 'metrc_rcpt_metrc_id_idx');
                }
            });
        }

        /** METRC RECEIPT LINES: speed up package-first linking */
        if (Schema::hasTable('metrc_receipt_lines')) {
            Schema::table('metrc_receipt_lines', function (Blueprint $t) {
                if (Schema::hasColumn('metrc_receipt_lines', 'metrc_receipt_id')) {
                    $t->index('metrc_receipt_id', 'metrc_lines_receipt_idx');
                }
                if (Schema::hasColumn('metrc_receipt_lines', 'package_label')) {
                    $t->index('package_label', 'metrc_lines_package_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $t) {
                try { $t->dropIndex('users_org_idx'); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('metrc_receipts')) {
            Schema::table('metrc_receipts', function (Blueprint $t) {
                foreach ([
                    'metrc_rcpt_org_time_idx',
                    'metrc_rcpt_license_time_idx',
                    'metrc_rcpt_metrc_id_idx',
                ] as $idx) {
                    try { $t->dropIndex($idx); } catch (\Throwable $e) {}
                }
            });
        }

        if (Schema::hasTable('metrc_receipt_lines')) {
            Schema::table('metrc_receipt_lines', function (Blueprint $t) {
                foreach ([
                    'metrc_lines_receipt_idx',
                    'metrc_lines_package_idx',
                ] as $idx) {
                    try { $t->dropIndex($idx); } catch (\Throwable $e) {}
                }
            });
        }
    }
};
