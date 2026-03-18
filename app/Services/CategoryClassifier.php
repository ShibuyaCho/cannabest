<?php

namespace App\Services;

use Illuminate\Support\Str;

class CategoryClassifier
{
    /** @var array<string, array> */
    protected array $rules;

    /** @var string[] */
    protected array $allowed;

    /** @var array<string,string> */
    protected array $synonyms;

    public function __construct(?array $cfg = null)
    {
        // load from config, or fallback defaults
        $cfg = $cfg ?? config('category_classifier', []);

        $this->allowed  = $cfg['allowed']  ?? [
            'Flower','Joints','Extract','Concentrate','Infused Joints',
            'Extract Carts','Flavored Carts','Edibles','Drinks/Tinctures',
            'Clones','Accessories','Apparel','Hemp','Topicals',
        ];

        $this->synonyms = $cfg['synonyms'] ?? [
            'flower'=>'Flower','flowers'=>'Flower',
            'joint'=>'Joints','joints'=>'Joints','pre roll'=>'Joints','pre-roll'=>'Joints','pre rolls'=>'Joints','pre-rolls'=>'Joints',
            'infused joint'=>'Infused Joints','infused joints'=>'Infused Joints','infused pre roll'=>'Infused Joints','infused pre-roll'=>'Infused Joints',
            'cartridge'=>'Extract Carts','cartridges'=>'Extract Carts','cart'=>'Extract Carts','carts'=>'Extract Carts','vape'=>'Extract Carts','vapes'=>'Extract Carts',
            'flavored carts'=>'Flavored Carts','flavored cart'=>'Flavored Carts',
            'concentrate'=>'Concentrate','concentrates'=>'Concentrate',
            'extract'=>'Extract','extracts'=>'Extract',
            'edible'=>'Edibles','edibles'=>'Edibles',
            'drink'=>'Drinks/Tinctures','drinks'=>'Drinks/Tinctures','tincture'=>'Drinks/Tinctures','tinctures'=>'Drinks/Tinctures','beverage'=>'Drinks/Tinctures','beverages'=>'Drinks/Tinctures',
            'clones'=>'Clones','accessories'=>'Accessories','apparel'=>'Apparel','hemp'=>'Hemp','topicals'=>'Topicals',
        ];

        // Weighted tokens; positive and negative signals per category
        $this->rules = $cfg['rules'] ?? $this->defaultRules();
    }

    /**
     * Classify a row: returns [category, confidence, reasons]
     * Inputs can be raw CSV fields; we’ll normalize.
     *
     * @param array $row keys can include: category, product name, name, variant name, brand, description, product_category_name, item_category_name, etc.
     */
    public function classify(array $row): array
    {
        $csvCat  = $this->v($row, ['category']);
        $name    = $this->v($row, ['product name', 'name']);
        $variant = $this->v($row, ['variant name']);
        $brand   = $this->v($row, ['brand']);
        $desc    = $this->v($row, ['description']);
        $extra   = trim(implode(' ', [
            $this->v($row, ['product category name']),
            $this->v($row, ['item category name']),
            $this->v($row, ['producttype','product type']),
        ]));

        $display = trim(preg_replace('/\s+/', ' ', trim($name.' '.$variant)));
        $hay     = Str::lower(trim(implode(' ', array_filter([$csvCat,$display,$brand,$desc,$extra]))));

        // Start with CSV synonyms (weak, low-weight hint)
        $seed = $this->synonymCategory($csvCat);

        // Score categories
        $scores  = array_fill_keys($this->allowed, 0.0);
        $reasons = [];

        // Global “hard” guards first (e.g., carts beat flower)
        if ($this->looksCart($hay)) {
            $scores['Extract Carts'] += 6.0;
            $reasons[] = 'cart-token';
            if ($this->looksFlavored($hay)) {
                $scores['Flavored Carts'] += 3.5;
                $reasons[] = 'flavored-token';
            }
            // Anti-flower penalty when cart detected
            $scores['Flower'] -= 4.0;
        }

        // Infused vs plain prerolls
        if ($this->looksPreRoll($hay)) {
            $scores['Joints'] += 3.0; $reasons[] = 'preroll';
            if ($this->looksInfused($hay)) {
                $scores['Infused Joints'] += 3.0; $reasons[] = 'infused';
                // Give Infused a slight edge over plain joints
                $scores['Joints'] -= 1.0;
            }
        }

        // Dabbables (NOT carts)
        if ($this->looksConcentrate($hay)) {
            $scores['Concentrate'] += 3.5; $reasons[] = 'concentrate';
            // Don’t allow carts to steal this unless cart tokens present
            if (!$this->looksCart($hay)) {
                $scores['Extract Carts'] -= 1.0;
                $scores['Flavored Carts'] -= 1.0;
            }
        }

        if ($this->looksEdible($hay))           { $scores['Edibles'] += 3.0; $reasons[] = 'edible'; }
        if ($this->looksDrink($hay))            { $scores['Drinks/Tinctures'] += 3.0; $reasons[] = 'drink'; }
        if ($this->looksTopical($hay))          { $scores['Topicals'] += 3.0; $reasons[] = 'topical'; }
        if ($this->looksClone($hay))            { $scores['Clones'] += 3.0; $reasons[] = 'clone'; }
        if ($this->looksAccessory($hay))        { $scores['Accessories'] += 3.0; $reasons[] = 'accessory'; }
        if ($this->looksApparel($hay))          { $scores['Apparel'] += 3.0; $reasons[] = 'apparel'; }
        if ($this->looksHemp($hay))             { $scores['Hemp'] += 2.0;  $reasons[] = 'hemp'; }

        // Flower is LOWEST priority and only when no cart/preroll signals
        if (!$this->looksCart($hay) && !$this->looksPreRoll($hay) && $this->looksFlower($hay)) {
            $scores['Flower'] += 2.0; $reasons[] = 'flower';
        }

        // CSV seed nudges (tiny)
        if ($seed && isset($scores[$seed])) {
            $scores[$seed] += 0.75;
            $reasons[] = 'csv-seed:'.$seed;
        }

        // Choose best
        arsort($scores);
        $bestCat   = array_key_first($scores);
        $bestScore = $scores[$bestCat];

        // If very close tie between carts, prefer Flavored when flavored token present
        if (in_array($bestCat, ['Extract Carts','Flavored Carts'], true) && $this->looksFlavored($hay)) {
            $bestCat = 'Flavored Carts'; $bestScore += 0.5;
        }

        // Confidence heuristic
        $secondScore = (count($scores) > 1) ? array_values($scores)[1] : 0.0;
        $confidence  = max(0.0, min(1.0, ($bestScore - $secondScore + 3) / 8)); // 0..1-ish

        // Final guard: carts may NEVER be Flower
        if ($this->looksCart($hay) && $bestCat === 'Flower') {
            $bestCat = $this->looksFlavored($hay) ? 'Flavored Carts' : 'Extract Carts';
            $confidence = max($confidence, 0.9);
            $reasons[] = 'guard:cart-not-flower';
        }

        // Fallback if somehow nothing matched
        if (!in_array($bestCat, $this->allowed, true)) {
            $bestCat = 'Extract';
        }

        return [$bestCat, round($confidence, 3), $reasons, $scores];
    }

    /* ---------------- helpers ---------------- */

    protected function v(array $row, array $keys): string
    {
        foreach ($keys as $k) {
            foreach ([$k, Str::lower($k)] as $try) {
                if (array_key_exists($try, $row) && trim((string)$row[$try]) !== '') {
                    return trim((string)$row[$try]);
                }
            }
        }
        return '';
    }

    protected function synonymCategory(?string $csvCat): ?string
    {
        $raw = Str::lower(trim((string)$csvCat));
        return $this->synonyms[$raw] ?? null;
    }

    /* ------- token detectors (tight, with safe negatives) ------- */

    protected function looksCart(string $hay): bool
    {
        return (bool) preg_match('/\b(cartridge|cart\b|510|vapes?|vape[- ]?pen|disposable|pod|pods|pax|stiiizy|airgraft|aio|all[- ]in[- ]one)\b/i', $hay);
    }
    protected function looksFlavored(string $hay): bool
    {
        if (str_contains($hay, 'buddies flavored')) return true;
        if (str_contains($hay, 'green leaf special')) return true;
        return (bool) preg_match('/\bflavor(?:ed|s)?\b|\bterp(?:s|ene)?s?\b/i', $hay);
    }
    protected function looksPreRoll(string $hay): bool
    {
        return (bool) preg_match('/\b(pre[- ]?rolls?|joints?)\b/i', $hay);
    }
    protected function looksInfused(string $hay): bool
    {
        return (bool) preg_match('/\b(infused|diamond|hash|rosin|k?ief)\b/i', $hay);
    }
    protected function looksConcentrate(string $hay): bool
    {
        return (bool) preg_match('/\b(budder|badder|sugar|crumble|hash(?!\s*infused)|rosin(?!.*cart)|wax|shatter|diamonds?|sauce|live\s*rosin|live\s*resin)\b/i', $hay)
            && !$this->looksCart($hay);
    }
    protected function looksEdible(string $hay): bool
    {
        return (bool) preg_match('/\b(edible|gummy|choc(?:olate)?|cookie|brownie|chew|candy|lozenge|mint|baked)\b/i', $hay);
    }
    protected function looksDrink(string $hay): bool
    {
        return (bool) preg_match('/\b(tincture|beverage|drink|soda|elixir|lemonade|tea|coffee|shot)\b/i', $hay);
    }
    protected function looksFlower(string $hay): bool
    {
        // never match if cart keywords exist (checked by caller)
        return (bool) preg_match('/\b(flower|bud|smalls|popcorn|eighth|quarter|half|ounce|oz|pre[- ]?pack)\b/i', $hay);
    }
    protected function looksTopical(string $hay): bool
    {
        return (bool) preg_match('/\b(topical|lotion|balm|salve|cream|ointment|patch)\b/i', $hay);
    }
    protected function looksClone(string $hay): bool
    {
        return (bool) preg_match('/\b(clone|clones|seed|seeds|plant)\b/i', $hay);
    }
    protected function looksAccessory(string $hay): bool
    {
        return (bool) preg_match('/\b(battery|charger|lighter|papers?|cones?|tips?|grinder|bong|rig|tray|torch)\b/i', $hay);
    }
    protected function looksApparel(string $hay): bool
    {
        return (bool) preg_match('/\b(tee|t[- ]?shirt|shirt|hoodie|sweatshirt|hat|cap|beanie)\b/i', $hay);
    }
    protected function looksHemp(string $hay): bool
    {
        return (bool) preg_match('/\bhemp\b/i', $hay);
    }

    protected function defaultRules(): array
    {
        return []; // weights are implemented directly in detectors above
    }
}
