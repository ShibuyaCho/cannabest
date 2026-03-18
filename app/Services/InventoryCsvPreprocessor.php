<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class InventoryCsvPreprocessor
{
    protected array $allowed;
    protected array $synonyms;
    protected array $flavoredSignals;
    protected array $rules;

    public function __construct()
    {
        $conf = Config::get('inventory_map', []);
        $this->allowed         = $conf['allowed'] ?? [];
        $this->synonyms        = $conf['synonyms'] ?? [];
        $this->flavoredSignals = $conf['flavored_cart_signals'] ?? [];
        $this->rules           = $conf['rules'] ?? [];
    }

    /**
     * Create a cleaned CSV that overwrites the Category column with our best guess.
     * Returns [$destPath, $stats]
     */
    public function preprocess(string $srcPath, string $destPath): array
    {
        if (!is_file($srcPath) || !is_readable($srcPath)) {
            throw new \RuntimeException("CSV not readable: {$srcPath}");
        }

        $in  = fopen($srcPath, 'rb');
        $out = fopen($destPath, 'wb');
        if (!$in || !$out) throw new \RuntimeException('Unable to open CSV streams');

        $header = fgetcsv($in) ?: [];
        $map    = [];
        foreach ($header as $i => $h) {
            $map[Str::lower(trim($h))] = $i;
        }

        // Ensure Category column exists
        if (!array_key_exists('category', $map)) {
            $header[] = 'Category';
            $map['category'] = count($header)-1;
        }

        fputcsv($out, $header);

        $stats = ['rows'=>0, 'changed'=>0, 'kept'=>0];
        while (($row = fgetcsv($in)) !== false) {
            $stats['rows']++;

            $catIdx = $map['category'];
            $name   = $this->val($row, $map, ['product name','name']);
            $var    = $this->val($row, $map, ['variant name']);
            $brand  = $this->val($row, $map, ['brand']);
            $csvCat = $this->val($row, $map, ['category']);

            $display = trim(preg_replace('/\s+/', ' ', trim($name.' '.$var)));
            $hay     = Str::lower(trim(($csvCat ?? '').' '.$display.' '.$brand));

            // 1) HARD short-circuit: anything that looks like a cart ⇒ cart (beats CSV=Flower)
            if ($this->looksLikeCart($hay)) {
                $newCat = $this->looksFlavored($hay) ? 'Flavored Carts' : 'Extract Carts';
            } else {
                // 2) Brand/name flavored hints
                $newCat = null;
                foreach ($this->flavoredSignals as $sig) {
                    if ($sig && Str::contains($hay, Str::lower($sig))) {
                        $newCat = 'Flavored Carts';
                        break;
                    }
                }

                // 3) Rule engine (priority order)
                if (!$newCat) {
                    foreach ($this->rules as $rule) {
                        $rx  = Arr::get($rule, 'rx');
                        $cat = Arr::get($rule, 'cat');
                        if ($rx && $cat && @preg_match($rx, $hay) && preg_match($rx, $hay)) {
                            $newCat = $cat;
                            break;
                        }
                    }
                }

                // 4) Direct CSV synonyms
                if (!$newCat) {
                    $raw = Str::lower(trim((string)$csvCat));
                    if ($raw && isset($this->synonyms[$raw])) $newCat = $this->synonyms[$raw];
                }

                // 5) If CSV exactly matches an allowed one, keep it
                if (!$newCat) {
                    foreach ($this->allowed as $exact) {
                        if (Str::lower($exact) === Str::lower((string)$csvCat)) {
                            $newCat = $exact; break;
                        }
                    }
                }

                // 6) Final fallback
                if (!$newCat) $newCat = 'Extract';
            }

            if (!isset($row[$catIdx])) $row[$catIdx] = '';
            $oldCat = trim((string) $row[$catIdx]);

            if ($newCat && $newCat !== $oldCat) {
                $row[$catIdx] = $newCat;
                $stats['changed']++;
            } else {
                $stats['kept']++;
            }

            fputcsv($out, $row);
        }

        fclose($in);
        fclose($out);
        return [$destPath, $stats];
    }

    protected function looksLikeCart(string $hay): bool
    {
        return (bool) preg_match(
            '/\b(cartridge|cart\b|510|vapes?|vape[- ]?pen|disposable|aio|all[- ]in[- ]one|pod|pods|pax|stiiizy|airgraft)\b/i',
            $hay
        );
    }

    protected function looksFlavored(string $hay): bool
    {
        return (bool)(
            preg_match('/\bflavor(?:ed|s)?\b/i', $hay)
            || Str::contains($hay, 'buddies flavored')
            || Str::contains($hay, 'green leaf special')
            || Str::contains($hay, 'gls')
        );
    }

    protected function val(array $row, array $map, array $candidates): string
    {
        foreach ($candidates as $col) {
            $idx = $map[$col] ?? null;
            if ($idx !== null && isset($row[$idx]) && trim($row[$idx]) !== '') {
                return trim((string)$row[$idx]);
            }
        }
        return '';
    }
}
