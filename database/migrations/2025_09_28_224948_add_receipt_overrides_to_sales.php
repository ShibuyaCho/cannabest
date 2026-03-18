<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sales', function (Blueprint $table) {
            // Stores edited fields (pre_tax, tax, total_due, paid, change, discounts, etc)
            $table->json('receipt_overrides')->nullable()->after('change');
            $table->unsignedBigInteger('receipt_override_by')->nullable()->after('receipt_overrides');
            $table->timestamp('receipt_override_at')->nullable()->after('receipt_override_by');

            // Optional helpful indexes
            $table->index('receipt_override_at');
            $table->index('receipt_override_by');
        });
    }

    public function down(): void {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['receipt_overrides', 'receipt_override_by', 'receipt_override_at']);
        });
    }
};
