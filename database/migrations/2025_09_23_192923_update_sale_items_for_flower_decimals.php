<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // keep grams precisely
            $table->decimal('quantity', 10, 3)->change();

            // make price explicit scale
            $table->decimal('price', 10, 2)->change();

            // add the fields your controller already supports
            if (!Schema::hasColumn('sale_items','unit_price')) {
                $table->decimal('unit_price', 10, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('sale_items','line_total')) {
                $table->decimal('line_total', 10, 2)->nullable()->after('unit_price');
            }
            if (!Schema::hasColumn('sale_items','price_is_line_total')) {
                $table->boolean('price_is_line_total')->default(false)->after('line_total');
            }
            if (!Schema::hasColumn('sale_items','inline_discount_type')) {
                $table->string('inline_discount_type', 10)->nullable()->after('price_is_line_total');
            }
            if (!Schema::hasColumn('sale_items','inline_discount_value')) {
                $table->decimal('inline_discount_value', 10, 2)->nullable()->default(0)->after('inline_discount_type');
            }
        });
    }

    public function down(): void
    {
        // If you need a down() you can revert types; commonly left empty for destructive changes.
    }
};
