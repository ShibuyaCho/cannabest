<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales','metrc_receipt_id')) {
            // Clean duplicates first: keep the smallest id, null-out the rest
            $dupes = DB::table('sales')
                ->select('metrc_receipt_id')
                ->whereNotNull('metrc_receipt_id')
                ->groupBy('metrc_receipt_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('metrc_receipt_id');

            foreach ($dupes as $rid) {
                $ids = DB::table('sales')->where('metrc_receipt_id',$rid)->orderBy('id')->pluck('id')->all();
                array_shift($ids); // keep the first
                if ($ids) DB::table('sales')->whereIn('id',$ids)->update(['metrc_receipt_id'=>null, 'updated_at'=>now()]);
            }

            Schema::table('sales', function (Blueprint $table) {
                // Unique for non-null values (MySQL allows multiple NULLs)
                $table->unique('metrc_receipt_id', 'uniq_sales_metrc_receipt');
            });
        }

        if (Schema::hasTable('metrc_receipts')) {
            Schema::table('metrc_receipts', function (Blueprint $table) {
                // Avoid duplicates at source: one row per (license, metrc_id)
                $table->unique(['license_number','metrc_id'],'uniq_metrc_license_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales','metrc_receipt_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropUnique('uniq_sales_metrc_receipt');
            });
        }
        if (Schema::hasTable('metrc_receipts')) {
            Schema::table('metrc_receipts', function (Blueprint $table) {
                $table->dropUnique('uniq_metrc_license_id');
            });
        }
    }
};
