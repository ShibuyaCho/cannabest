<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RetagInventoryCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orgId;
    public ?string $progressKey;
    public ?string $csvPath;

    public function __construct(int $orgId, ?string $progressKey = null, ?string $csvPath = null)
    {
        $this->orgId       = $orgId;
        $this->progressKey = $progressKey;
        $this->csvPath     = $csvPath;
    }

    public function handle(): void
    {
        $this->mark('retagging-start');

        // Build category name -> id map (ensured by seeder)
        $cats = DB::table('categories')->where('organization_id', $this->orgId)->get(['id','name']);
        $catId = [];
        foreach ($cats as $c) $catId[Str::lower($c->name)] = (int) $c->id;

        $getCatId = function (string $cat) use ($catId): int {
            $k = Str::lower(trim($cat));
            return $catId[$k] ?? ($catId['extract'] ?? array_values($catId)[0]);
        };

        // Optional: read CSV again to pull price/cost + category hints by Label/SKU
        $csvRows = [];
        if ($this->csvPath && is_file($this->csvPath) && is_readable($this->csvPath)) {
            [$headers, $rowsCount, $rows] = $this->readCsv($this->csvPath);
            $h = $headers['map'];

            // Alias sets
            $CAT_COLS   = ['category','product category','category name','type','item type','product type','department','class'];
            $NAME_COLS  = ['product name','name','item name'];
            $BRAND_COLS = ['brand','brand name','manufacturer','producer'];
            $SKU_COLS   = ['sku','barcode','upc','item code'];
            $LBL_COLS   = ['regulatory id','label','metrc package','package id','package tag','tag'];

            $PRICE_COLS = ['price','retail price','unit price','sale price','price each','price (each)','msrp','list price','our price','your price','item price','selling price'];
            $COST_COLS  = ['cost','unit cost','wholesale cost','vendor cost','purchase cost','avg cost','average cost','cost each','cost (each)','buy price'];

            foreach ($rows as $row) {
                $csvCat = $this->val($row, $h, $CAT_COLS);
                $name   = $this->val($row, $h, $NAME_COLS);
                $brand  = $this->val($row, $h, $BRAND_COLS);
                $sku    = $this->val($row, $h, $SKU_COLS);
                $label  = $this->val($row, $h, $LBL_COLS);

                $catName = $this->resolveCategoryName($csvCat, $name, $brand);
                $price   = $this->firstNumber($row, $h, $PRICE_COLS);
                $cost    = $this->firstNumber($row, $h, $COST_COLS);

                if ($label !== '') $csvRows['label:'.$label] = ['cat' => $catName, 'price' => $price, 'cost' => $cost];
                if ($sku   !== '') $csvRows['sku:'.$sku]     = ['cat' => $catName, 'price' => $price, 'cost' => $cost];
            }
        }

        // Retag + fill price/cost if missing
        $q = DB::table('inventories')->where('organization_id', $this->orgId);
        $total = (int) $q->count();
        $done  = 0;

        $q->orderBy('id')->chunk(2000, function ($chunk) use (&$done, $total, $csvRows, $getCatId) {
            $updates = [];
            foreach ($chunk as $inv) {
                $name = (string) $inv->name;
                $hay  = Str::lower($name);

                // category from CSV row if possible
                $fromCsv = null;
                if ($inv->Label && isset($csvRows['label:'.$inv->Label])) $fromCsv = $csvRows['label:'.$inv->Label];
                elseif ($inv->sku && isset($csvRows['sku:'.$inv->sku]))   $fromCsv = $csvRows['sku:'.$inv->sku];

                $catName = $fromCsv['cat'] ?? $this->classifyNameOnly($hay);
                $targetId = $getCatId($catName);

                $patch = [
                    'id'            => $inv->id,
                    'category_id'   => $targetId,
                    'updated_at'    => now(),
                ];

                // price / cost: only write if inv value is null/0 but CSV has a positive number
                if (isset($fromCsv['price']) && (empty($inv->original_price) || $inv->original_price == 0)) {
                    if (is_numeric($fromCsv['price']) && $fromCsv['price'] > 0) $patch['original_price'] = (float)$fromCsv['price'];
                }
                if (isset($fromCsv['cost']) && (empty($inv->original_cost) || $inv->original_cost == 0)) {
                    if (is_numeric($fromCsv['cost']) && $fromCsv['cost'] > 0) $patch['original_cost'] = (float)$fromCsv['cost'];
                }

                $updates[] = $patch;
            }

            if ($updates) {
                DB::table('inventories')->upsert(
                    $updates,
                    ['id'],
                    ['category_id','original_price','original_cost','updated_at']
                );
            }

            $done += count($chunk);
            $this->mark('retagging', ['processed' => $done, 'total' => $total]);
        });

        $this->mark('done');
    }

    /* ======= CSV helpers for post-pass ======= */
    protected function readCsv(string $path): array
    {
        $fh = fopen($path, 'rb');
        if (!$fh) return [[ 'map' => [] ], 0, []];

        $header = fgetcsv($fh) ?: [];
        $map = [];
        foreach ($header as $i => $h) $map[Str::lower(trim($h))] = $i;

        $rows = [];
        while (($r = fgetcsv($fh)) !== false) $rows[] = $r;
        fclose($fh);

        return [[ 'map' => $map ], count($rows), $rows];
    }

    protected function val(array $row, array $map, array $candidates): string
    {
        foreach ($candidates as $c) {
            $idx = $map[Str::lower($c)] ?? null;
            if ($idx !== null && isset($row[$idx]) && trim((string)$row[$idx]) !== '') {
                return trim((string)$row[$idx]);
            }
        }
        return '';
    }
    protected function firstNumber(array $row, array $map, array $candidates): ?float
    {
        foreach ($candidates as $c) {
            $idx = $map[Str::lower($c)] ?? null;
            if ($idx === null) continue;
            $v = $row[$idx] ?? null;
            if ($v === null || $v === '') continue;
            $n = $this->num($v);
            if ($n !== null) return $n + 0.0;
        }
        return null;
    }
    protected function num($v): ?float
    {
        if ($v === null || $v === '') return null;
        if (is_string($v)) $v = trim(str_replace([',','$','%'], '', $v));
        return is_numeric($v) ? (float)$v : null;
    }

    /* ======= Classification (same rules as seeder, without CSV) ======= */
    protected function classifyNameOnly(string $hay): string
    {
        if ($this->isCartLike($hay))        return $this->isFlavoredCart($hay) ? 'Flavored Carts' : 'Extract Carts';
        if ($this->isPreRollLike($hay))     return $this->isInfused($hay) ? 'Infused Joints' : 'Joints';
        if ($this->isCloneLike($hay))       return 'Clones';
        if ($this->isDrinkLike($hay))       return 'Drinks/Tinctures';
        if ($this->isEdibleLike($hay))      return 'Edibles';
        if ($this->isConcentrateLike($hay)) return 'Concentrate';
        if ($this->isTopicalLike($hay))     return 'Topicals';
        if ($this->isAccessoryLike($hay))   return 'Accessories';
        if ($this->isApparelLike($hay))     return 'Apparel';
        if ($this->isHempLike($hay))        return 'Hemp';
        if ($this->isFlowerLike($hay))      return 'Flower'; // name-only fallback
        if ($this->isExtractLike($hay))     return 'Extract';
        return 'Extract';
    }

    protected function resolveCategoryName(?string $csvCategory, string $name, string $brand): string
    {
        // same as the seeder’s logic, abbreviated to reuse
        $raw = Str::lower(trim((string)$csvCategory));
        $hay = Str::lower(trim(($csvCategory ?? '').' '.$name.' '.$brand));

        if ($this->isCartLike($hay)) return $this->isFlavoredCart($hay) ? 'Flavored Carts' : 'Extract Carts';

        $direct = [
            'flower'=>'Flower','flowers'=>'Flower',
            'joint'=>'Joints','joints'=>'Joints','pre roll'=>'Joints','pre-roll'=>'Joints','pre rolls'=>'Joints','pre-rolls'=>'Joints',
            'infused joint'=>'Infused Joints','infused joints'=>'Infused Joints','infused pre roll'=>'Infused Joints','infused pre-roll'=>'Infused Joints',
            'concentrate'=>'Concentrate','concentrates'=>'Concentrate',
            'extract'=>'Extract','extracts'=>'Extract',
            'cartridge'=>'Extract Carts','cartridges'=>'Extract Carts','cart'=>'Extract Carts','carts'=>'Extract Carts','vape'=>'Extract Carts','vapes'=>'Extract Carts',
            'flavored cart'=>'Flavored Carts','flavored carts'=>'Flavored Carts',
            'edible'=>'Edibles','edibles'=>'Edibles',
            'drink'=>'Drinks/Tinctures','drinks'=>'Drinks/Tinctures','tincture'=>'Drinks/Tinctures','tinctures'=>'Drinks/Tinctures','beverage'=>'Drinks/Tinctures','beverages'=>'Drinks/Tinctures',
            'clone'=>'Clones','clones'=>'Clones','accessories'=>'Accessories','apparel'=>'Apparel','hemp'=>'Hemp','topicals'=>'Topicals'
        ];
        if ($raw && isset($direct[$raw])) {
            $pick = $direct[$raw];
            if ($pick === 'Extract Carts' && $this->isFlavoredCart($hay)) return 'Flavored Carts';
            if (in_array($pick, ['Joints','Infused Joints'], true)) return $this->isInfused($hay) ? 'Infused Joints' : 'Joints';
            return $pick;
        }

        return $this->classifyNameOnly($hay);
    }

    // signals (same as seeder)
    protected function isCartLike(string $hay): bool
    {
        return (bool) preg_match(
            '/\b(cartridge|cart\b|510|vapes?|vape[- ]?pen|disposable|aio|all[- ]in[- ]one|pod|pods|pax|stiiizy|airgraft)\b|' .
            '\b(live\s*resin|rosin|distillate)\s*(cart|cartridge)\b/i',
            $hay
        );
    }
    protected function isFlavoredCart(string $hay): bool
    {
        if (str_contains($hay, 'buddies flavored')) return true;
        if (str_contains($hay, 'green leaf special')) return true;
        return (bool) preg_match('/\bflavor(?:ed|s)?\b/i', $hay);
    }
    protected function isPreRollLike(string $hay): bool
    {
        return (bool) preg_match('/\b(pre[- ]?rolls?|joints?)\b/i', $hay);
    }
    protected function isInfused(string $hay): bool
    {
        return (bool) preg_match('/\b(infused|diamond|hash|rosin|k?ief)\b/i', $hay);
    }
    protected function isEdibleLike(string $hay): bool
    {
        return (bool) preg_match('/\b(edible|gummy|choc(?:olate)?|cookie|brownie|chew|candy|lozenge|mint|baked)\b/i', $hay);
    }
    protected function isDrinkLike(string $hay): bool
    {
        return (bool) preg_match('/\b(tincture|beverage|drink|soda|elixir|lemonade|tea|coffee|shot)\b/i', $hay);
    }
    protected function isConcentrateLike(string $hay): bool
    {
        return (bool) preg_match(
            '/\b(budder|badder|sugar|crumble|hash(?!\s*infused)|rosin(?!\s*cart)|wax|shatter|diamonds?|sauce|live\s*rosin|live\s*resin)\b/i',
            $hay
        );
    }
    protected function isTopicalLike(string $hay): bool
    {
        return (bool) preg_match('/\b(topical|lotion|balm|salve|cream|ointment|patch)\b/i', $hay);
    }
    protected function isCloneLike(string $hay): bool
    {
        return (bool) preg_match('/\b(clone|clones|seed|seeds|plant)\b/i', $hay);
    }
    protected function isAccessoryLike(string $hay): bool
    {
        return (bool) preg_match('/\b(battery|charger|lighter|papers?|cones?|tips?|grinder|bong|rig|tray|torch)\b/i', $hay);
    }
    protected function isApparelLike(string $hay): bool
    {
        return (bool) preg_match('/\b(tee|t[- ]?shirt|shirt|hoodie|sweatshirt|hat|cap|beanie)\b/i', $hay);
    }
    protected function isHempLike(string $hay): bool
    {
        return (bool) preg_match('/\bhemp\b/i', $hay);
    }
    protected function isExtractLike(string $hay): bool
    {
        return (bool) preg_match('/\b(rso|syringe|distillate(?!\s*cart)|capsules?)\b/i', $hay);
    }
    protected function isFlowerLike(string $hay): bool
    {
        return (bool) preg_match('/\b(flower|bud|smalls|popcorn|eighth|quarter|half|ounce|oz|pre[- ]?pack)\b/i', $hay);
    }

    protected function mark(string $status, array $extra = []): void
    {
        if (!$this->progressKey) return;
        $state = Cache::get($this->progressKey, []);
        $state['status'] = $status;
        foreach ($extra as $k => $v) $state[$k] = $v;
        Cache::put($this->progressKey, $state, now()->addHours(6));
    }
}
