<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class RelinkMetrcLabels extends Command
{
    /**
     * Interactively relink METRC labels per item by exact price match (with optional widening).
     * Scope by --day (YYYY-MM-DD) or --month (YYYY-MM); otherwise defaults to items before --date.
     */
    protected $signature = 'labels:relink
        {--day= : Limit to a specific calendar day, format YYYY-MM-DD}
        {--month= : Limit to a calendar month, format YYYY-MM}
        {--date=2025-10-21 : Legacy fallback: items with sales before this date (ignored if --day/--month set)}
        {--include-existing=0 : Include items that already have metrc_package_label}
        {--org= : Limit to an organization_id}
        {--dry=0 : Preview only (no writes)}';

    protected $description = 'Interactively relink METRC labels per sale item by exact receipt price.';

    public function handle(): int
    {
        // Basic table sanity
        if (!Schema::hasTable('sale_items') || !Schema::hasTable('sales') || !Schema::hasTable('inventories')) {
            $this->error('Required tables missing (sale_items, sales, inventories).');
            return 1;
        }

        $includeExisting = (int)$this->option('include-existing') === 1;
        $orgFilter       = $this->option('org') ? (int)$this->option('org') : null;
        $dry             = (int)$this->option('dry') === 1;

        // Resolve time window
        $useDay   = false;
        $useMonth = false;
        $day      = null;      // Carbon
        $startAt  = null;      // Carbon
        $endAt    = null;      // Carbon
        $cutoff   = null;      // Carbon

        if ($this->option('day')) {
            try {
                $day = Carbon::createFromFormat('Y-m-d', $this->option('day'));
                $useDay = true;
            } catch (\Throwable $e) {
                $this->error("Invalid --day format. Use YYYY-MM-DD (e.g. 2025-10-20).");
                return 1;
            }
        } elseif ($this->option('month')) {
            try {
                $startAt = Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth();
                $endAt   = (clone $startAt)->endOfMonth();
                $useMonth = true;
            } catch (\Throwable $e) {
                $this->error("Invalid --month format. Use YYYY-MM (e.g. 2025-10).");
                return 1;
            }
        } else {
            $cutoff = Carbon::parse($this->option('date'))->startOfDay();
        }

        // Build item query
        $q = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->when(!$includeExisting, fn($x) => $x->whereNull('si.metrc_package_label'))
            ->when($orgFilter, fn($x) => $x->where('u.organization_id', $orgFilter))
            ->when($useDay,   fn($x) => $x->whereDate('s.created_at', '=', $day->toDateString()))
            ->when($useMonth, fn($x) => $x->whereBetween('s.created_at', [$startAt, $endAt]))
            ->when(!$useDay && !$useMonth, fn($x) => $x->where('s.created_at', '<', $cutoff))
            ->orderBy('s.created_at')
            ->select([
                'si.id as sale_item_id',
                'si.sale_id',
                'si.product_id',
                'si.price',
                'si.unit_price',
                'si.price_is_line_total',
                'si.quantity',
                'si.metrc_package_label',
                's.created_at as sale_at',
                'u.organization_id as org_id',
            ]);

        $items = $q->get();

        if ($items->isEmpty()) {
            if ($useDay)   { $this->info("No sale items found on {$day->toDateString()}."); }
            elseif ($useMonth) { $this->info("No sale items found between {$startAt} and {$endAt}."); }
            else { $this->info("No sale items found before {$cutoff->toDateString()}."); }
            return 0;
        }

        $this->info(
            $useDay
                ? "Found {$items->count()} sale items on {$day->toDateString()}."
                : ($useMonth
                    ? "Found {$items->count()} sale items in {$startAt->format('F Y')}."
                    : "Found {$items->count()} sale items before {$cutoff->toDateString()}.")
        );
        $this->newLine();

        foreach ($items as $it) {
            $qty = max((float)$it->quantity, 0.0001);

            // Compute "receipt unit price":
            // - if unit_price is present, use it
            // - else if price_is_line_total=1, then price is subtotal -> divide by qty
            // - else price already represents unit amount
            $unit = !is_null($it->unit_price)
                ? (float)$it->unit_price
                : ((int)$it->price_is_line_total === 1 ? (float)$it->price / $qty : (float)$it->price);
            $unit = round($unit, 2);

            $saleAt = Carbon::parse($it->sale_at);

            $this->line(str_repeat('-', 78));
            $this->line("Sale #{$it->sale_id}  Item #{$it->sale_item_id}  Org {$it->org_id}  Date {$saleAt}");
            $this->line("Qty: {$it->quantity}   Unit: $".number_format($unit, 2)."   Current Label: ".($it->metrc_package_label ?? '—'));

            // Start with exact price match; widen only on request
            $tolerance   = 0.00;
            $maxTolSteps = [0.00, 0.01, 0.02, 0.05, 0.10];
            $tolIdx      = 0;

            while (true) {
                [$cands, $count] = $this->findInventoryCandidates(
                    orgId: (int)$it->org_id,
                    unit: $unit,
                    saleAt: $saleAt,
                    tolerance: $tolerance
                );

                if ($count === 0) {
                    $this->warn("No inventories @ original_price ~ $".number_format($unit, 2)." (±{$tolerance}).");
                    $choice = $this->ask("[w] widen | [m] manual label | [s] skip | [q] quit", 'w');
                    if ($choice === 'q') { return 0; }
                    if ($choice === 's') { break; }
                    if ($choice === 'm') {
                        $label = $this->ask('Enter Package Label exactly');
                        if ($label) { $this->applyLabel($it->sale_item_id, $label, $dry); }
                        break;
                    }
                    $tolIdx = min($tolIdx + 1, count($maxTolSteps) - 1);
                    $tolerance = $maxTolSteps[$tolIdx];
                    continue;
                }

                // List candidates
                $this->line("Candidates (price match ±{$tolerance})");
                foreach ($cands as $idx => $c) {
                    $this->line(sprintf(
                        " [%d] inv:%-7s  Label:%-24s  orig:$%s  created:%s  Δt:%5s min",
                        $idx,
                        $c->id,
                        $c->Label,
                        number_format((float)$c->original_price, 2),
                        (string)$c->created_at,
                        $c->delta_minutes
                    ));
                }

                $choice = $this->ask("Pick index [0-".($count-1)."] | [w] widen | [m] manual | [s] skip | [q] quit", '0');
                if ($choice === 'q') { return 0; }
                if ($choice === 's') { break; }
                if ($choice === 'w') {
                    $tolIdx = min($tolIdx + 1, count($maxTolSteps) - 1);
                    $tolerance = $maxTolSteps[$tolIdx];
                    continue;
                }
                if ($choice === 'm') {
                    $label = $this->ask('Enter Package Label exactly');
                    if ($label) { $this->applyLabel($it->sale_item_id, $label, $dry); }
                    break;
                }

                if (!is_numeric($choice) || (int)$choice < 0 || (int)$choice >= $count) {
                    $this->warn('Invalid choice. Try again.');
                    continue;
                }

                $chosen = $cands[(int)$choice];
                $this->applyLabel($it->sale_item_id, $chosen->Label, $dry);
                break;
            }
        }

        $this->info('Done.');
        return 0;
    }

    /**
     * Find labeled inventories for an org whose original_price matches the unit price
     * within the given tolerance (exact 2-decimal match when tolerance=0),
     * ordered by proximity of inventory.created_at to the sale timestamp.
     *
     * @return array{0:\Illuminate\Support\Collection,1:int}
     */
    protected function findInventoryCandidates(int $orgId, float $unit, Carbon $saleAt, float $tolerance): array
    {
        $q = DB::table('inventories as i')
            ->where('i.organization_id', $orgId)
            ->whereNotNull('i.Label');

        if (Schema::hasColumn('inventories', 'original_price')) {
            if ($tolerance == 0.0) {
                // Exact 2-decimal match
                $q->whereRaw('ROUND(i.original_price, 2) = ?', [$unit]);
            } else {
                $q->whereBetween('i.original_price', [$unit - $tolerance, $unit + $tolerance]);
            }
        }

        $rows = $q->select(['i.id', 'i.Label', 'i.original_price', 'i.created_at'])
            ->orderByRaw(
                'ABS(TIMESTAMPDIFF(SECOND, COALESCE(i.created_at, ?), ?)) ASC',
                [$saleAt->toDateTimeString(), $saleAt->toDateTimeString()]
            )
            ->orderByDesc('i.id')
            ->limit(25)
            ->get();

        // Add Δt (minutes) for display
        $rows = $rows->map(function ($r) use ($saleAt) {
            $r->delta_minutes = $r->created_at ? abs(Carbon::parse($r->created_at)->diffInMinutes($saleAt)) : null;
            return $r;
        });

        return [$rows, $rows->count()];
    }

    /**
     * Write the chosen label to sale_items (indexed column + minimal JSON snapshot).
     */
    protected function applyLabel(int $saleItemId, string $label, bool $dry): void
    {
        if ($dry) {
            $this->info("[DRY] sale_item {$saleItemId} -> {$label}");
            return;
        }

        $payload = [
            'metrc_package_label' => $label,
            'updated_at'          => now(),
        ];

        // Only add JSON snapshot if column exists (it does in your schema)
        if (Schema::hasColumn('sale_items', 'metrc_package')) {
            $payload['metrc_package'] = json_encode(['PackageLabel' => $label]);
        }

        DB::table('sale_items')->where('id', $saleItemId)->update($payload);

        $this->info("OK → sale_item {$saleItemId}: PackageLabel={$label}");
    }
}
