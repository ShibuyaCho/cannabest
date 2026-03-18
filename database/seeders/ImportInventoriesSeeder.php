<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImportInventoriesSeeder extends Seeder
{
    /** @var null|callable */
    protected $progress;

    public function __construct(?callable $progress = null)
    {
        $this->progress = $progress;
    }

    public function run(): void
    {
        $orgId = (int) config('imports.organization_id');
        $csv   = (string) config('imports.inventory_csv_path');

        if (!$orgId || !is_file($csv) || !is_readable($csv)) {
            throw new \RuntimeException("Importer missing org ({$orgId}) or CSV not readable: {$csv}");
        }

        DB::disableQueryLog();

        // Categories are NOT org-limited in your setup — pull all and map by name.
        $allCats = DB::table('categories')->get(['id', 'name']);

        // Allowed UI categories (exact spelling as in DB/UI)
        $allowed = [
            'flower','joints','extract','concentrate','infused joints',
            'extract carts','flavored carts','edibles','drinks/tinctures',
            'clones','accessories','apparel','hemp','topicals'
        ];

        // Build map: lower(name) -> ['id'=>..,'name'=>Original Case]
        $catMap = [];
        foreach ($allCats as $c) {
            $key = Str::lower(trim($c->name));
            if (in_array($key, $allowed, true)) {
                $catMap[$key] = ['id' => (int) $c->id, 'name' => $c->name];
            }
        }

        if (!$catMap) {
            throw new \RuntimeException('No allowed categories found in `categories` table.');
        }

        // Helper: pick a category id by (exact) name string
        $getCatId = function (string $want) use (&$catMap): int {
            $k = Str::lower(trim($want));
            if (isset($catMap[$k])) return $catMap[$k]['id'];
            if (isset($catMap['extract']))  return $catMap['extract']['id'];
            if (isset($catMap['flower']))   return $catMap['flower']['id'];
            return (int) reset($catMap)['id']; // any valid id
        };

        // Pull org discount tiers once (used for Flower)
        $rawTiers = DB::table('organizations')->where('id', $orgId)->value('discount_tiers');
        $tiers    = $this->normalizeTiers($rawTiers);

        [$headers, $rowsCount] = $this->countRows($csv);
        $this->emit('progress', ['processed' => 0, 'total' => $rowsCount]);

        $h   = $headers['map']; // normalized header -> index
        $now = Carbon::now()->toDateTimeString();

        // Buckets (fixed columns in every payload to avoid 1136 errors)
        $byLabel_withPC = [];
        $byLabel_noPC   = [];
        $bySku_withPC   = [];
        $bySku_noPC     = [];

        // Flusher
        $flush = function () use (&$byLabel_withPC, &$bySku_withPC, &$byLabel_noPC, &$bySku_noPC) {
            $this->flushBuckets($byLabel_withPC, $bySku_withPC, $byLabel_noPC, $bySku_noPC);
        };

        // Open & skip header
        if (($fh = fopen($csv, 'rb')) === false) {
            throw new \RuntimeException("Unable to open CSV: {$csv}");
        }
        fgetcsv($fh);

        $processed = 0;

        // Column alias sets
        $CAT_COLS   = ['category','product category','category name','type','item type','product type','department','class'];
        $NAME_COLS  = ['product name','name','item name','product'];
        $VAR_COLS   = ['variant name','variant','option','option name','flavor'];
        $BRAND_COLS = ['brand','brand name','manufacturer','producer','vendor','company'];
        $SKU_COLS   = ['sku','barcode','upc','item code','code'];
        $LBL_COLS   = ['regulatory id','label','metrc package','package id','package tag','tag'];

        // price / cost synonyms
        $PRICE_COLS = [
            'price','retail price','unit price','sale price','price each','price (each)',
            'msrp','list price','our price','your price','item price','selling price'
        ];
        $COST_COLS = [
            'cost','unit cost','wholesale cost','vendor cost','purchase cost',
            'avg cost','average cost','cost each','cost (each)','buy price'
        ];

        // quantity and inventory type (Room) aliases
        $QTY_COLS   = ['quantity','qty','on hand','on-hand','onhand','stock'];
        $ROOM_COLS  = ['room','location','bin','area','status','inventory type'];
        // discount tier aliases
        $TIER_COLS  = ['discount tier','tier','price tier'];

        // Process rows
        while (($row = fgetcsv($fh)) !== false) {
            // Extract fields using aliases
            $csvCat = $this->val($row, $h, $CAT_COLS);
            $product= $this->val($row, $h, $NAME_COLS);
            $variant= $this->val($row, $h, $VAR_COLS);
            $brand  = $this->val($row, $h, $BRAND_COLS);
            $sku    = $this->val($row, $h, $SKU_COLS);
            $label  = $this->val($row, $h, $LBL_COLS);
            $room   = $this->val($row, $h, $ROOM_COLS);
            $tierCsv= $this->val($row, $h, $TIER_COLS);

            $name = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([$product, $variant]))));
            if ($name === '') $name = $product ?: ($sku ?: ($label ?: 'Unnamed Item'));

            // Category (CSV-first policy + guards) aligned to Oregon definitions
            $targetCatName = $this->resolveCategoryName($csvCat, $name, $brand);
            $catId         = $getCatId($targetCatName);
            $isFlower      = Str::lower($targetCatName) === 'flower';

            // Numbers
            $qty   = $this->firstNumber($row, $h, $QTY_COLS) ?? 0;
            $price = $this->firstNumber($row, $h, $PRICE_COLS);
            $cost  = $this->firstNumber($row, $h, $COST_COLS);

            // Inventory type from "room"
            $inventoryType = $this->resolveInventoryType($room);

            // Decide discount tier (Flower only)
            $selectedTier = null;
            if ($isFlower) {
                if ($tierCsv !== '') {
                    $selectedTier = $this->matchTierName($tiers, $tierCsv);
                }
                if (!$selectedTier && $price !== null) {
                    $selectedTier = $this->inferTierByPrice($tiers, (float)$price);
                }
            }

            // ---- Build a FIXED-KEY payload (avoid 1136 by always including same columns)
            $base = [
                'organization_id'        => $orgId,
                'label'                  => $label !== '' ? $label : null, // lowercase column
                'category_id'            => $catId,
                'name'                   => $name,
                'sku'                    => $sku !== '' ? $sku : null,
                'storeQty'               => is_numeric($qty) ? (float)$qty : 0,
                'inventory_type'         => $inventoryType,
                'created_at'             => $now,
                'updated_at'             => $now,
                'original_price'         => null,
                'original_cost'          => null,
                'selected_discount_tier' => $selectedTier,
            ];

            $withPC   = $base;
            $hasPrice = is_numeric($price);
            $hasCost  = is_numeric($cost);

            // For Flower, use tier pricing (do not pin a fixed price); non-Flower can set price
            if (!$isFlower && $hasPrice) $withPC['original_price'] = (float) $price;
            if ($hasCost)               $withPC['original_cost']   = (float) $cost;

            $hasAnyPC = $hasPrice || $hasCost;

            // Route into buckets
            if ($label !== '') {
                if ($hasAnyPC) $byLabel_withPC[$orgId.'#'.$label] = $withPC;
                else           $byLabel_noPC[$orgId.'#'.$label]   = $base;
            } elseif ($sku !== '') {
                if ($hasAnyPC) $bySku_withPC[$orgId.'#'.$sku] = $withPC;
                else           $bySku_noPC[$orgId.'#'.$sku]   = $base;
            } else {
                $tmp = uniqid((string)$orgId, true);
                if ($hasAnyPC) $bySku_withPC[$tmp] = $withPC;
                else           $bySku_noPC[$tmp]   = $base;
            }

            $processed++;
            if (($processed % 2000) === 0) {
                $flush();
                $this->emit('progress', ['processed' => $processed, 'total' => $rowsCount]);
            }
        }
        fclose($fh);

        // Final flush
        $flush();
        $this->emit('progress', ['processed' => $processed, 'total' => $rowsCount]);
    }

    /* =========================================================
       CATEGORY RESOLUTION — CSV-first, Oregon-aligned:
       - Carts first (Extract Carts / Flavored Carts).
       - Edibles/Drinks/etc. via heuristics.
       - Concentrate vs Extract:
           * Concentrate: rosin/hash/kief/bubble; ethanol/RSO/FECO; subcritical CO2.
           * Extract: hydrocarbon/BHO/PHO/CRC; LIVE RESIN; distillate; supercritical CO2.
       - Flower ONLY if CSV said Flower (prevents cart mis-tags)
       ========================================================= */
    protected function resolveCategoryName(?string $csvCategory, string $name, string $brand): string
    {
        $raw = Str::lower(trim((string)$csvCategory));
        $hay = Str::lower(trim(($csvCategory ?? '').' '.$name.' '.$brand));

        // 0) Carts always win (prevents Flower/Edible mis-tags on carts)
        if ($this->isCartLike($hay)) {
            return $this->isFlavoredCart($hay) ? 'Flavored Carts' : 'Extract Carts';
        }

        // 1) CSV direct synonyms with refinements
        $direct = [
            'flower'             => 'Flower',
            'flowers'            => 'Flower',

            'joint'              => 'Joints',
            'joints'             => 'Joints',
            'pre roll'           => 'Joints',
            'pre-roll'           => 'Joints',
            'pre rolls'          => 'Joints',
            'pre-rolls'          => 'Joints',

            'infused joint'      => 'Infused Joints',
            'infused joints'     => 'Infused Joints',
            'infused pre roll'   => 'Infused Joints',
            'infused pre-roll'   => 'Infused Joints',

            'concentrate'        => 'Concentrate',
            'concentrates'       => 'Concentrate',

            'extract'            => 'Extract',
            'extracts'           => 'Extract',

            'cartridge'          => 'Extract Carts',
            'cartridges'         => 'Extract Carts',
            'cart'               => 'Extract Carts',
            'carts'              => 'Extract Carts',
            'vape'               => 'Extract Carts',
            'vapes'              => 'Extract Carts',

            'flavored cart'      => 'Flavored Carts',
            'flavored carts'     => 'Flavored Carts',

            'edible'             => 'Edibles',
            'edibles'            => 'Edibles',

            'drink'              => 'Drinks/Tinctures',
            'drinks'             => 'Drinks/Tinctures',
            'tincture'           => 'Drinks/Tinctures',
            'tinctures'          => 'Drinks/Tinctures',
            'beverage'           => 'Drinks/Tinctures',
            'beverages'          => 'Drinks/Tinctures',

            'clone'              => 'Clones',
            'clones'             => 'Clones',
            'accessories'        => 'Accessories',
            'apparel'            => 'Apparel',
            'hemp'               => 'Hemp',
            'topicals'           => 'Topicals',
        ];

        if ($raw && isset($direct[$raw])) {
            $pick = $direct[$raw];

            // refine carts → flavored if name hints
            if ($pick === 'Extract Carts' && $this->isFlavoredCart($hay)) return 'Flavored Carts';
            // refine joints → infused if name hints
            if (in_array($pick, ['Joints', 'Infused Joints'], true)) {
                return $this->isInfused($hay) ? 'Infused Joints' : 'Joints';
            }
            return $pick;
        }

        // 2) Heuristics by name/brand (no Flower here)
        if ($this->isPreRollLike($hay))      return $this->isInfused($hay) ? 'Infused Joints' : 'Joints';
        if ($this->isCloneLike($hay))        return 'Clones';
        if ($this->isDrinkLike($hay))        return 'Drinks/Tinctures';
        if ($this->isEdibleLike($hay))       return 'Edibles';
        if ($this->isConcentrateLike($hay))  return 'Concentrate';
        if ($this->isExtractLike($hay))      return 'Extract';
        if ($this->isTopicalLike($hay))      return 'Topicals';
        if ($this->isAccessoryLike($hay))    return 'Accessories';
        if ($this->isApparelLike($hay))      return 'Apparel';
        if ($this->isHempLike($hay))         return 'Hemp';

        // 3) Flower ONLY if CSV actually said flower (prevents cart mis-tags)
        if (in_array($raw, ['flower', 'flowers'], true)) {
            return 'Flower';
        }

        // 4) Final fallback
        return 'Extract';
    }

    // ---- Signals / Regex helpers ----

    protected function isCartLike(string $hay): bool
    {
        return (bool) preg_match(
            '/\b(cartridge|cart\b|510|vapes?|vape[- ]?pen|disposable|aio|all[- ]in[- ]one|pod|pods|pax|stiiizy|airgraft)\b|' .
            '\b(live\s*resin|rosin|distillate)\s*(cart|cartridge)\b/i',
            $hay
        );
    }

    // Stricter "flavored" check
    protected function isFlavoredCart(string $hay): bool
    {
        if (!$this->isCartLike($hay)) return false;

        // Negative guards first (NOT flavored)
        if (preg_match('/\b(single[- ]?(origin|source)|strain[- ]?(specific)?|native\s*terpenes|cannabis[- ]derived|no\s*added\s*terpenes|unflavored|solventless|live\s*rosin)\b/i', $hay)) {
            return false;
        }

        // Explicit flavored indicators
        return (bool) preg_match('/\b(buddies\s+flavored|flavor(?:ed|s)?|terp(?:s|ene)?[- ]?infused|botanical(?:ly)?[- ]?derived|added\s*terpenes)\b/i', $hay);
    }

    protected function isPreRollLike(string $hay): bool
    {
        return (bool) preg_match('/\b(pre[- ]?rolls?|joints?)\b/i', $hay);
    }

    protected function isInfused(string $hay): bool
    {
        return (bool) preg_match('/\b(infused|diamond|hash|rosin|k?ief)\b/i', $hay);
    }

    // EDIBLES (expanded)
    protected function isEdibleLike(string $hay): bool
    {
        return (bool) preg_match(
            '/\b(edible|gummy|gummies|choc(?:olate)?|cookie|brownie|chew|taffy|caramel|candy|hard\s*candy|lozenge|mint|pastille|baked|' .
            'capsules?|softgels?|tablets?|pills?|gelcaps?)\b/i',
            $hay
        );
    }

    // DRINKS / TINCTURES — treat "drops" & "syrup" as liquids
    protected function isDrinkLike(string $hay): bool
    {
        return (bool) preg_match('/\b(tincture|beverage|drink|soda|elixir|lemonade|tea|coffee|shot|syrup|drops?)\b/i', $hay);
    }

    // CONCENTRATE — Oregon: mechanical / ethanol / subcritical CO₂
    protected function isConcentrateLike(string $hay): bool
    {
        if (preg_match('/\b(rso|feco|ethanol|qwet|rosin(?!\s*cart)|live\s*rosin|hash(?!\s*infused)|k?ief|bubble|ice[- ]?water)\b/i', $hay)) {
            return true;
        }
        if (preg_match('/\b(sub[- ]?critical\s*co2|co2\s*(sub[- ]?critical|low[- ]?temp|low[- ]?pressure))\b/i', $hay)) {
            return true;
        }
        return false;
    }

    // EXTRACT — Oregon: hydrocarbons, LIVE RESIN, distillate, supercritical CO₂
    protected function isExtractLike(string $hay): bool
    {
        if (preg_match('/\b(bho|pho|hydrocarbon|crc)\b/i', $hay)) return true;
        if (preg_match('/\blive\s*resin\b/i', $hay)) return true; // hydrocarbon
        if (preg_match('/\bdistillate(?!\s*cart)\b/i', $hay)) return true;
        if (preg_match('/\b(super[- ]?critical\s*co2|co2\s*(super[- ]?critical|high[- ]?pressure|high[- ]?temp)|co2\s*oil)\b/i', $hay)) return true;

        // generic "CO2" with no qualifier → default to Extract
        if (preg_match('/\bco2\b/i', $hay) && !preg_match('/\bsub[- ]?critical\b/i', $hay)) return true;

        // syringes default by content
        if (preg_match('/\bsyringe\b/i', $hay) && !preg_match('/\b(rso|feco)\b/i', $hay)) return true;

        return false;
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

    /* =========================
       Inventory Type
       ========================= */
    protected function resolveInventoryType(string $room): string
    {
        $r = Str::lower(trim($room));
        if ($r === '') return 'inventories';

        if (preg_match('/hold|back[- ]?stock|quarantine|storage|safe|vault|overstock|intake/', $r)) {
            return 'hold_inventories';
        }
        return 'inventories';
    }

    /* =========================
       CSV helpers
       ========================= */
    protected function val(array $row, array $map, array $candidates): string
    {
        foreach ($candidates as $c) {
            $idx = $map[Str::lower($c)] ?? null;
            if ($idx !== null && array_key_exists($idx, $row)) {
                $v = trim((string) $row[$idx]);
                if ($v !== '') return $v;
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
            if ($n !== null) return (float) $n;
        }
        return null;
    }

    protected function num($v): ?float
    {
        if ($v === null || $v === '') return null;
        if (is_string($v)) $v = trim(str_replace([',', '$', '%'], '', $v));
        return is_numeric($v) ? (float) $v : null;
    }

    protected function countRows(string $csvPath): array
    {
        if (($fh = fopen($csvPath, 'rb')) === false) {
            throw new \RuntimeException("Unable to open CSV: {$csvPath}");
        }
        $header = fgetcsv($fh) ?: [];
        $map    = [];
        foreach ($header as $i => $h) {
            $map[Str::lower(trim($h))] = $i;
        }
        $n = 0;
        while (fgetcsv($fh) !== false) $n++;
        fclose($fh);
        return [['map' => $map], $n];
    }

    protected function flushBuckets(array &$labelWith, array &$skuWith, array &$labelNo, array &$skuNo): void
    {
        if ($labelWith) {
            DB::table('inventories')->upsert(
                array_values($labelWith),
                ['organization_id', 'label'], // lowercase
                ['name','category_id','storeQty','original_price','original_cost','inventory_type','selected_discount_tier','updated_at']
            );
            $labelWith = [];
        }

        if ($skuWith) {
            DB::table('inventories')->upsert(
                array_values($skuWith),
                ['organization_id', 'sku'],
                ['name','category_id','storeQty','original_price','original_cost','inventory_type','selected_discount_tier','updated_at']
            );
            $skuWith = [];
        }

        if ($labelNo) {
            DB::table('inventories')->upsert(
                array_values($labelNo),
                ['organization_id', 'label'], // lowercase
                ['name','category_id','storeQty','inventory_type','selected_discount_tier','updated_at'] // no price/cost update
            );
            $labelNo = [];
        }

        if ($skuNo) {
            DB::table('inventories')->upsert(
                array_values($skuNo),
                ['organization_id', 'sku'],
                ['name','category_id','storeQty','inventory_type','selected_discount_tier','updated_at'] // no price/cost update
            );
            $skuNo = [];
        }
    }

    protected function emit(string $evt, array $payload = []): void
    {
        if (is_callable($this->progress)) {
            ($this->progress)($evt, $payload);
        }
    }

    /** -------------------- PRICE TIER HELPERS -------------------- */

    /** Normalize org->discount_tiers JSON/array to [['name'=>..., 'pricing'=>[['price'=>...], ...]], ...] */
    protected function normalizeTiers($raw): array
    {
        $t = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
        return array_values(array_filter($t, fn($x) => is_array($x) && isset($x['name'])));
    }

    protected function matchTierName(array $tiers, string $tierCsv): ?string
    {
        $k = Str::lower(trim($tierCsv));
        foreach ($tiers as $t) {
            if (Str::lower($t['name'] ?? '') === $k) {
                return $t['name'];
            }
        }
        return null;
    }

    protected function inferTierByPrice(array $tiers, float $price): ?string
    {
        $best = null; $bestDiff = INF;

        foreach ($tiers as $t) {
            $p0 = data_get($t, 'pricing.0.price');
            if (!is_numeric($p0)) continue;
            $p0 = (float) $p0;

            if (abs($p0 - $price) < 0.00001) return $t['name']; // exact

            $diff = abs($p0 - $price) / max($p0, 0.01);
            if ($diff < $bestDiff) { $bestDiff = $diff; $best = $t['name']; }
        }

        return ($bestDiff <= 0.05) ? $best : null; // ≤5% tolerance
    }
}
