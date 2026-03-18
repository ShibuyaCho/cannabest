<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('metrc_receipts', function (Blueprint $table) {
            // Speeds up org-scoped time window scans
            $table->index(['organization_id', 'sales_date_time'], 'mr_org_sales_idx');

            // Speeds up license-scoped time window scans
            $table->index(['license_number', 'sales_date_time'], 'mr_license_sales_idx');

            // Fast lookup by METRC id (and optional uniqueness per license)
            $table->index('metrc_id', 'mr_metrc_id_idx');

            // Optional but recommended if metrc_id is unique per license
            // $table->unique(['license_number', 'metrc_id'], 'mr_license_metrc_unique');
        });
    }

    public function down(): void
    {
        Schema::table('metrc_receipts', function (Blueprint $table) {
            $table->dropIndex('mr_org_sales_idx');
            $table->dropIndex('mr_license_sales_idx');
            $table->dropIndex('mr_metrc_id_idx');
            // $table->dropUnique('mr_license_metrc_unique');
        });
    }
};
