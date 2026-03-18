<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Services\CategoryClassifier;

class FixInventoryCategories extends Command
{
    protected $signature = 'inventory:fix-categories 
        {--org= : Organization ID}
        {--since=30 minutes : Time window to scan, e.g. "2 hours", "1 day"}';

    protected $description = 'Re-classify inventories by name/brand/etc and correct mis-categorized rows.';

    public function handle(): int
    {
        $orgId = (int) ($this->option('org') ?? 0);
        if (!$orgId) {
            $this->error('Missing --org');
            return 1;
        }

        $sinceStr = (string) $this->option('since');
        $since = now()->sub((string)$sinceStr);

        $classifier = new CategoryClassifier();

        // Build category map once
        $catRows = DB::table('categories')
            ->where('organization_id', $orgId)
            ->get(['id','name']);
        $catMap = [];
        foreach ($catRows as $c) $catMap[Str::lower($c->name)] = (int)$c->id;

        $q = DB::table('inventories')
            ->select('id','organization_id','name','sku','Label','category_id','updated_at')
            ->where('organization_id', $orgId)
            ->where('updated_at', '>=', $since);

        $rows = $q->get();
        $updated = 0; $checked = 0;

        foreach ($rows as $inv) {
            $checked++;
            $row = [
                'name'     => $inv->name,
                'variant name' => '',
                'brand'    => '',
                'category' => '', // unknown/original
            ];

            [$cat, $conf] = $classifier->classify($row);

            $catId = $catMap[Str::lower($cat)] ?? null;
            if (!$catId) continue;

            if ((int)$inv->category_id !== (int)$catId) {
                DB::table('inventories')->where('id', $inv->id)->update([
                    'category_id' => $catId,
                    'updated_at'  => now(),
                ]);
                $updated++;
            }
        }

        $this->info("Checked: {$checked} | Updated: {$updated}");
        return 0;
    }
}
