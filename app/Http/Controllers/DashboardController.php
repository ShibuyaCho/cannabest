<?php

namespace App\Http\Controllers;

use App\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder as EBuilder;
use Illuminate\Database\Query\Builder as QBuilder;

class DashboardController extends Controller
{
    /* ===== Config to mirror your receipt math when needed ===== */
    protected array $NON_TAX_CATS = ['hemp','apparel','accessories']; // lowercased
    protected int   $TAX_RATE_PCT = 20; // consumer only

    /* =======================================================================
     |  ROUTE: /org-dashboard  (server only returns IDs + dates)
     * ======================================================================= */
    public function index(Request $r)
    {
        [$startDate, $endDate, $startLocal, $endLocal, $storeTz] = $this->localWindow($r);

        // EXACTLY like Sales Index (status=1 + org scoping)
        $q = DB::table('sales as s')
            ->where('s.status', 1)
            ->whereBetween('s.created_at', [$startLocal, $endLocal]);

        $this->applyOrgScopeToPosQuery($q);

        // Be strict about excluding cancelled/voided if columns exist
        foreach ([
            'is_cancelled','is_canceled','cancelled','canceled',
            'void','is_void','voided','is_voided','deleted','is_deleted'
        ] as $col) {
            if (Schema::hasColumn('sales', $col)) {
                $q->where(function($w) use ($col) {
                    $w->whereNull('s.'.$col)->orWhere('s.'.$col, 0);
                });
            }
        }

        // Detect a payment column like Sales page does
        $payCol = null;
        foreach (['payment_type','pay_type','payment_method','payment','type'] as $cand) {
            if (Schema::hasColumn('sales', $cand)) { $payCol = 's.'.$cand; break; }
        }

        $selects = ['s.id'];
        if ($payCol) $selects[] = DB::raw("$payCol as _pay");

        $rows = $q->orderBy('s.created_at','asc')->get($selects);

        $saleIds    = [];
        $salePayMap = [];
        foreach ($rows as $row) {
            $saleIds[] = $row->id;

            $p = strtolower((string)($row->_pay ?? ''));
            // Same classification as Sales index:
            // Cash if contains "cash"; Card if contains "card|debit|credit"; else Other
            $class = $p === ''
                ? 'Other'
                : (str_contains($p,'cash') ? 'Cash'
                    : ((str_contains($p,'card') || str_contains($p,'debit') || str_contains($p,'credit')) ? 'Card' : 'Other'));
            $salePayMap[$row->id] = $class;
        }

        return view('backend.dashboard.index', [
            'saleIds'                     => $saleIds,
            'salePayMap'                  => $salePayMap, // <-- used by the Blade JS to infer tender when JSON split missing
            'start'                       => $startDate,
            'end'                         => $endDate,
            'receiptUrlTemplate'          => url('/sales/receipt/__SALE__').'?print=1',
            'receiptNumbersUrlTemplate'   => url('/sales/receipt/__SALE__/numbers'), // JSON-first endpoint
        ]);
    }

    /* =======================================================================
     |  JSON: Summary KPIs (Eloquent, safe eager)
     * ======================================================================= */
    public function summary(Request $r)
{
    [$startUtc, $endUtc] = $this->range($r);
    $c       = $this->cols();
    $catMap  = $this->categoryMap();

    // ---- Existing KPI math (unchanged) ----
    $base = $this->applyCompletedEloquent(
        Sale::query()->whereBetween('created_at', [$startUtc, $endUtc])
    );

    if (!empty($c['salesStoreCol']) && $r->filled('store')) {
        $base->where($c['salesStoreCol'], $r->query('store'));
    }

    $visits = (clone $base)->count('id');

    $sumSubC = $sumLineDiscC = $sumOrderDiscC = $sumTaxC = $sumDueC = 0;

    $base->with($this->saleEager())
        ->orderBy('id')
        ->chunkById(500, function ($chunk) use (&$sumSubC,&$sumLineDiscC,&$sumOrderDiscC,&$sumTaxC,&$sumDueC,$catMap) {
            foreach ($chunk as $sale) {
                $b = $this->breakdownCents($sale, $catMap);
                $sumSubC      += $b['subC'];
                $sumLineDiscC += $b['lineDiscC'];
                $sumOrderDiscC+= $b['orderDiscC'];
                $sumTaxC      += $b['salesTaxC'];
                $sumDueC      += $b['dueC'];
            }
        });

    $discountsC = $sumLineDiscC + $sumOrderDiscC;
    $grossC     = $sumDueC; // post-tax collected
    $aov        = $visits ? $this->fromCents($sumDueC-$sumTaxC) / $visits : 0;

    // ---- NEW: Tender splits (Cash / Card / Other) ----
    $cashC = $cardC = $otherC = 0;

    $payCol = $this->detectPayCol();
    if ($payCol) {
        $qb = \DB::table('sales as s')
            ->whereBetween('s.created_at', [$startUtc, $endUtc]);

        $this->applyCompletedQB($qb, 's');

        if (!empty($c['salesStoreCol']) && $r->filled('store')) {
            $qb->where("s.{$c['salesStoreCol']}", $r->query('store'));
        }

        // Use the same collected expression as the rest of the KPIs
        $qb->selectRaw("s.id as id, {$payCol} as pay, {$c['salesCollected']} as due")
           ->orderBy('s.id')
           ->chunkById(500, function($chunk) use (&$cashC,&$cardC,&$otherC) {
               foreach ($chunk as $row) {
                   $dueC = (int) round(((float)($row->due ?? 0)) * 100); // to cents
                   $class = $this->classifyPay($row->pay ?? '');

                   if ($class === 'Cash') {
                       // We don't have paid/change on the server; approximate with due
                       $cashC += max(0, $dueC);
                   } elseif ($class === 'Card') {
                       // Mirror Sales page: round up (cashback) to nearest $10
                       $cardC += $this->ceil10Cents($dueC);
                   } else {
                       $otherC += max(0, $dueC);
                   }
               }
           }, 's.id', 'id');
    }

    return response()->json([
        'gross'        => round($this->fromCents($grossC), 2),
        'discounts'    => round($this->fromCents($discountsC), 2),
        'tax'          => round($this->fromCents($sumTaxC), 2),
        'collected'    => round($this->fromCents($sumDueC), 2),
        'visits'       => $visits,
        'aov'          => round($aov, 2),
        'lostRevenue'  => round($this->fromCents($discountsC), 2),
        'returnRate'   => $this->returnRate($startUtc, $endUtc, $r, $c, $visits),

        // NEW fields the dashboard can render directly
        'cash'         => round($this->fromCents($cashC), 2),
        'card'         => round($this->fromCents($cardC), 2),
        'other'        => round($this->fromCents($otherC), 2),
        'expectedCash' => round($this->fromCents($cashC), 2),
    ]);
}


    /* =======================================================================
     |  JSON: Sales by Day
     * ======================================================================= */
    public function salesByDay(Request $r)
    {
        [$startUtc, $endUtc, $tz] = $this->range($r);
        $salesStoreCol = $this->detectStoreCol('sales');
        $catMap = $this->categoryMap();
        $byDay = [];

        $query = $this->applyCompletedEloquent(
            Sale::with($this->saleEager())->whereBetween('created_at', [$startUtc, $endUtc])
        );

        if ($salesStoreCol && $r->filled('store')) {
            $query->where($salesStoreCol, $r->query('store'));
        }

        $query->orderBy('id')->chunkById(500, function($chunk) use (&$byDay,$tz,$catMap){
            foreach ($chunk as $sale) {
                $d = Carbon::parse($sale->created_at)->setTimezone($tz)->toDateString();
                $b = $this->breakdownCents($sale, $catMap);
                if (!isset($byDay[$d])) $byDay[$d] = ['collectedC'=>0, 'txns'=>0];
                $byDay[$d]['collectedC'] += $b['dueC'];
                $byDay[$d]['txns']++;
            }
        });

        ksort($byDay);
        $rows = [];
        foreach ($byDay as $d => $row) {
            $rows[] = [
                'd'         => $d,
                'collected' => round($this->fromCents($row['collectedC']), 2),
                'txns'      => $row['txns'],
            ];
        }
        return response()->json($rows);
    }

    /* =======================================================================
     |  JSON: Hourly Heatmap (visits only)
     * ======================================================================= */
    public function hourlyHeatmap(Request $r)
    {
        [$startUtc, $endUtc, $tz] = $this->range($r);
        $c = $this->cols();

        $q = DB::table('sales as s')
            ->whereBetween('s.created_at', [$startUtc, $endUtc]);

        $this->applyCompletedQB($q, 's');

        if ($c['salesStoreCol'] && $r->filled('store')) {
            $q->where("s.{$c['salesStoreCol']}", $r->query('store'));
        }

        $rows = $q->selectRaw("
                WEEKDAY(CONVERT_TZ(s.created_at,'UTC',?)) as weekday,
                HOUR(CONVERT_TZ(s.created_at,'UTC',?))     as hour,
                COUNT(*) as visits
            ", [$tz,$tz])
            ->groupBy('weekday','hour')
            ->orderBy('weekday')->orderBy('hour')
            ->get();

        return response()->json($rows);
    }

    /* =======================================================================
     |  JSON: Category Mix
     * ======================================================================= */
    public function categoryMix(Request $r)
    {
        [$startUtc, $endUtc] = $this->range($r);
        $salesStoreCol = $this->detectStoreCol('sales');
        $catMap = $this->categoryMap();

        $acc = []; // cat => ['qty'=>, 'revC'=>]

        $query = $this->applyCompletedEloquent(
            Sale::with($this->saleEager())->whereBetween('created_at', [$startUtc, $endUtc])
        );

        if ($salesStoreCol && $r->filled('store')) {
            $query->where($salesStoreCol, $r->query('store'));
        }

        $query->orderBy('id')->chunkById(300, function($chunk) use (&$acc,$catMap){
            foreach ($chunk as $sale) {
                foreach (($sale->items ?? []) as $it) {
                    if (!$it->relationLoaded('inventory')) continue;
                    $inv = $it->inventory;
                    $cat = $inv && isset($catMap[$inv->category_id]) ? $catMap[$inv->category_id] : 'uncategorized';
                    $lineC = $this->netLineCents($it, $cat);
                    if (!isset($acc[$cat])) $acc[$cat] = ['qty'=>0,'revC'=>0];
                    $acc[$cat]['qty']  += (float)($it->quantity ?? 0);
                    $acc[$cat]['revC'] += $lineC;
                }
            }
        });

        uasort($acc, fn($a,$b)=>$b['qty']<=>$a['qty']);
        $rows = [];
        foreach ($acc as $cat=>$v) {
            $rows[] = [
                'category' => $cat ?: 'Uncategorized',
                'qty'      => $v['qty'],
                'units'    => $v['qty'],
                'revenue'  => round($this->fromCents($v['revC']), 2),
            ];
        }
        return response()->json($rows);
    }

    /* =======================================================================
     |  JSON: Discounts over time
     * ======================================================================= */
    public function discounts(Request $r)
    {
        [$startUtc, $endUtc, $tz] = $this->range($r);
        $salesStoreCol = $this->detectStoreCol('sales');
        $catMap = $this->categoryMap();

        $byDay = []; // d => cents

        $query = $this->applyCompletedEloquent(
            Sale::with($this->saleEager())->whereBetween('created_at', [$startUtc, $endUtc])
        );

        if ($salesStoreCol && $r->filled('store')) {
            $query->where($salesStoreCol, $r->query('store'));
        }

        $query->orderBy('id')->chunkById(500, function($chunk) use (&$byDay,$tz,$catMap){
            foreach ($chunk as $sale) {
                $d = Carbon::parse($sale->created_at)->setTimezone($tz)->toDateString();
                $b = $this->breakdownCents($sale, $catMap);
                $discC = $b['lineDiscC'] + $b['orderDiscC'];
                $byDay[$d] = ($byDay[$d] ?? 0) + $discC;
            }
        });

        ksort($byDay);
        $rows = [];
        foreach ($byDay as $d=>$cents) {
            $rows[] = ['d'=>$d, 'discounts'=>round($this->fromCents($cents),2), 'loyalty'=>0.00];
        }
        return response()->json($rows);
    }

    /* =======================================================================
     |  JSON: Top Products
     * ======================================================================= */
    public function topProducts(Request $r)
    {
        [$startUtc, $endUtc] = $this->range($r);
        $limit = (int)($r->query('limit', 15)); if ($limit<=0 || $limit>100) $limit = 15;

        $salesStoreCol = $this->detectStoreCol('sales');
        $catMap = $this->categoryMap();

        $acc = []; // key => ['label','producer','qty','revC']

        $query = $this->applyCompletedEloquent(
            Sale::with($this->saleEager())->whereBetween('created_at', [$startUtc, $endUtc])
        );

        if ($salesStoreCol && $r->filled('store')) {
            $query->where($salesStoreCol, $r->query('store'));
        }

        $query->orderBy('id')->chunkById(300, function($chunk) use (&$acc,$catMap){
            foreach ($chunk as $sale) {
                foreach (($sale->items ?? []) as $it) {
                    if (!$it->relationLoaded('inventory')) continue;
                    $inv = $it->inventory;
                    $label    = $inv->Label ?? '';
                    $producer = $inv->producer ?? '';
                    $cat      = $inv && isset($catMap[$inv->category_id]) ? $catMap[$inv->category_id] : '';
                    $lineC    = $this->netLineCents($it, $cat);

                    $key = $label.'|'.$producer;
                    if (!isset($acc[$key])) $acc[$key] = ['label'=>$label,'producer'=>$producer,'qty'=>0,'revC'=>0];
                    $acc[$key]['qty']  += (float)($it->quantity ?? 0);
                    $acc[$key]['revC'] += $lineC;
                }
            }
        });

        usort($acc, fn($a,$b)=>$b['revC']<=>$a['revC']);
        $acc = array_slice($acc, 0, $limit);

        $rows = [];
        foreach ($acc as $row) {
            $rows[] = [
                'label'    => $row['label'],
                'producer' => $row['producer'],
                'qty'      => $row['qty'],
                'revenue'  => round($this->fromCents($row['revC']), 2),
            ];
        }
        return response()->json($rows);
    }

    /* =======================================================================
     |  JSON: Voids / Returns (SQL)
     * ======================================================================= */
    public function voidsReturns(Request $r)
    {
        [$startUtc, $endUtc] = $this->range($r);
        $c = $this->cols();

        $q = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('inventories as i', 'i.id', '=', 'si.product_id')
            ->whereBetween('s.created_at', [$startUtc, $endUtc]);

        $this->applyCompletedQB($q, 's');

        if ($c['salesStoreCol'] && $r->filled('store')) {
            $q->where("s.{$c['salesStoreCol']}", $r->query('store'));
        }

        $typeExpr = "CASE WHEN si.quantity<0 THEN 'Return' ELSE 'Adj' END";
        if ($c['itemVoidFlag'])    $typeExpr = "CASE WHEN {$c['itemVoidFlag']}=1 THEN 'Void' WHEN si.quantity<0 THEN 'Return' ELSE 'Adj' END";
        if ($c['salesReturnFlag']) $typeExpr = "CASE WHEN {$c['itemVoidFlag']}=1 THEN 'Void' WHEN si.quantity<0 OR {$c['salesReturnFlag']}=1 THEN 'Return' ELSE 'Adj' END";
        if ($c['itemReturnFlag'])  $typeExpr = "CASE WHEN {$c['itemVoidFlag']}=1 THEN 'Void' WHEN si.quantity<0 OR {$c['itemReturnFlag']}=1 THEN 'Return' ELSE 'Adj' END";

        $rows = $q->selectRaw("
                s.id as sale_id,
                COALESCE(i.Label, '') as label,
                si.quantity,
                {$typeExpr} as type,
                {$c['lineNet']} as subtotal,
                {$c['lineTax']} as tax
            ")
            ->orderByDesc('s.created_at')
            ->get();

        return response()->json($rows);
    }

    /* =======================================================================
     |  JSON: METRC Discrepancies (label lag)
     * ======================================================================= */
    public function metrcDiscrepancies(Request $r)
    {
        [$startUtc, $endUtc, $tz] = $this->range($r);

        if ($r->boolean('after_today') || $r->query('preset') === 'after_today') {
            $startLocal = Carbon::now($tz)->addDay()->startOfDay();
            $endLocal   = Carbon::parse('2100-01-01', $tz)->endOfDay();
            $startUtc   = $startLocal->clone()->utc();
            $endUtc     = $endLocal->clone()->utc();
        }

        $c = $this->cols();

        // inv label col a must
        if (empty($c['invLabelCol'])) {
            foreach (['Label','label','PackageLabel','package_label','Tag','tag'] as $cn) {
                if (Schema::hasColumn('inventories', $cn)) { $c['invLabelCol'] = 'i.'.$cn; break; }
            }
        }
        if (empty($c['invLabelCol'])) return response()->json([]);

        $posQtyExpr = ($c['invQtyCol'] ?? '0') === '0' ? '0' : "i.{$c['invQtyCol']}";

        // Category label (robust)
        $categorySelect = null; $joinCat = false;
        if (Schema::hasTable('categories') && Schema::hasColumn('inventories','category_id')) {
            foreach (['name','category','category_name','title','label'] as $cc) {
                if (Schema::hasColumn('categories', $cc)) { $categorySelect = "COALESCE(cat.$cc, 'Uncategorized')"; $joinCat = true; break; }
            }
        }
        if (!$categorySelect) {
            $parts = [];
            foreach (['category','category_name','type'] as $ic) {
                if (Schema::hasColumn('inventories', $ic)) $parts[] = "i.$ic";
            }
            $categorySelect = $parts ? "COALESCE(".implode(', ',$parts).", 'Uncategorized')" : "'Uncategorized'";
        }

        // UNREPORTED condition
        $unreportedCond = "1";
        if (Schema::hasColumn('sales','ismetricsend')) {
            $unreportedCond = "COALESCE(s.ismetricsend,0) = 1";
        } elseif (Schema::hasColumn('sales','metrc_synced_at')) {
            $unreportedCond = "s.metrc_synced_at IS NULL";
        } elseif (Schema::hasColumn('sales','metrc_status')) {
            $unreportedCond = "(s.metrc_status IS NULL OR s.metrc_status <> 'sent')";
        }

        // sale-level cancel/void
        $salesCancelFlag = null;
        foreach (['is_cancelled','is_canceled','cancelled','canceled','void','is_void','voided','is_voided','deleted','is_deleted'] as $sc) {
            if (Schema::hasColumn('sales', $sc)) { $salesCancelFlag = "s.$sc"; break; }
        }

        $lineExclusions = ["si.quantity > 0"];
        if (!empty($c['itemVoidFlag']))    $lineExclusions[] = "COALESCE({$c['itemVoidFlag']},0) = 0";
        if (!empty($c['itemReturnFlag']))  $lineExclusions[] = "COALESCE({$c['itemReturnFlag']},0) = 0";
        if (!empty($c['salesReturnFlag'])) $lineExclusions[] = "COALESCE({$c['salesReturnFlag']},0) = 0";
        if (!empty($salesCancelFlag))      $lineExclusions[] = "( {$salesCancelFlag} IS NULL OR {$salesCancelFlag} = 0 )";

        $unreportedCaseCond = implode(' AND ', array_merge([$unreportedCond], $lineExclusions));

        // inventories aggregate
        $invAgg = DB::table('inventories as i')
            ->when($joinCat, fn($q) => $q->leftJoin('categories as cat', 'cat.id', '=', 'i.category_id'))
            ->whereRaw("{$c['invLabelCol']} IS NOT NULL AND TRIM({$c['invLabelCol']}) <> ''");

        if (!empty($c['invStoreCol']) && $r->filled('store')) {
            $invAgg->where("i.{$c['invStoreCol']}", $r->query('store'));
        }

        $invAgg = $invAgg->selectRaw("
                TRIM({$c['invLabelCol']}) as label,
                COALESCE(SUM({$posQtyExpr}), 0) as store_qty,
                MIN(i.sku) as sku,
                MAX({$categorySelect}) as category
            ")
            ->groupBy(DB::raw("TRIM({$c['invLabelCol']})"));

        // sales aggregate (UNREPORTED) by label
        $salesAgg = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('inventories as i', 'i.id', '=', 'si.product_id')
            ->whereBetween('s.created_at', [$startUtc, $endUtc])
            ->whereRaw("{$c['invLabelCol']} IS NOT NULL AND TRIM({$c['invLabelCol']}) <> ''");

        $this->applyCompletedQB($salesAgg, 's');

        if (!empty($c['salesStoreCol']) && $r->filled('store')) {
            $salesAgg->where("s.{$c['salesStoreCol']}", $r->query('store'));
        }

        $salesAgg = $salesAgg->selectRaw("
                TRIM({$c['invLabelCol']}) as label,
                COALESCE(SUM(CASE WHEN {$unreportedCaseCond} THEN si.quantity ELSE 0 END), 0) as unreported_qty
            ")
            ->groupBy(DB::raw("TRIM({$c['invLabelCol']})"));

        $inner = DB::query()
            ->fromSub($invAgg, 'inv')
            ->leftJoinSub($salesAgg, 'sx', 'sx.label', '=', 'inv.label')
            ->selectRaw("
                inv.label,
                inv.sku,
                inv.category,
                inv.store_qty                                   as pos_qty,
                inv.store_qty + COALESCE(sx.unreported_qty, 0) as metrc_qty,
                COALESCE(sx.unreported_qty, 0)                  as delta,
                COALESCE(sx.unreported_qty, 0)                  as uqty
            ");

        $rows = DB::query()
            ->fromSub($inner, 'z')
            ->whereRaw('z.uqty <> 0')
            ->orderByDesc('z.uqty')
            ->get(['label','sku','category','pos_qty','metrc_qty','delta']);

        return response()->json($rows);
    }

    /* =======================================================================
     |  JSON: METRC Unlinked Sales
     * ======================================================================= */
    public function metrcUnlinked(Request $r)
    {
        [$startUtc, $endUtc] = $this->range($r);
        $c = $this->cols();

        if (!$c['mpLabelCol'] || !$c['mpIdCol']) {
            return response()->json([]); // not available
        }

        $q = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('inventories as i', 'i.id', '=', 'si.product_id')
            ->leftJoin('metrc_packages as mp', function($join) use ($c) {
                $join->on(DB::raw($c['mpLabelCol']), '=', DB::raw($c['invLabelCol'] ?? 'i.Label'));
            })
            ->whereBetween('s.created_at', [$startUtc, $endUtc]);

        $this->applyCompletedQB($q, 's');

        if ($c['salesStoreCol'] && $r->filled('store')) {
            $q->where("s.{$c['salesStoreCol']}", $r->query('store'));
        }

        $rows = $q->whereRaw("{$c['mpIdCol']} IS NULL")
            ->selectRaw("
                s.id as sale_id,
                COALESCE(i.Label, '') as label,
                COALESCE(i.sku, '') as sku,
                si.quantity,
                {$c['lineNet']} as subtotal
            ")
            ->orderByDesc('s.created_at')
            ->get();

        return response()->json($rows);
    }

    /* =======================================================================
     |  Helpers: time windows
     * ======================================================================= */
    private function storeTz(): string
    {
        $appTz   = (string) (config('app.timezone') ?: 'UTC');
        $storeTz = (string) (function_exists('setting_by_key') ? (setting_by_key('store_timezone') ?: $appTz) : $appTz);
        return $storeTz;
    }

    /** Sales Index-style local window (strings) */
    private function localWindow(Request $r): array
    {
        $tz         = $this->storeTz();
        $startDate  = $r->query('start_date', Carbon::now($tz)->format('Y-m-d'));
        $endDate    = $r->query('end_date',   Carbon::now($tz)->format('Y-m-d'));
        $startLocal = $startDate.' 00:00:00';
        $endLocal   = $endDate  .' 23:59:59';
        return [$startDate, $endDate, $startLocal, $endLocal, $tz];
    }

    /** API endpoints use UTC range from org/app tz */
    private function tz(): string
    {
        return optional(optional(auth()->user())->organization)->timezone
            ?: config('app.timezone', 'America/Los_Angeles');
    }

    /** Returns [startUtc, endUtc, tz] */
    private function range(Request $r): array
    {
        $tz = $this->tz();

        $startLocal = $r->query('start_date')
            ? Carbon::parse($r->query('start_date'), $tz)->startOfDay()
            : Carbon::now($tz)->startOfMonth();

        $endLocal = $r->query('end_date')
            ? Carbon::parse($r->query('end_date'), $tz)->endOfDay()
            : Carbon::now($tz)->endOfDay();

        return [$startLocal->clone()->utc(), $endLocal->clone()->utc(), $tz];
    }

    /* =======================================================================
     |  Helpers: scoping & status filters
     * ======================================================================= */
    private function applyOrgScopeToPosQuery(QBuilder $q): void
    {
        $orgId = auth()->user()->organization_id ?? null;
        if ($orgId === null) return;

        if (Schema::hasColumn('sales', 'organization_id')) {
            $q->where('s.organization_id', $orgId);
        } else {
            $q->join('users as pu', 'pu.id', '=', 's.user_id')
              ->where('pu.organization_id', $orgId);
        }
    }

    private function applyCompletedEloquent(EBuilder $q): EBuilder
    {
        if (!Schema::hasColumn('sales','status')) return $q;
        return $q->where(function($w){
            $w->where('status', 1)
              ->orWhere('status', true)
              ->orWhere('status', 'Completed')
              ->orWhere('status', 'completed');
        });
    }

    private function applyCompletedQB(QBuilder $q, string $alias = 's'): QBuilder
    {
        if (!Schema::hasColumn('sales','status')) return $q;
        $col = $alias.'.status';
        return $q->where(function($w) use ($col){
            $w->where($col, 1)
              ->orWhere($col, true)
              ->orWhere($col, 'Completed')
              ->orWhere($col, 'completed');
        });
    }

    /* =======================================================================
     |  Helpers: category map & math (for Eloquent endpoints)
     * ======================================================================= */
    private function detectStoreCol(string $table): ?string
    {
        foreach (['store_id','location_id','shop_id'] as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }
        return null;
    }

    private function categoryMap(): array
    {
        if (!Schema::hasTable('categories')) return [];
        return DB::table('categories')->pluck('name','id')
                 ->map(fn($n)=>strtolower($n))->all();
    }

    private function toCents($v): int { return (int) round((float)$v * 100); }
    private function fromCents($c): float { return $c / 100; }

    private function netLineCents($item, string $cat): int
    {
        $qty   = (float)($item->quantity ?? 1);
        $price = (float)($item->price ?? 0);

        $grossC = ($cat === 'flower') ? $this->toCents($price) : $this->toCents($price * $qty);

        foreach ([
            $item->line_total ?? null,
            $item->total ?? null,
            $item->final_price ?? null,
            $item->net_price ?? null,
            $item->price_after_discount ?? null,
            $item->discounted_price ?? null,
            $item->subtotal_after_discount ?? null,
        ] as $cand) {
            if ($cand !== null) {
                $v = $this->toCents((float)$cand);
                if ($v >= 0 && $v <= $grossC + 1) return $v;
            }
        }

        $ldType = strtolower($item->discount_type ?? $item->line_discount_type ?? '');
        $ldVal  = (float)($item->discount_value ?? $item->line_discount_value ?? $item->discount ?? 0);

        if ($ldType === 'percent') {
            $discC = (int) round($grossC * $ldVal / 100);
            return max(0, $grossC - $discC);
        }
        if ($ldType === 'amount') {
            $discC = $this->toCents($ldVal);
            return max(0, $grossC - $discC);
        }
        return $grossC;
    }

    private function breakdownCents($sale, array $categoryMap): array
    {
        $custType = strtolower($sale->customer_type ?? 'consumer');

        $subC = 0; $taxableC = 0;
        foreach (($sale->items ?? []) as $it) {
            if (!$it->relationLoaded('inventory')) continue;
            $inv = $it->inventory;
            $cat = $inv && isset($categoryMap[$inv->category_id]) ? $categoryMap[$inv->category_id] : '';

            $lineC = $this->netLineCents($it, $cat);
            $subC += $lineC;

            if ($custType === 'consumer' && !in_array($cat, $this->NON_TAX_CATS, true)) {
                $taxableC += $lineC;
            }
        }

        if ($subC === 0) { // fallback
            $subC     = $this->toCents((float)($sale->subtotal ?? 0));
            $taxableC = $subC;
        }

        $lineDiscC = $this->toCents((float)($sale->discount ?? 0));
        $lineDiscC = min($lineDiscC, $subC);

        $odType = strtolower($sale->order_discount_type ?? '');
        $odVal  = (float)($sale->order_discount_value ?? 0);
        $orderDiscC = 0;
        if ($odType === 'percent')      $orderDiscC = (int) round($subC * ($odVal/100));
        elseif ($odType === 'amount')   $orderDiscC = min($this->toCents($odVal), $subC);

        if ($lineDiscC > 0 && $orderDiscC > 0 && abs($lineDiscC - $orderDiscC) <= 1) {
            $lineDiscC = 0;
        }

        $subAfterLineC = max(0, $subC - $lineDiscC);

        $taxableAfterLineC = $taxableC;
        if ($subC > 0 && $lineDiscC > 0) {
            $taxableAfterLineC = max(0, $taxableC - (int) round($lineDiscC * ($taxableC / $subC)));
        }

        if ($odType === 'percent')      $orderDiscC = (int) round($subAfterLineC * ($odVal/100));
        elseif ($odType === 'amount')   $orderDiscC = min($this->toCents($odVal), $subAfterLineC);
        else                            $orderDiscC = 0;

        $taxBaseAfterC = $taxableAfterLineC;
        if ($subAfterLineC > 0 && $orderDiscC > 0) {
            $taxBaseAfterC = max(0, $taxableAfterLineC - (int) round($orderDiscC * ($taxableAfterLineC / $subAfterLineC)));
        }

        $salesTaxC = ($custType === 'consumer') ? (int) round($taxBaseAfterC * $this->TAX_RATE_PCT / 100) : 0;
        $dueC      = max(0, $subAfterLineC - $orderDiscC) + $salesTaxC;

        return compact('subC','lineDiscC','subAfterLineC','orderDiscC','taxBaseAfterC','salesTaxC','dueC');
    }

    private function returnRate($startUtc, $endUtc, Request $r, array $c, int $visits): float
    {
        $retQ = DB::table('sale_items as si')
            ->join('sales as s','s.id','=','si.sale_id')
            ->whereBetween('s.created_at', [$startUtc, $endUtc]);

        $this->applyCompletedQB($retQ, 's');

        if ($c['salesStoreCol'] && $r->filled('store')) {
            $retQ->where("s.{$c['salesStoreCol']}", $r->query('store'));
        }

        $retQ->where(function($q) use ($c) {
            $q->where('si.quantity','<',0);
            if (!empty($c['itemReturnFlag']))  { $q->orWhereRaw("{$c['itemReturnFlag']} = 1"); }
            if (!empty($c['salesReturnFlag'])) { $q->orWhereRaw("{$c['salesReturnFlag']} = 1"); }
        });

        $returns = $retQ->distinct('s.id')->count('s.id');
        return $visits ? round(($returns / $visits) * 100, 2) : 0.0;
    }

    /* =======================================================================
     |  Helpers: safe eager detection (ONLY items & items.inventory)
     * ======================================================================= */
    private function saleEager(): array
    {
        $eager = [];
        $sale = new Sale;

        // find items relation name
        $itemsRel = null;
        foreach (['items','saleItems','sale_items','lineItems','lines','products'] as $cand) {
            if (!method_exists($sale, $cand)) continue;
            try {
                $rel = $sale->{$cand}();
                if ($rel instanceof \Illuminate\Database\Eloquent\Relations\Relation) { $itemsRel = $cand; break; }
            } catch (\Throwable $e) {}
        }
        if (!$itemsRel) return [];

        $eager[] = $itemsRel;

        // find nested inventory/product relation on the item model
        try {
            $itemModel = $sale->{$itemsRel}()->getRelated();
            foreach (['inventory','product','variant','sku','stock'] as $cand) {
                if (!method_exists($itemModel, $cand)) continue;
                try {
                    $rel = $itemModel->{$cand}();
                    if ($rel instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                        $eager[] = $itemsRel.'.'.$cand;
                        break;
                    }
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {}

        return array_values(array_unique($eager));
    }

    /* =======================================================================
     |  Helpers: dynamic column detection for SQL endpoints
     * ======================================================================= */
    private function cols(): array
    {
        // Sales collected (grand total)
        $salesCollected = Schema::hasColumn('sales','total') ? 'total'
                        : (Schema::hasColumn('sales','amount') ? 'amount'
                        : (Schema::hasColumn('sales','total_collected') ? 'total_collected' : null));
        if (!$salesCollected) {
            $salesCollected = '(COALESCE(total_after_discount, subtotal, 0) + COALESCE(state_tax,0) + COALESCE(county_tax,0) + COALESCE(city_tax,0))';
        }

        // Sales subtotal
        $salesSubtotal = Schema::hasColumn('sales','subtotal') ? 'subtotal'
                       : (Schema::hasColumn('sales','pre_discount_subtotal') ? 'pre_discount_subtotal'
                       : 'COALESCE(total_after_discount,0) + COALESCE(discount,0)');

        // Sales discount
        $salesDiscount = Schema::hasColumn('sales','discount') ? 'discount' : '0';

        // Sales tax
        if (Schema::hasColumn('sales','tax_total')) {
            $salesTax = 'tax_total';
        } else {
            $parts = [];
            foreach (['state_tax','county_tax','city_tax'] as $t) {
                if (Schema::hasColumn('sales',$t)) $parts[] = "COALESCE($t,0)";
            }
            $salesTax = $parts ? implode(' + ', $parts) : '0';
        }

        // Inventory category / joins
        $categoryExpr = null; $joinCategory = false;
        if (Schema::hasColumn('inventories','category')) {
            $categoryExpr = 'i.category';
        } elseif (Schema::hasColumn('inventories','category_name')) {
            $categoryExpr = 'i.category_name';
        } elseif (Schema::hasColumn('inventories','type')) {
            $categoryExpr = 'i.type';
        } elseif (Schema::hasColumn('inventories','category_id') && Schema::hasTable('categories')) {
            $categoryExpr = 'COALESCE(cat.name, "Uncategorized")';
            $joinCategory = true;
        } else {
            $categoryExpr = '"Uncategorized"';
        }

        // INVENTORY label (case-safe)
        $invLabelCol = null;
        foreach (['Label','label','PackageLabel','package_label','Tag','tag'] as $cname) {
            if (Schema::hasColumn('inventories', $cname)) { $invLabelCol = 'i.'.$cname; break; }
        }

        // Inventory on-hand qty
        $invQtyCol = null;
        foreach (['storeQty','store_qty','on_hand','onhand','quantity','qty'] as $c) {
            if (Schema::hasColumn('inventories', $c)) { $invQtyCol = $c; break; }
        }
        if (!$invQtyCol) $invQtyCol = '0';

        // Sale item net/tax
        if (Schema::hasColumn('sale_items','line_subtotal_post_discount')) {
            $lineNet = 'si.line_subtotal_post_discount';
        } elseif (Schema::hasColumn('sale_items','line_subtotal')) {
            $lineNet = 'si.line_subtotal';
        } elseif (Schema::hasColumn('sale_items','subtotal')) {
            $lineNet = 'si.subtotal';
        } elseif (Schema::hasColumn('sale_items','unit_price') && Schema::hasColumn('sale_items','quantity')) {
            $lineNet = '(si.unit_price * si.quantity)';
        } elseif (Schema::hasColumn('sale_items','price') && Schema::hasColumn('sale_items','quantity')) {
            $lineNet = '(si.price * si.quantity)';
        } else {
            $lineNet = '0';
        }
        $lineTax = Schema::hasColumn('sale_items','tax_amount') ? 'si.tax_amount' : '0';

        // Return/void flags
        $salesReturnFlag = null;
        foreach (['is_return','is_returned','return_flag','returned','is_refund'] as $c) {
            if (Schema::hasColumn('sales', $c)) { $salesReturnFlag = "s.$c"; break; }
        }
        $itemReturnFlag = null;
        foreach (['is_return','is_returned','is_refund'] as $c) {
            if (Schema::hasColumn('sale_items', $c)) { $itemReturnFlag = "si.$c"; break; }
        }
        $itemVoidFlag = null;
        foreach (['is_void','void'] as $c) {
            if (Schema::hasColumn('sale_items', $c)) { $itemVoidFlag = "si.$c"; break; }
        }

        // METRC packages
        $mpLabelCol = null;
        foreach (['Label','label','PackageLabel','package_label','Tag','tag'] as $c) {
            if (Schema::hasColumn('metrc_packages', $c)) { $mpLabelCol = 'mp.'.$c; break; }
        }
        $mpIdCol = null;
        foreach (['Id','id','PackageId','package_id'] as $c) {
            if (Schema::hasColumn('metrc_packages', $c)) { $mpIdCol = 'mp.'.$c; break; }
        }
        $mpQtyCol = null;
        foreach (['RemainingQuantity','remaining_quantity','Quantity','quantity','AvailableQuantity','available_quantity','qty'] as $c) {
            if (Schema::hasColumn('metrc_packages', $c)) { $mpQtyCol = 'mp.'.$c; break; }
        }

        // Optional store/location cols
        $salesStoreCol = $this->detectStoreCol('sales');
        $invStoreCol   = $this->detectStoreCol('inventories');

        return compact(
            'salesCollected','salesSubtotal','salesDiscount','salesTax',
            'categoryExpr','joinCategory','invQtyCol','lineNet','lineTax',
            'salesReturnFlag','itemReturnFlag','itemVoidFlag',
            'mpLabelCol','mpIdCol','mpQtyCol','salesStoreCol','invStoreCol','invLabelCol'
        );
    }
    private function detectPayCol(): ?string
{
    foreach (['payment_type','pay_type','payment_method','payment','type'] as $c) {
        if (\Schema::hasColumn('sales', $c)) return 's.'.$c;
    }
    return null;
}

private function classifyPay(?string $raw): string
{
    $p = strtolower((string)$raw);
    if ($p === '') return 'Other';
    if (str_contains($p,'cash')) return 'Cash';
    if (str_contains($p,'card') || str_contains($p,'debit') || str_contains($p,'credit')) return 'Card';
    return 'Other';
}

/** Round cents up to nearest $10 (i.e., 1000 cents) for card + cashback */
private function ceil10Cents(int $cents): int
{
    if ($cents <= 0) return 0;
    $block = 1000; // $10
    return (int) (ceil($cents / $block) * $block);
}

}
