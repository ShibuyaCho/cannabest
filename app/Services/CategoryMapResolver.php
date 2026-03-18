<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryMapResolver
{
    protected int $orgId;
    protected bool $autoCreate;
    /** @var array<int, array{name:string,id:int,lc:string}> */
    protected array $cats = [];
    /** @var array<string,int> */
    protected array $byLc = [];

    public function __construct(int $orgId, bool $autoCreate = true)
    {
        $this->orgId = $orgId;
        $this->autoCreate = $autoCreate;
        $this->reload();
    }

    protected function reload(): void
    {
        $rows = DB::table('categories')
            ->where('organization_id', $this->orgId)
            ->get(['id','name']);

        $this->cats = [];
        $this->byLc = [];
        foreach ($rows as $r) {
            $lc = Str::lower(trim($r->name));
            $this->cats[] = ['id' => (int)$r->id, 'name' => (string)$r->name, 'lc' => $lc];
            $this->byLc[$lc] = (int)$r->id;
        }
    }

    /**
     * Resolve/ensure a category id for a canonical target (e.g. "Flavored Carts").
     * $hay can be the product/brand text to help heuristic matching against existing names.
     */
    public function idFor(string $canonical, ?string $hay = null): int
    {
        $canonical = $this->canon($canonical);
        $hay = Str::lower(trim((string)$hay));

        // 1) Exact (case-insensitive) match
        if (isset($this->byLc[$canonical])) {
            return $this->byLc[$canonical];
        }

        // 2) Try to map canonical to an existing similar category by tokens
        $matchId = $this->findHeuristic($canonical);
        if ($matchId) return $matchId;

        // 3) Last-ditch: if haystack suggests a different existing cat name, use it
        if ($hay !== '') {
            $id = $this->matchByHay($hay, $canonical);
            if ($id) return $id;
        }

        // 4) Create missing canonical category (optional)
        if ($this->autoCreate) {
            $id = $this->create($canonical);
            return $id;
        }

        // 5) Fallbacks (avoid blind "Flower" where possible)
        return $this->fallbackId($canonical);
    }

    protected function canon(string $name): string
    {
        return Str::lower(trim($name));
    }

    protected function looksCart(string $s): bool
    {
        return (bool) preg_match('/\b(cartridge|cart\b|510|vapes?|vape[- ]?pen|disposable|aio|all[- ]in[- ]one|pod|pods|pax|stiiizy|airgraft)\b/i', $s);
    }
    protected function looksFlavored(string $s): bool
    {
        return (bool) (str_contains($s, 'buddies flavored') || str_contains($s, 'green leaf special') || preg_match('/\bflavor(?:ed|s)?\b/i', $s));
    }
    protected function looksJoint(string $s): bool
    {
        return (bool) preg_match('/\b(pre[- ]?rolls?|joints?)\b/i', $s);
    }
    protected function looksInfused(string $s): bool
    {
        return (bool) preg_match('/\b(infused|diamond|hash|rosin|k?ief)\b/i', $s);
    }
    protected function looksConcentrate(string $s): bool
    {
        return (bool) preg_match('/\b(budder|badder|sugar|crumble|hash(?!\s*infused)|rosin(?!.*cart)|wax|shatter|diamonds?|sauce|live\s*rosin|live\s*resin)\b/i', $s);
    }
    protected function looksExtract(string $s): bool
    {
        return (bool) preg_match('/\b(rso|syringe|distillate(?!\s*cart)|capsules?)\b/i', $s);
    }
    protected function looksEdible(string $s): bool
    {
        return (bool) preg_match('/\b(edible|gummy|choc(?:olate)?|cookie|brownie|chew|candy|lozenge|mint|baked)\b/i', $s);
    }
    protected function looksDrink(string $s): bool
    {
        return (bool) preg_match('/\b(tincture|beverage|drink|soda|elixir|lemonade|tea|coffee|shot)\b/i', $s);
    }
    protected function looksTopical(string $s): bool
    {
        return (bool) preg_match('/\b(topical|lotion|balm|salve|cream|ointment|patch)\b/i', $s);
    }
    protected function looksClone(string $s): bool
    {
        return (bool) preg_match('/\b(clone|clones|seed|seeds|plant)\b/i', $s);
    }
    protected function looksAccessory(string $s): bool
    {
        return (bool) preg_match('/\b(battery|charger|lighter|papers?|cones?|tips?|grinder|bong|rig|tray|torch)\b/i', $s);
    }
    protected function looksApparel(string $s): bool
    {
        return (bool) preg_match('/\b(tee|t[- ]?shirt|shirt|hoodie|sweatshirt|hat|cap|beanie)\b/i', $s);
    }
    protected function looksFlower(string $s): bool
    {
        return (bool) preg_match('/\b(flower|bud|smalls|popcorn|eighth|quarter|half|ounce|oz|pre[- ]?pack)\b/i', $s);
    }

    protected function findHeuristic(string $canonicalLc): ?int
    {
        // Try to align canonical to an existing cat name by keyword
        foreach ($this->cats as $c) {
            $n = $c['lc'];
            switch ($canonicalLc) {
                case 'flavored carts':
                    if ($this->looksCart($n) && $this->looksFlavored($n)) return $c['id'];
                    break;
                case 'extract carts':
                    if ($this->looksCart($n)) return $c['id'];
                    break;
                case 'infused joints':
                    if ($this->looksJoint($n) && $this->looksInfused($n)) return $c['id'];
                    break;
                case 'joints':
                    if ($this->looksJoint($n)) return $c['id'];
                    break;
                case 'concentrate':
                    if ($this->looksConcentrate($n)) return $c['id'];
                    break;
                case 'extract':
                    if ($this->looksExtract($n)) return $c['id'];
                    break;
                case 'edibles':
                    if ($this->looksEdible($n)) return $c['id'];
                    break;
                case 'drinks/tinctures':
                    if ($this->looksDrink($n)) return $c['id'];
                    break;
                case 'topicals':
                    if ($this->looksTopical($n)) return $c['id'];
                    break;
                case 'clones':
                    if ($this->looksClone($n)) return $c['id'];
                    break;
                case 'accessories':
                    if ($this->looksAccessory($n)) return $c['id'];
                    break;
                case 'apparel':
                    if ($this->looksApparel($n)) return $c['id'];
                    break;
                case 'hemp':
                    if (str_contains($n, 'hemp')) return $c['id'];
                    break;
                case 'flower':
                    if ($this->looksFlower($n)) return $c['id'];
                    break;
            }
        }

        // Special: if canonical is flavored carts but we only have a generic carts bucket
        if ($canonicalLc === 'flavored carts') {
            return $this->byLc['extract carts'] ?? null;
        }

        return null;
    }

    protected function matchByHay(string $hay, string $canonicalLc): ?int
    {
        foreach ($this->cats as $c) {
            $n = $c['lc'];

            if ($this->looksCart($hay)) {
                // carts must never land in flower
                if ($canonicalLc === 'flavored carts') {
                    if ($this->looksFlavored($hay) && ($this->looksCart($n) || $n === 'flavored carts')) return $c['id'];
                }
                if ($this->looksCart($n)) return $c['id'];
            }

            if ($this->looksJoint($hay) && !$this->looksCart($hay)) {
                if ($this->looksInfused($hay) && ($this->looksJoint($n) || $n === 'infused joints')) return $c['id'];
                if ($this->looksJoint($n)) return $c['id'];
            }

            if ($this->looksConcentrate($hay) && !$this->looksCart($hay)) {
                if ($this->looksConcentrate($n)) return $c['id'];
            }

            if ($this->looksExtract($hay) && !$this->looksCart($hay)) {
                if ($this->looksExtract($n)) return $c['id'];
            }

            if ($this->looksEdible($hay) && $this->looksEdible($n)) return $c['id'];
            if ($this->looksDrink($hay) && $this->looksDrink($n)) return $c['id'];
            if ($this->looksTopical($hay) && $this->looksTopical($n)) return $c['id'];
            if ($this->looksClone($hay) && $this->looksClone($n)) return $c['id'];
            if ($this->looksAccessory($hay) && $this->looksAccessory($n)) return $c['id'];
            if ($this->looksApparel($hay) && $this->looksApparel($n)) return $c['id'];
            if ($this->looksFlower($hay) && $this->looksFlower($n)) return $c['id'];
        }
        return null;
    }

    protected function create(string $canonicalLc): int
    {
        $name = $this->labelFor($canonicalLc);
        $now = now()->toDateTimeString();
        $id = (int) DB::table('categories')->insertGetId([
            'organization_id' => $this->orgId,
            'name'            => $name,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
        $this->reload();
        return $id;
    }

    protected function labelFor(string $lc): string
    {
        // Friendly capitalization
        $map = [
            'flower' => 'Flower',
            'joints' => 'Joints',
            'extract' => 'Extract',
            'concentrate' => 'Concentrate',
            'infused joints' => 'Infused Joints',
            'extract carts' => 'Extract Carts',
            'flavored carts' => 'Flavored Carts',
            'edibles' => 'Edibles',
            'drinks/tinctures' => 'Drinks/Tinctures',
            'clones' => 'Clones',
            'accessories' => 'Accessories',
            'apparel' => 'Apparel',
            'hemp' => 'Hemp',
            'topicals' => 'Topicals',
        ];
        return $map[$lc] ?? Str::title($lc);
    }

    protected function fallbackId(string $canonicalLc): int
    {
        // Prefer closest neighbor fallbacks, never blindly pick Flower unless it matches intent.
        $prefs = match ($canonicalLc) {
            'flavored carts' => ['extract carts','extract'],
            'extract carts'  => ['extract'],
            'infused joints' => ['joints','flower'],
            'joints'         => ['flower'],
            'concentrate'    => ['extract'],
            'extract'        => ['concentrate'],
            'edibles'        => ['drinks/tinctures'],
            'drinks/tinctures'=> ['edibles'],
            default          => [],
        };

        foreach ($prefs as $want) {
            if (isset($this->byLc[$want])) return $this->byLc[$want];
        }

        if (isset($this->byLc[$canonicalLc])) return $this->byLc[$canonicalLc];
        if (isset($this->byLc['extract']))     return $this->byLc['extract'];
        if (isset($this->byLc['flower']))      return $this->byLc['flower'];

        // last resort: first category in org
        return $this->cats[0]['id'] ?? 0;
    }
}
