<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up() {
    // Local flags (if not present)
    Schema::table('sales', function (Blueprint $t) {
      if (!Schema::hasColumn('sales','external_receipt_number'))
        $t->string('external_receipt_number')->nullable()->index();
      if (!Schema::hasColumn('sales','metrc_receipt_id'))
        $t->unsignedBigInteger('metrc_receipt_id')->nullable()->index();
      if (!Schema::hasColumn('sales','metrc_status'))
        $t->string('metrc_status', 32)->nullable()->index(); // pending|posted|linked|archived|error|duplicate
      if (!Schema::hasColumn('sales','metrc_signature_strict'))
        $t->string('metrc_signature_strict', 64)->nullable()->index();
      if (!Schema::hasColumn('sales','metrc_signature_relaxed'))
        $t->string('metrc_signature_relaxed', 64)->nullable()->index();
      if (!Schema::hasColumn('sales','metrc_last_pushed_at'))
        $t->timestamp('metrc_last_pushed_at')->nullable();
    });

    Schema::table('sale_items', function (Blueprint $t) {
      if (!Schema::hasColumn('sale_items','metrc_line_signature'))
        $t->string('metrc_line_signature', 64)->nullable()->index();
      if (!Schema::hasColumn('sale_items','metrc_receipt_id'))
        $t->unsignedBigInteger('metrc_receipt_id')->nullable()->index();
      if (!Schema::hasColumn('sale_items','metrc_receipt_line_id'))
        $t->unsignedBigInteger('metrc_receipt_line_id')->nullable()->index();
      if (!Schema::hasColumn('sale_items','metrc_package_label'))
        $t->string('metrc_package_label')->nullable()->index(); // quick lookup
    });

    // Cache of remote Metrc receipts
    Schema::create('metrc_receipts', function (Blueprint $t) {
      $t->bigIncrements('id');
      $t->unsignedBigInteger('organization_id')->nullable()->index();
      $t->string('license_number')->index();
      $t->unsignedBigInteger('metrc_id')->index(); // Metrc Receipt Id
      $t->string('external_receipt_number')->nullable()->index();
      $t->string('receipt_number')->nullable();
      $t->boolean('is_final')->default(false);
      $t->dateTime('sales_date_time')->nullable(); // facility local
      $t->dateTime('last_modified')->nullable();
      $t->string('signature_strict', 64)->nullable()->index();
      $t->string('signature_relaxed', 64)->nullable()->index();
      $t->decimal('total_price', 12, 2)->nullable(); // Metrc TotalPrice sum
      $t->json('raw')->nullable();
      $t->timestamps();
      $t->unique(['license_number','metrc_id']);
    });

    // Cache lines of those receipts
    Schema::create('metrc_receipt_lines', function (Blueprint $t) {
      $t->bigIncrements('id');
      $t->unsignedBigInteger('metrc_receipt_id')->index(); // FK to metrc_receipts.id (local table id)
      $t->unsignedBigInteger('metrc_line_id')->nullable(); // Metrc does not always expose line IDs, keep nullable
      $t->string('package_label')->index();
      $t->decimal('quantity', 12, 3);
      $t->string('uom', 24)->nullable();
      $t->decimal('total_price', 12, 2)->nullable(); // line pre-tax
      $t->string('line_signature', 64)->nullable()->index();
      $t->timestamps();
      $t->index(['metrc_receipt_id','package_label']);
    });
  }

  public function down() {
    Schema::dropIfExists('metrc_receipt_lines');
    Schema::dropIfExists('metrc_receipts');
    Schema::table('sale_items', function (Blueprint $t) {
      $t->dropColumn(['metrc_line_signature','metrc_receipt_id','metrc_receipt_line_id','metrc_package_label']);
    });
    Schema::table('sales', function (Blueprint $t) {
      $t->dropColumn(['external_receipt_number','metrc_receipt_id','metrc_status','metrc_signature_strict','metrc_signature_relaxed','metrc_last_pushed_at']);
    });
  }
};

