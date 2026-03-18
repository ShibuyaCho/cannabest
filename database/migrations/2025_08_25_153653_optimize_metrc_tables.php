<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
  private function indexExists(string $table, string $index): bool {
        $db = DB::getDatabaseName();
        return DB::table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function makeIndexName(string $table, $columns): string {
        $cols = is_array($columns) ? implode('_', (array) $columns) : $columns;
        return strtolower("{$table}_{$cols}_index");
    }

    private function safeAddIndex(string $table, $columns, ?string $name = null): void {
        $name = $name ?? $this->makeIndexName($table, $columns);
        if ($this->indexExists($table, $name)) return;
        Schema::table($table, function (Blueprint $t) use ($columns, $name) {
            $t->index((array) $columns, $name);
        });
    }

    private function safeDropIndex(string $table, $columns, ?string $name = null): void {
        $name = $name ?? $this->makeIndexName($table, $columns);
        if (! $this->indexExists($table, $name)) return;
        Schema::table($table, function (Blueprint $t) use ($name) {
            $t->dropIndex($name);
        });
    }

    public function up(): void
    {
        // metrc_packages
        if (! Schema::hasColumn('metrc_packages', 'LastModified')) {
            Schema::table('metrc_packages', function (Blueprint $t) {
                $t->dateTime('LastModified')->nullable();
            });
        }
        $this->safeAddIndex('metrc_packages', 'Id');
        $this->safeAddIndex('metrc_packages', 'Label');        // will skip if already there
        $this->safeAddIndex('metrc_packages', 'LastModified');

        // metrc_test_results
        $this->safeAddIndex('metrc_test_results', 'PackageId');
        $this->safeAddIndex('metrc_test_results', 'DateTested');

        // inventories (composite)
        $this->safeAddIndex('inventories', ['organization_id','Label'], 'inventories_org_label_idx');
    }

    public function down(): void
    {
        $this->safeDropIndex('inventories', ['organization_id','Label'], 'inventories_org_label_idx');
        $this->safeDropIndex('metrc_test_results', 'DateTested');
        $this->safeDropIndex('metrc_test_results', 'PackageId');
        $this->safeDropIndex('metrc_packages', 'LastModified');
        $this->safeDropIndex('metrc_packages', 'Label');
        $this->safeDropIndex('metrc_packages', 'Id');
        // (Leaving the LastModified column in place to avoid data loss.)
    }
};
