{{-- resources/views/sales/index.blade.php --}}

@extends('layouts.app')

@section('content')
  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <script>
    window.__pendingJobId = null;
    window.poll = window.poll || function (id) {
      window.__pendingJobId = id || null;
    };
  </script>

  <script>
    window.metrcSyncInline = window.metrcSyncInline || function () {
      try { console.warn('Sync not ready yet'); } catch (e) {}
    };
  </script>

  @php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;

    $currency = function_exists('setting_by_key') ? (setting_by_key('currency') ?? '$') : '$';

    $appTz   = (string)(config('app.timezone') ?: 'UTC');
    $storeTz = (string)((function_exists('setting_by_key') ? (setting_by_key('store_timezone') ?? '') : '') ?: $appTz);

    $startDate = request('start_date', now($storeTz)->format('Y-m-d'));
    $endDate   = request('end_date',   now($storeTz)->format('Y-m-d'));

    $user  = auth()->user();
    $orgId = $user->organization_id ?? null;

    $licenseNumber = null;
    if ($orgId && Schema::hasTable('organizations') && Schema::hasColumn('organizations','license_number')) {
      $licenseNumber = DB::table('organizations')->where('id',$orgId)->value('license_number');
    }

    /** Fallback org if user has none but there is exactly one organization */
    if (!$orgId && Schema::hasTable('organizations')) {
      $orgs = DB::table('organizations')->select('id','license_number')->orderBy('id')->limit(2)->get();
      if ($orgs->count() === 1) {
        $orgId = $orgs[0]->id;
        $licenseNumber = $orgs[0]->license_number ?? $licenseNumber;
      }
    }

    $sales = $sales ?? collect();
    $rows  = ($sales instanceof \Illuminate\Pagination\LengthAwarePaginator) ? $sales->items() : $sales;

    $money = function ($n) use ($currency) {
      return $currency . number_format((float)($n ?: 0), 2, '.', ',');
    };

    /** Prefetch linked METRC rows for this page */
    $metrcMap = collect();
    if (!empty($rows) && Schema::hasTable('metrc_receipts') && Schema::hasColumn('sales', 'metrc_receipt_id')) {
      $metrcIds = collect($rows)->pluck('metrc_receipt_id')->filter()->unique()->values()->all();
      if ($metrcIds) {
        $metrcMap = DB::table('metrc_receipts')->whereIn('id', $metrcIds)->get()->keyBy('id');
      }
    }

    /**
     * Determine METRC eligibility.
     * Strategy:
     * (A) Any sale_items.* package label column (fast path)
     * (B) sale_items.inventory_id → inventories.Label/label/metrc_package
     * (C) sale_items.product_id → inventories.Label/label/metrc_package (fallback if no inventory_id)
     * NOTE: Being "linked" to a metrc_receipt_id does NOT imply eligibility.
     */
    $pkgFlagSet   = [];
    $salePackages = [];

    if (Schema::hasTable('sale_items')) {
      // (A) label on sale_items itself
      $labelCol = null;
      foreach (['package_label','package_tag','metrc_tag','metrc_package','package','label'] as $c) {
        if (Schema::hasColumn('sale_items', $c)) { $labelCol = $c; break; }
      }

      $saleIds = collect($rows)->pluck('id')->filter()->values()->all();

      if ($labelCol && $saleIds) {
        $idsWithPkg = DB::table('sale_items')
          ->whereIn('sale_id', $saleIds)
          ->whereNotNull($labelCol)
          ->where($labelCol, '!=', '')
          ->distinct()
          ->pluck('sale_id')
          ->all();

        foreach ($idsWithPkg as $sid) $pkgFlagSet[$sid] = true;

        // collect package strings for client-side scan filter
        $pkgRows = DB::table('sale_items')
          ->whereIn('sale_id', $saleIds)
          ->whereNotNull($labelCol)
          ->where($labelCol, '!=', '')
          ->select('sale_id', $labelCol.' as label')
          ->get();

        foreach ($pkgRows as $pr) {
          $lab = trim((string)$pr->label);
          if ($lab === '') continue;
          $salePackages[$pr->sale_id] = $salePackages[$pr->sale_id] ?? [];
          $salePackages[$pr->sale_id][] = $lab;
        }
      }

      // (B) inventory label via inventory_id (if present)
      $hasInvId    = Schema::hasColumn('sale_items','inventory_id');
      $hasInvTable = Schema::hasTable('inventories');

      // Prefer 'Label' (capital L), then 'label', then 'metrc_package'
      $invLabelCol = null;
      foreach (['Label','label','metrc_package'] as $c) {
        if ($hasInvTable && Schema::hasColumn('inventories', $c)) { $invLabelCol = $c; break; }
      }

      if ($saleIds && $hasInvId && $invLabelCol) {
        $idsViaInventory = DB::table('sale_items as si')
          ->join('inventories as inv','inv.id','=','si.inventory_id')
          ->whereIn('si.sale_id', $saleIds)
          ->whereNotNull("inv.$invLabelCol")
          ->where("inv.$invLabelCol",'!=','')
          ->distinct()
          ->pluck('si.sale_id')
          ->all();

        foreach ($idsViaInventory as $sid) $pkgFlagSet[$sid] = true;

        // collect inventory labels into the per-sale package list
        $invPkgs = DB::table('sale_items as si')
          ->join('inventories as inv','inv.id','=','si.inventory_id')
          ->whereIn('si.sale_id', $saleIds)
          ->whereNotNull("inv.$invLabelCol")
          ->where("inv.$invLabelCol",'!=','')
          ->select('si.sale_id', DB::raw("inv.$invLabelCol as label"))
          ->get();

        foreach ($invPkgs as $pr) {
          $lab = trim((string)$pr->label);
          if ($lab === '') continue;
          $salePackages[$pr->sale_id] = $salePackages[$pr->sale_id] ?? [];
          if (!in_array($lab, $salePackages[$pr->sale_id], true)) {
            $salePackages[$pr->sale_id][] = $lab;
          }
        }
      }

      // (C) inventory label via product_id (fallback when inventory_id is missing)
      if ($saleIds && Schema::hasColumn('sale_items','product_id') && $invLabelCol) {
        $idsViaProd = DB::table('sale_items as si')
          ->join('inventories as inv','inv.product_id','=','si.product_id')
          ->whereIn('si.sale_id', $saleIds)
          ->whereNotNull("inv.$invLabelCol")
          ->where("inv.$invLabelCol",'!=','')
          ->distinct()
          ->pluck('si.sale_id')
          ->all();

        foreach ($idsViaProd as $sid) $pkgFlagSet[$sid] = true;

        $invPkgs2 = DB::table('sale_items as si')
          ->join('inventories as inv','inv.product_id','=','si.product_id')
          ->whereIn('si.sale_id', $saleIds)
          ->whereNotNull("inv.$invLabelCol")
          ->where("inv.$invLabelCol",'!=','')
          ->select('si.sale_id', DB::raw("inv.$invLabelCol as label"))
          ->get();

        foreach ($invPkgs2 as $pr) {
          $lab = trim((string)$pr->label);
          if ($lab === '') continue;
          $salePackages[$pr->sale_id] = $salePackages[$pr->sale_id] ?? [];
          if (!in_array($lab, $salePackages[$pr->sale_id], true)) {
            $salePackages[$pr->sale_id][] = $lab;
          }
        }
      }
    }

    $cashiers = DB::table('users')->pluck('name', 'id');

    /** List METRC receipts in window (store-local) */
    $metrcReceipts  = collect();
    $rcptLinkedSale = [];
    if (Schema::hasTable('metrc_receipts')) {
      $tsCol     = 'sales_date_time';
      $startLocal = Carbon::parse($startDate, $storeTz)->startOfDay()->toDateTimeString();
      $endLocal   = Carbon::parse($endDate,   $storeTz)->endOfDay()->toDateTimeString();

      $q = DB::table('metrc_receipts')->whereBetween($tsCol, [$startLocal, $endLocal]);
      if ($orgId && Schema::hasColumn('metrc_receipts','organization_id')) {
        $q->where('organization_id', $orgId);
      } elseif (Schema::hasColumn('metrc_receipts','user_id') && auth()->id()) {
        $q->where('user_id', auth()->id());
      }

      $metrcReceipts = $q->orderBy($tsCol)->get();

      if ($metrcReceipts->count() && Schema::hasTable('sales') && Schema::hasColumn('sales','metrc_receipt_id')) {
        $rcptLinkedSale = DB::table('sales')
          ->whereIn('metrc_receipt_id', $metrcReceipts->pluck('id')->all())
          ->pluck('id','metrc_receipt_id')
          ->all();
      }
    }
  @endphp

  <meta
    id="routeSeeds"
    data-org-id="{{ $orgId }}"
    data-license="{{ $licenseNumber }}"
    data-receiptnum="{{ url('/sales/receipt/__SALE__/numbers') }}"
    data-receipt="{{ url('/sales/receipt/__SALE__') }}"
    data-cancel="{{ url('/sales/cancel/__SALE__') }}"
    data-sync-inline-init="{{ url('/metrc/sync-inline/init') }}"
    data-sync-inline-chunk="{{ url('/metrc/sync-inline/chunk') }}"
    data-cands-ts="{{ url('/metrc/reconcile/candidates-ts') }}"
    data-link-ts="{{ url('/metrc/reconcile/link-ts') }}"
    data-relink-window="{{ url('/metrc/relink/timestamp-window-inline') }}"
    data-push-sync="{{ url('/metrc/push-and-sync') }}"
    data-store-tz="{{ $storeTz }}"
    data-start="{{ $startDate }}"
    data-end="{{ $endDate }}"
  >

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <style>
    .wrapper-content { padding-top: 56px !important; }
    .page-heading, .breadcrumb { display: none; }

    @media print {
      header, nav[class*="navbar"], .topbar, .page-heading, .breadcrumb, .actions, .filters, .card:not(.eod-card) { display:none !important; }
      .eod-card { display:block !important; border:none; }
      body { background:#fff; }
    }

    .shell { max-width: 1200px; margin: 8px auto 20px; padding: 0 10px; }
    .hbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
    .title { font-weight:800; font-size:20px; letter-spacing:.2px; }
    .subtitle { color:#6b7280; font-size:12px; }

    .filters { display:flex; align-items:flex-end; gap:8px; flex-wrap:wrap; margin-bottom:10px; }
    .filters .group { display:flex; flex-direction:column; gap:4px; }
    .filters label { margin:0; color:#6b7280; font-size:11px; }
    .filters input[type="text"], .filters input[type="number"], .filters select { padding:6px 8px; border:1px solid #e5e7eb; border-radius:8px; min-height:34px; }

    .btn { display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border-radius:8px; border:1px solid #e5e7eb; background:#fff; cursor:pointer; }
    .btn[disabled] { opacity:.55; cursor:not-allowed; }
    .btn.primary { background:#111827; color:#fff; border-color:#111827; }
    .btn.small { padding:5px 8px; font-size:12px; border-radius:6px; }
    .btn.ghost { background:#f9fafb; }

    .card { border:1px solid #e5e7eb; border-radius:14px; background:#fff; overflow:hidden; }
    .card-hd { padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:10px; border-bottom:1px solid #eef0f3; background:linear-gradient(180deg,#fbfbfc,#f6f7fb); }
    .card-ttl { font-weight:800; }

    .kpis { display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:8px; align-items:stretch; padding:12px; }
    .kpi .lbl { font-size:11px; color:#6b7280; }
    .kpi .val { font-weight:800; font-size:16px; }

    .tbl { width:100%; border-collapse:collapse; }
    .tbl thead th { position:sticky; top:0; z-index:1; background:#f9fafb; border-bottom:1px solid #eef0f3; font-size:12px; font-weight:700; color:#374151; text-align:left; padding:9px 10px; }
    .tbl tbody td { border-bottom:1px solid #f1f5f9; padding:9px 10px; font-size:13px; color:#111827; vertical-align:top; }

    .muted { color:#6b7280; }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .nowrap { white-space:nowrap; }
    .tag { display:inline-block; font-size:11px; padding:3px 7px; border-radius:999px; background:#eef2ff; color:#3730a3; border:1px solid #e0e7ff; }
    .badge { display:inline-flex; align-items:center; gap:6px; padding:3px 8px; border-radius:999px; font-size:11px; border:1px solid transparent; }
    .ok { background:#ecfdf5; color:#065f46; border-color:#d1fae5; }
    .warn { background:#fff7ed; color:#9a3412; border-color:#ffedd5; }
    .info { background:#eef2ff; color:#3730a3; border-color:#e0e7ff; }
    .scan-hit { outline: 2px solid #2563eb; outline-offset: 2px; background: #eff6ff; }

    .eod-card { display:none; margin-top:12px; }
    .eod-wrap { padding:16px; }
    .eod-hd { display:flex; justify-content:space-between; align-items:flex-end; gap:12px; margin-bottom:10px; }
    .eod-ttl { font-weight:800; font-size:18px; }
    .eod-sub { color:#6b7280; font-size:12px; }
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .grid1 { display:grid; grid-template-columns:1fr; gap:12px; }
    .eod-table { width:100%; border-collapse:collapse; }
    .eod-table th, .eod-table td { padding:8px 10px; border-bottom:1px solid #eef0f3; font-size:13px; }
    .eod-table thead th { background:#f9fafb; text-align:left; font-size:12px; font-weight:700; color:#374151; }
    .tr { text-align:right; }
    .eod-note { font-size:11px; color:#6b7280; margin-top:6px; }
  </style>

  <div class="shell">
    <div class="hbar actions">
      <div>
        <div class="title">Sales</div>
        <div class="subtitle">
          Store TZ: <span class="mono">{{ $storeTz }}</span>
          · App TZ: <span class="mono">{{ $appTz }}</span>
          · Org ID: <span class="mono">{{ $orgId ?: '—' }}</span>
        </div>
      </div>

      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <form method="GET" action="{{ url('/sales') }}" class="filters" style="margin:0;">
          <div class="group" style="min-width:280px;">
            <label>Date range</label>
            <input id="rangeInput" type="text" placeholder="Pick range…" />
            <input id="startDateInput" type="hidden" name="start_date" value="{{ $startDate }}">
            <input id="endDateInput" type="hidden"  name="end_date"  value="{{ $endDate }}">
          </div>
          <div class="group" style="align-self:flex-end;">
            <button class="btn">Apply</button>
          </div>
        </form>

        <button class="btn primary" id="syncAllBtn" onclick="window.sync()">Sync</button>
        <button class="btn ghost" onclick="exportCsv()">Export CSV</button>
        <button class="btn" id="btnPrintEod" type="button">Print EOD</button>
      </div>
    </div>

    <div class="card" style="margin-bottom:10px;">
      <div class="card-hd">
        <div class="card-ttl">Totals — {{ $startDate }} → {{ $endDate }}</div>
        <div id="syncProgress" class="muted" style="font-size:12px;"></div>
      </div>

      <div class="kpis">
        <div class="kpi"><div class="lbl">Transactions</div><div class="val" id="kpi-txns">—</div></div>
        <div class="kpi"><div class="lbl">Gross (Post-Tax)</div><div class="val" id="kpi-gross">{{ $money(0) }}</div></div>
        <div class="kpi"><div class="lbl">Pre-Tax</div><div class="val" id="kpi-pre">{{ $money(0) }}</div></div>
        <div class="kpi"><div class="lbl">Tax</div><div class="val" id="kpi-tax">{{ $money(0) }}</div></div>
        <div class="kpi"><div class="lbl">Cash</div><div class="val" id="kpi-cash">{{ $money(0) }}</div></div>
        <div class="kpi"><div class="lbl">Card</div><div class="val" id="kpi-card">{{ $money(0) }}</div></div>
        <div class="kpi"><div class="lbl">Discounts</div><div class="val" id="kpi-disc">{{ $money(0) }}</div></div>
        <div class="kpi"><div class="lbl">METRC Pre-Tax</div><div class="val" id="kpi-metrc-pre">{{ $money(0) }}</div></div>
        <div class="kpi"><div class="lbl">Δ Pre (Local − METRC)</div><div class="val" id="kpi-delta-pre">{{ $money(0) }}</div></div>
      </div>
    </div>

    <div class="filters">
      <div class="group" style="min-width:260px;">
        <label>Search</label>
        <input id="f-q" type="text" placeholder="Sale #, invoice, cashier, METRC #, receipt #" />
      </div>

      <div class="group">
        <label>Cashier</label>
        <select id="f-cashier">
          <option value="">All</option>
          @foreach(($sales instanceof \Illuminate\Pagination\LengthAwarePaginator ? $sales->getCollection() : $sales)->pluck('user_id')->filter()->unique()->values() as $uid)
            <option value="{{ $uid }}">{{ $cashiers[$uid] ?? ('User #'.$uid) }}</option>
          @endforeach
        </select>
      </div>

      <div class="group">
        <label>Payment</label>
        <select id="f-pay">
          <option value="">All</option>
          <option>Cash</option>
          <option>Card</option>
          <option>Other</option>
        </select>
      </div>

      <div class="group">
        <label>Link</label>
        <select id="f-linked">
          <option value="">All</option>
          <option value="1">Linked</option>
          <option value="0">Unlinked</option>
        </select>
      </div>

      <div class="group">
        <label>Δ Pre-Tax</label>
        <select id="f-delta-kind">
          <option value="">All</option>
          <option value="neq">≠ 0</option>
          <option value="pos">Local &gt; METRC</option>
          <option value="neg">Local &lt; METRC</option>
          <option value="zero">= 0</option>
        </select>
      </div>

      <div class="group">
        <label>Min |Δ|</label>
        <input id="f-delta-min" type="number" step="0.01" min="0" placeholder="0.00" />
      </div>

      <div class="group" style="min-width:260px;">
        <label>Package scan</label>
        <input id="f-scan" type="text" placeholder="Scan package label…" autocomplete="off" />
      </div>

      <div class="group" style="align-self:flex-end;">
        <button class="btn small ghost" type="button" id="btnMismatches">Mismatches</button>
        <button class="btn small ghost" type="button" id="btnClearFilters">Clear</button>
      </div>

      <div class="group" style="align-self:center;">
        <span class="tag" id="pillFiltered" style="display:none;">Filtered: <strong id="filCnt">0</strong> · <strong id="filAmt">{{ $currency }}0.00</strong></span>
        <span class="tag" id="pillScan" style="display:none;">Scan: <strong id="scanVal">—</strong></span>
      </div>
    </div>

    <div id="metrcFeedback" style="margin-bottom:8px;"></div>

    <div class="card" style="margin-bottom:10px;">
      <div class="card-hd"><div class="card-ttl">Sales</div></div>
      <div class="card-bd" style="max-height: calc(100vh - 320px); overflow:auto;">
        <table class="tbl" id="salesTable">
          <thead>
          <tr>
            <th>Date & Time</th>
            <th>Sale #</th>
            <th>Cashier</th>
            <th class="nowrap">Invoice</th>
            <th class="nowrap">Pre-Tax</th>
            <th>Tax</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Type</th>
            <th>Link</th>
            <th>METRC Receipt #</th>
            <th class="nowrap">METRC Time</th>
            <th class="nowrap">METRC Pre</th>
            <th class="nowrap">Δ Pre</th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody>
          @forelse($rows as $s)
            @php
              if (isset($s->status) && (int)$s->status !== 1) { continue; }

              $cashierName = $cashiers[$s->user_id] ?? '—';

              $invoiceRaw = null;
              foreach (['external_receipt_number','invoice_number','invoice_no','order_number','order_no'] as $c) {
                if (Schema::hasColumn('sales', $c) && isset($s->{$c}) && $s->{$c}) {
                  $invoiceRaw = (string)$s->{$c};
                  break;
                }
              }

              $invoiceBase = '';
              if ($invoiceRaw) {
                $tmp = strtoupper(trim($invoiceRaw));
                $invoiceBase = mb_substr($tmp, 0, 40);
              }

              $pre = (float)($s->pre_tax_total ?? $s->pretax ?? $s->subtotal ?? 0);
              $tax = (float)($s->tax ?? 0);
              $tot = (float)($s->total ?? ($pre + $tax));

              $paySrc = strtolower((string)($s->payment_type ?? ''));
              $hasFn = function($h) use ($paySrc) { return strpos($paySrc, $h) !== false; };
              $payKind = $paySrc === '' ? 'Other' : ($hasFn('cash') ? 'Cash' : (($hasFn('card') || $hasFn('debit') || $hasFn('credit')) ? 'Card' : 'Other'));

              $linkedRow = null;
              $metrcReceiptNumber = '';
              $metrcId = '';
              $metrcPretax = null;
              $metrcTimeLocal = null;

              if (Schema::hasColumn('sales','metrc_receipt_id') && !empty($s->metrc_receipt_id)) {
                $linkedRow = $metrcMap->get($s->metrc_receipt_id);
                if ($linkedRow) {
                  $metrcReceiptNumber = (string)($linkedRow->receipt_number ?? '');
                  $metrcId = (string)($linkedRow->metrc_id ?? '');
                  $metrcPretax = isset($linkedRow->total_price) ? number_format((float)$linkedRow->total_price, 2, '.', '') : null;
                  $tsCol = 'sales_date_time';
                  if (!empty($linkedRow->$tsCol)) {
                    // 24h display, store-local
                    $metrcTimeLocal = Carbon::parse($linkedRow->$tsCol, $storeTz)->timezone($storeTz)->format('Y-m-d H:i:s');
                  }
                }
              }

              $isLinked = (bool)($linkedRow);

              // Eligibility ONLY from DB/Inventory detection (do not infer from being linked)
              $dbEligible = !empty($pkgFlagSet[$s->id]);
              $isEligible = $dbEligible;

              $rawPosTs   = (string)$s->created_at; // 24h display, store-local
              $localDT    = Carbon::parse($rawPosTs, $appTz)->timezone($storeTz)->format('Y-m-d H:i:s');
              $localEpoch = Carbon::parse($rawPosTs, $appTz)->timezone($storeTz)->timestamp; // for display/sort
              $saleEpochUtc = Carbon::parse($rawPosTs, $appTz)->timezone('UTC')->timestamp; // canonical linkage

              $displayDT    = $isLinked && $metrcTimeLocal ? $metrcTimeLocal : $localDT;
              $displayEpoch = $isLinked && $metrcTimeLocal ? Carbon::parse($linkedRow->sales_date_time ?? null, $storeTz)->timezone($storeTz)->timestamp : $localEpoch;

              $receiptUrl = url('/sales/receipt/'.$s->id);

              $pkgList = $salePackages[$s->id] ?? [];
              $pkgAttr = implode(',', array_map(function($x){ return str_replace(['"',"'"], '', $x); }, $pkgList));
            @endphp

            <tr
              data-sale-id="{{ $s->id }}"
              data-time="{{ $displayDT }}"
              data-epoch="{{ $displayEpoch }}"
              data-epoch-utc="{{ $saleEpochUtc }}"
              data-pos-iso=""
              data-pos-local=""
              data-cashier-id="{{ $s->user_id }}"
              data-cashier-name="{{ e($cashierName) }}"
              data-pay="{{ $payKind }}"
              data-invoice="{{ e($invoiceRaw ?? '') }}"
              data-invoice-base="{{ e($invoiceBase) }}"
              data-pretax="{{ number_format($pre, 2, '.', '') }}"
              data-tax="{{ number_format($tax, 2, '.', '') }}"
              data-total="{{ number_format($tot, 2, '.', '') }}"
              data-eligible="{{ $isEligible ? '1' : '0' }}"
              data-linked="{{ $isLinked ? '1' : '0' }}"
              data-metrc-receipt="{{ $metrcReceiptNumber }}"
              data-metrc-id="{{ $metrcId }}"
              data-receipt-url="{{ $receiptUrl }}"
              data-packages="{{ e($pkgAttr) }}"
            >
              <td class="mono js-time-cell">{{ $displayDT }}</td>
              <td class="mono">#{{ $s->id }}</td>
              <td>{{ $cashierName }}</td>
              <td class="mono">{{ $invoiceRaw ?: '—' }}</td>
              <td class="nowrap js-pre">{{ $money($pre) }}</td>
              <td class="nowrap js-tax">{{ $money($tax) }}</td>
              <td class="nowrap js-tot">{{ $money($tot) }}</td>
              <td><span class="tag">{{ $payKind }}</span></td>
              <td class="js-type-cell">
                @if($isEligible)
                  <span class="badge ok">METRC</span>
                @else
                  <span class="badge warn">Non-METRC</span>
                @endif
              </td>
              <td class="js-link-cell">
                @if($isLinked)<span class="badge ok">Linked</span>
                @else           <span class="badge warn">Unlinked</span>
                @endif
              </td>
              <td class="mono js-metrc-ref-cell">{{ $metrcReceiptNumber ?: '—' }}</td>
              <td class="mono js-metrc-time-cell">{{ $metrcTimeLocal ?: '—' }}</td>
              <td class="nowrap js-metrc-pre">{{ $metrcPretax !== null ? $money($metrcPretax) : '—' }}</td>
              <td class="nowrap js-metrc-delta">
                @if($metrcPretax !== null)
                  @php
                    $d = round(($pre - (float)$metrcPretax), 2);
                    if (abs($d) < 0.005) $d = 0.0;
                  @endphp
                  <span class="{{ $d == 0 ? 'muted' : ($d > 0 ? 'ok' : 'warn') }}">{{ $money($d) }}</span>
                @else
                  <span class="muted">—</span>
                @endif
              </td>
              <td class="nowrap">
                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                  <button class="btn small"        onclick="printReceipt({{ $s->id }})">Reprint</button>
                  <button class="btn small ghost"  onclick="repushCorrected({{ $s->id }})">Repush</button>
                  <button class="btn small"        onclick="deleteSale({{ $s->id }})">Delete</button>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="15" class="muted">No completed sales in this window.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div class="muted" style="padding:8px 12px; font-size:11px;">
        “METRC” = sale has tracked package labels (from sale items, inventory, or detected in receipt). Non-eligible rows can still auto-link by timestamp.
      </div>
    </div>

    <div class="card">
      <div class="card-hd">
        <div class="card-ttl">METRC Receipts (in window) — {{ $startDate }} → {{ $endDate }}</div>
      </div>

      <div class="card-bd" style="max-height: 40vh; overflow:auto;">
        <table class="tbl" id="metrcReceiptsTable">
          <thead>
          <tr>
            <th>Local Time ({{ $storeTz }})</th>
            <th>METRC ID</th>
            <th>Receipt #</th>
            <th>External #</th>
            <th class="nowrap">Pre-Tax</th>
            <th>Final?</th>
            <th>Linked Sale #</th>
          </tr>
          </thead>
          <tbody>
          @php $tsCol = 'sales_date_time'; @endphp
          @forelse($metrcReceipts as $r)
            @php
              $localTs  = $r->$tsCol ? Carbon::parse($r->$tsCol, $storeTz)->timezone($storeTz)->format('Y-m-d H:i:s') : '—';
              $saleLinked = $rcptLinkedSale[$r->id] ?? null;
              $pre = isset($r->total_price) ? number_format((float)$r->total_price, 2, '.', '') : '0.00';
              $epochUtc = $r->$tsCol ? Carbon::parse($r->$tsCol, $storeTz)->timezone('UTC')->timestamp : null;
            @endphp
            <tr
              data-rcpt-id="{{ $r->id }}"
              data-rcpt-metrc-id="{{ $r->metrc_id }}"
              data-rcpt-ts="{{ e($r->$tsCol) }}"
              data-rcpt-epoch="{{ $epochUtc }}"
              data-rcpt-pre="{{ $pre }}"
              data-rcpt-receipt="{{ e($r->receipt_number ?: '') }}"
              data-rcpt-external="{{ e($r->external_receipt_number ?: '') }}"
            >
              <td class="mono">{{ $localTs }}</td>
              <td class="mono">{{ $r->metrc_id }}</td>
              <td class="mono">{{ $r->receipt_number ?: '—' }}</td>
              <td class="mono">{{ $r->external_receipt_number ?: '—' }}</td>
              <td class="mono">{{ $money($r->total_price ?? 0) }}</td>
              <td>{!! (int)($r->is_final ?? 0) ? '<span class="badge ok">Yes</span>' : '<span class="badge warn">No</span>' !!}</td>
              <td class="mono">{{ $saleLinked ? ('#'.$saleLinked) : '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="7" class="muted">No METRC receipts found for this window.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($sales instanceof \Illuminate\Pagination\LengthAwarePaginator)
      <div style="margin-top:10px;">
        {{ $sales->links() }}
      </div>
    @endif

    {{-- ======== EOD PANEL (hidden until print) ======== --}}
    <div id="eodCard" class="card eod-card">
      <div class="eod-wrap">
        <div class="eod-hd">
          <div>
            <div class="eod-ttl">End of Day — <span id="eodRange">{{ $startDate }} → {{ $endDate }}</span></div>
            <div class="eod-sub">Store TZ: <span class="mono">{{ $storeTz }}</span> · Generated: <span id="eodGenTs" class="mono"></span></div>
          </div>
          <div class="eod-sub">
            Rule: Cash = Gross − Card (Card = tender+cashback if present; else ceil10(Gross) for Card payments)
          </div>
        </div>

        <div class="grid2" style="margin-top:6px;">
          <div>
            <table class="eod-table">
              <thead><tr><th colspan="2">Overall</th></tr></thead>
              <tbody id="eodOverallTbody"></tbody>
            </table>
          </div>
          <div>
            <table class="eod-table">
              <thead><tr><th colspan="2">METRC Summary</th></tr></thead>
              <tbody id="eodMetrcTbody"></tbody>
            </table>
          </div>
        </div>

        <div class="grid1" style="margin-top:12px;">
          <table class="eod-table">
            <thead>
            <tr>
              <th>Cashier</th>
              <th class="tr">Txns</th>
              <th class="tr">Pre-Tax</th>
              <th class="tr">Card</th>
              <th class="tr">Cash (Gross − Card)</th>
              <th class="tr">Tax</th>
              <th class="tr">Total</th>
              <th class="tr">Discounts</th>
            </tr>
            </thead>
            <tbody id="eodByCashierTbody"></tbody>
            <tfoot>
            <tr>
              <th>Total</th>
              <th class="tr" id="eodSumTxns">0</th>
              <th class="tr" id="eodSumPre">{{ $currency }}0.00</th>
              <th class="tr" id="eodSumCard">{{ $currency }}0.00</th>
              <th class="tr" id="eodSumCash">{{ $currency }}0.00</th>
              <th class="tr" id="eodSumTax">{{ $currency }}0.00</th>
              <th class="tr" id="eodSumTot">{{ $currency }}0.00</th>
              <th class="tr" id="eodSumDisc">{{ $currency }}0.00</th>
            </tr>
            </tfoot>
          </table>
          <div class="eod-note">Only includes rows present on this page (compiled client-side from the view).</div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    (function () {
      // -------- Controls ----------
      // Hard-disable any client-side auto-linking from the browser.
      var ALLOW_CLIENT_LINKING = false;
      var ALLOW_TIMESTAMP_AUTOLINK = false;

      var Rmeta = document.getElementById('routeSeeds');
      var R = (Rmeta && Rmeta.dataset) ? Rmeta.dataset : {};

      var RECEIPTNUM_URL_TMPL = R.receiptnum || '/sales/receipt/__SALE__/numbers';
      var RECEIPT_URL_TMPL    = R.receipt    || '/sales/receipt/__SALE__';
      var CANDS_TS_URL        = R.candsTs    || '/metrc/reconcile/candidates-ts';
      var LINK_TS_URL         = R.linkTs     || '/metrc/reconcile/link-ts';
      var SYNC_INLINE_INIT    = R.syncInlineInit || '/metrc/sync-inline/init';
      var SYNC_INLINE_CHUNK   = R.syncInlineChunk || '/metrc/sync-inline/chunk';
      var RELINK_WINDOW_URL   = R.relinkWindow   || '/metrc/relink/timestamp-window-inline';
      var PUSH_AND_SYNC_URL   = R.pushSync       || '/metrc/push-and-sync';

      var ORG_ID         = R.orgId      || '';
      var LICENSE_NUMBER = R.license    || '';
      var STORE_TZ       = R.storeTz    || 'UTC';
      var START_DATE     = R.start      || '';
      var END_DATE       = R.end        || START_DATE;

      var CURRENCY = @json($currency);
      var EPS = 0.005;
      var TIME_LIMIT_SECS  = 12 * 3600; // Tighten amount guards used during strict relink
      var PRE_ABS_TOL      = 0.05;      // amount guard
      var PRE_PCT_TOL      = 0.02;      // amount guard
      var AMT_WEIGHT_SEC_PER_DOLLAR = 60;

      function $id (x) { return document.getElementById(x); }
      function saleRows ()     { return Array.prototype.slice.call(document.querySelectorAll('#salesTable tbody tr[data-sale-id]')); }
      function visibleSaleRows () { return saleRows().filter(function(tr){ return tr.style.display !== 'none'; }); }
      function rcptRows ()     { return Array.prototype.slice.call(document.querySelectorAll('#metrcReceiptsTable tbody tr[data-rcpt-id]')); }

      function readCookie (name) {
        var m = document.cookie.split('; ').find(function (r) { return r.indexOf(name + '=') === 0; });
        return m ? m.split('=')[1] : '';
      }

      function csrfFromCookie () { try { return decodeURIComponent(readCookie('XSRF-TOKEN') || ''); } catch (_) { return ''; } }
      function currentCsrf ()    { var m = document.querySelector('meta[name="csrf-token"]'); return (m && m.content) || csrfFromCookie() || ''; }

      function fetchWithTimeout (url, opts, timeoutMs) {
        if (!opts) opts = {};
        if (!timeoutMs) timeoutMs = 60000;
        var ctrl = new AbortController();
        var t = setTimeout(function () { try { ctrl.abort(); } catch (e) {} }, timeoutMs);
        var merged = {};
        for (var k in opts) if (Object.prototype.hasOwnProperty.call(opts, k)) merged[k] = opts[k];
        merged.signal = ctrl.signal;
        merged.credentials = 'same-origin';
        return fetch(url, merged).finally(function () { clearTimeout(t); });
      }

      function fetchJson (url, method, body, timeoutMs) {
        if (!method) method = 'GET';
        var headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': currentCsrf(), 'X-Requested-With': 'XMLHttpRequest' };
        var opts = { method: method, headers: headers };
        if (body) opts.body = JSON.stringify(body);
        return fetchWithTimeout(url, opts, timeoutMs || 60000)
          .then(function (res) { return res.text().then(function (txt) { return { res: res, txt: txt }; }); })
          .then(function (pair) {
            var res = pair.res, txt = pair.txt, j = {};
            try { j = JSON.parse(txt || '{}'); } catch (e) {}
            if (!res.ok) {
              var err = new Error((j && (j.message || j.error)) || txt || (res.status + ' ' + res.statusText));
              err.status = res.status;
              throw err;
            }
            return j;
          });
      }

      function money2 (n) { return Math.round((+n || 0) * 100) / 100; }
      function fmt (n)    { var v = money2(n).toFixed(2); return CURRENCY + v.replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
      function un$ (t)    { return (t || '').replace(/[^\d.-]/g, ''); }

      function flash (msg, klass) {
        if (!klass) klass = 'info';
        var el = $id('metrcFeedback');
        if (!el) return;
        el.innerHTML = '<div class="badge ' + klass + '" style="margin-bottom:6px;">' + msg + '</div>';
        setTimeout(function () { if (el.innerHTML.indexOf(msg) !== -1) el.innerHTML = ''; }, 7000);
      }

      function setProgress (msg) { var el = $id('syncProgress'); if (el) el.textContent = msg || ''; }
      function pad2 (n) { n = +n; return (n < 10 ? '0' : '') + n; }
      // 24-hour display formatter
      function toDisplayYMDHMS (y, m, d, hh, mm, ss) { return y + '-' + pad2(m) + '-' + pad2(d) + ' ' + pad2(hh) + ':' + pad2(mm) + ':' + pad2(ss); }
      function utcPseudoEpoch (y, m, d, hh, mm, ss) { return Date.UTC(y, m - 1, d, hh, mm, ss || 0) / 1000; }

      function parseReceiptTextForLocalDateTime (txt) {
        var monthIdx = {january:1,february:2,march:3,april:4,may:5,june:6,july:7,august:8,september:9,sept:9,october:10,november:11,december:12,jan:1,feb:2,mar:3,apr:4,may:5,jun:6,jul:7,aug:8,sep:9,oct:10,nov:11,dec:12};
        var reLong = /\b(Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:t|tember)?|Oct(?:ober)?|Nov(?:ember)?)\s+(\d{1,2}),\s*(\d{4})/i;
        var reTime = /\b(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(AM|PM)\b/i;
        var reMDY  = /\b(\d{1,2})\/(\d{1,2})\/(\d{4})\b/;
        txt = (txt||'').replace(/\r/g,'\n');
        var y,m,d,hh,mm,ss=null;

        var mdyl = reLong.exec(txt);
        if (mdyl){
          y=+mdyl[3];
          m=monthIdx[mdyl[1].toLowerCase()]||null;
          d=+mdyl[2];
        } else {
          var mdys = reMDY.exec(txt);
          if (mdys){
            m=+mdys[1];
            d=+mdys[2];
            y=+mdys[3];
          }
        }

        var tm = reTime.exec(txt);
        if (tm){
          hh=+tm[1];
          mm=+tm[2];
          ss=(tm[3]!=null ? +tm[3] : null);
          var ap = tm[4].toUpperCase();
          if (ap==='PM' && hh<12) hh += 12;
          if (ap==='AM' && hh===12) hh = 0;
        }

        if (y && m && d && (hh!=null) && (mm!=null)){
          return { y:y, m:m, d:d, hh:hh, mm:mm, ss:ss };
        }
        return null;
      }

      // Parse pkg labels from receipt HTML (for scan filter)
      function parseLabelsFromHtml (txt) {
        var out = [];
        if (!txt) return out;
        var re = /(pkg\s*[:\-]\s*\(?\s*([A-Z0-9\-#]{8,})\s*\)?)/ig, m;
        while ((m = re.exec(txt))) {
          var lab = (m[2] || '').trim().toUpperCase();
          if (lab && out.indexOf(lab) === -1) out.push(lab);
        }
        return out;
      }

      async function fetchReceiptHtmlParts (id) {
        try{
          var href = (RECEIPT_URL_TMPL||'/sales/receipt/__SALE__').replace('__SALE__', String(id));
          var url = new URL(href, window.location.origin);
          url.searchParams.set('embed','1');

          var res = await fetchWithTimeout(url.toString(), {}, 20000);
          var html = await res.text();

          var parts = parseReceiptTextForLocalDateTime(html);

          // Detect pkg labels from the printed receipt content
          var labels = parseLabelsFromHtml(html);
          var tr = document.querySelector('tr[data-sale-id="'+id+'"]');
          if (tr && labels.length){
            tr.dataset.packages = (tr.dataset.packages ? tr.dataset.packages+',' : '') + labels.join(',');
            tr.dataset.eligible = '1';
            var typeCell = tr.querySelector('.js-type-cell');
            if (typeCell) typeCell.innerHTML = '<span class="badge ok">METRC</span>';
          }

          return parts || null;
        }catch(_){
          return null;
        }
      }

      function setLinkedBadge (tr, linked) {
        var cell = tr.querySelector('.js-link-cell');
        if (cell) cell.innerHTML = linked ? '<span class="badge ok">Linked</span>' : '<span class="badge warn">Unlinked</span>';
        tr.dataset.linked = linked ? '1' : '0';

        if (!linked){
          var preEl = tr.querySelector('.js-metrc-pre');
          var dltEl = tr.querySelector('.js-metrc-delta');
          var refEl = tr.querySelector('.js-metrc-ref-cell');
          var tmEl  = tr.querySelector('.js-metrc-time-cell');

          if (preEl) preEl.textContent = '—';
          if (dltEl) dltEl.innerHTML   = '<span class="muted">—</span>';
          if (refEl) refEl.textContent = '—';
          if (tmEl)  tmEl.textContent  = '—';

          tr.dataset.metrcPretax  = '';
          tr.dataset.deltaPretax  = '';
          tr.dataset.metrcReceipt = '';
          tr.dataset.metrcId      = '';
        }
      }

      function paintMetrcCells (tr, metrcPre, receiptNumber, tsStr) {
        var preEl = tr.querySelector('.js-metrc-pre');
        var dltEl = tr.querySelector('.js-metrc-delta');
        var refEl = tr.querySelector('.js-metrc-ref-cell');
        var tmEl  = tr.querySelector('.js-metrc-time-cell');

        var localPre = parseFloat(tr.dataset.pretax || '0');
        var mp = (metrcPre==null || !isFinite(metrcPre)) ? null : money2(metrcPre);

        if (mp == null){
          if (preEl) preEl.textContent = '—';
          if (dltEl) dltEl.innerHTML   = '<span class="muted">—</span>';
          tr.dataset.metrcPretax='';
          tr.dataset.deltaPretax='';
        } else {
          var d = money2(localPre - mp);
          if (Math.abs(d) < EPS) d = 0;
          var cls = d===0 ? 'muted' : (d>0 ? 'ok' : 'warn');

          if (preEl) preEl.textContent = fmt(mp);
          if (dltEl) dltEl.innerHTML   = '<span class="'+cls+'">'+fmt(d)+'</span>';

          tr.dataset.metrcPretax = mp.toFixed(2);
          tr.dataset.deltaPretax = d.toFixed(2);
          tr.dataset.metrcReceipt = receiptNumber || tr.dataset.metrcReceipt || '';
        }

        if (receiptNumber && refEl) refEl.textContent = receiptNumber;

        if (tsStr && tmEl) {
          var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})$/.exec((tsStr||'').trim());
          if (m){
            tmEl.textContent = toDisplayYMDHMS(+m[1],+m[2],+m[3],+m[4],+m[5],+m[6]);
          } else {
            tmEl.textContent = (tsStr||'').trim();
          }
        }
      }

      function fetchReceiptNumbers (id) {
        var url = (RECEIPTNUM_URL_TMPL||'').replace('__SALE__', String(id));
        return fetchWithTimeout(url, {}, 20000).then(function(res){
          if (!res.ok) return res.text().then(function(t){ throw new Error(res.status+' '+res.statusText+' — '+(t||'')); });
          var ct = res.headers.get('content-type') || '';
          if (ct.indexOf('application/json') === -1) return res.text().then(function(t){ throw new Error('Non-JSON from receipt numbers: '+t.slice(0,180)); });
          return res.json();
        });
      }

      function tryExtractIsoFromNumbers (j) {
        var iso = j.receipt_iso || j.pos_local_iso || j.issued_at_iso || j.timestamp_iso || null;
        if (iso) return iso;

        var epoch = j.epoch || j.pos_epoch || j.timestamp_epoch || null;
        if (epoch!=null && isFinite(epoch)) {
          var e = (+epoch < 2e10) ? (+epoch*1000) : +epoch;
          try { return new Date(e).toISOString(); } catch(_){}
        }

        var txt = j.receipt_time || j.printed_at || j.issued_at || j.timestamp || null;
        try{
          if (txt){
            var d=new Date(txt);
            if(!isNaN(d)) return d.toISOString();
          }
        }catch(_){}

        return null;
      }

      async function ensureRowHasReceiptTime (tr, numbersJson) {
        var iso = numbersJson ? tryExtractIsoFromNumbers(numbersJson) : null;
        if (iso){
          var d = new Date(iso);
          var y=d.getUTCFullYear(), mo=d.getUTCMonth()+1, dd=d.getUTCDate(), hh=d.getUTCHours(), mm=d.getUTCMinutes(), ss=d.getUTCSeconds();
          tr.dataset.posIso   = iso;
          tr.dataset.posLocal = toDisplayYMDHMS(y,mo,dd,hh,mm,ss);
          var tcell = tr.querySelector('.js-time-cell');
          if (tcell) tcell.textContent = tr.dataset.posLocal;
          return;
        }
        var parts = await fetchReceiptHtmlParts(tr.dataset.saleId);
        if (parts){
          var sec = (parts.ss != null ? parts.ss : 0);
          tr.dataset.posLocal = toDisplayYMDHMS(parts.y,parts.m,parts.d,parts.hh,parts.mm,sec);
          tr.dataset.posIso   = '';
          var tcell2 = tr.querySelector('.js-time-cell');
          if (tcell2) tcell2.textContent = tr.dataset.posLocal;
        }
      }

      function updateRowFromReceipt (tr, j) {
        var pre    = +((j.pre_tax || j.pre_tax_total || j.pretax || j.subtotal || j.pre || 0) || 0);
        var tax    = +((j.tax || j.tax_total || j.taxes || 0) || 0);
        var tot    = +((j.total || j.grand_total || (pre + tax) || 0) || 0);
        var cash   = +((j.cash || j.cash_total || 0) || 0);
        var card   = +((j.card || j.card_total || j.debit || 0) || 0);
        var disc   = +((j.discounts || j.discount || 0) || 0);
        var change = +((j.change || j.cashback || j.cash_back || j.cash_back_amount || 0) || 0);
        var paid   = +((j.paid_total || j.paid || j.amount_paid || j.tendered || (cash + card)) || 0);

        tr.dataset.pretax = money2(pre).toFixed(2);
        tr.dataset.tax    = money2(tax).toFixed(2);
        tr.dataset.total  = money2(tot).toFixed(2);
        tr.dataset.cash   = money2(cash).toFixed(2);
        tr.dataset.card   = money2(card).toFixed(2);
        tr.dataset.disc   = money2(disc).toFixed(2);
        tr.dataset.change = money2(change).toFixed(2);
        tr.dataset.paid   = money2(paid).toFixed(2);

        var preEl = tr.querySelector('.js-pre');
        var taxEl = tr.querySelector('.js-tax');
        var totEl = tr.querySelector('.js-tot');
        if (preEl) preEl.textContent = fmt(pre);
        if (taxEl) taxEl.textContent = fmt(tax);
        if (totEl) totEl.textContent = fmt(tot);
      }

      function ceil10 (n) { n = +n || 0; return Math.ceil(n / 10) * 10; }

      function refreshKpis () {
        var txns=0, pre=0, tax=0, tot=0, disc=0, metrcPre=0, delta=0, cash=0, card=0;
        var rows = visibleSaleRows();

        for (var i=0;i<rows.length;i++){
          var r = rows[i].dataset || {};
          var tPre   = +(r.pretax||0);
          var tTax   = +(r.tax||0);
          var tTot   = +(r.total||0);
          var tDisc  = +(r.disc||0);
          var tMPre  = +(r.metrcPretax||0);
          var tDelta = +(r.deltaPretax||0);
          var tChange = +(r.change||0);
          var tCardExplicit = +(r.card||0);

          var tCard;
          if (isFinite(tCardExplicit) && tCardExplicit > 0){
            tCard = tCardExplicit + (isFinite(tChange) ? tChange : 0);
          } else if (r.pay === 'Card'){
            tCard = ceil10(tTot);
          } else {
            tCard = 0;
          }

          var tCash = tTot - tCard;
          if (!isFinite(tCash) || tCash < 0) tCash = 0;

          txns++; pre+=tPre; tax+=tTax; tot+=tTot; disc+=tDisc; metrcPre+=tMPre; delta+=tDelta; card += tCard; cash += tCash;
        }

        $id('kpi-txns').textContent     = String(txns);
        $id('kpi-pre').textContent      = fmt(pre);
        $id('kpi-tax').textContent      = fmt(tax);
        $id('kpi-gross').textContent    = fmt(tot);
        $id('kpi-cash').textContent     = fmt(cash);
        $id('kpi-card').textContent     = fmt(card);
        $id('kpi-disc').textContent     = fmt(-disc);
        $id('kpi-metrc-pre').textContent= fmt(metrcPre);
        $id('kpi-delta-pre').textContent= fmt(delta);
      }

      // ---------- Filters (incl. Package Scan) ----------
      function applyFilters () {
        var q       = ($id('f-q')        && $id('f-q').value || '').trim().toUpperCase();
        var cashier = ($id('f-cashier')  && $id('f-cashier').value || '').trim();
        var pay     = ($id('f-pay')      && $id('f-pay').value || '').trim();
        var linked  = ($id('f-linked')   && $id('f-linked').value || '').trim();
        var dk      = ($id('f-delta-kind') && $id('f-delta-kind').value || '').trim();
        var dmin    = parseFloat(($id('f-delta-min') && $id('f-delta-min').value) || '0');
        var scan    = ($id('f-scan')     && $id('f-scan').value || '').trim();
        var scanNorm = (scan||'').toString().trim().toUpperCase();

        var shown=0, amt=0;

        saleRows().forEach(function(tr){
          tr.classList.remove('scan-hit');
          var r = tr.dataset || {};
          var ok = true;

          if (q){
            var hay = (r.saleId ? '#'+r.saleId : '') + ' ' + (r.invoice || '') + ' ' + (r.metrcReceipt || '') + ' ' + (r.cashierName || '');
            if (hay.toUpperCase().indexOf(q) === -1) ok = false;
          }
          if (ok && cashier && String(r.cashierId||'') !== String(cashier)) ok = false;
          if (ok && pay && (r.pay||'') !== pay) ok = false;
          if (ok && linked !== '' && String(r.linked||'') !== String(linked)) ok = false;

          if (ok && dk){
            var d = parseFloat(r.deltaPretax || '0');
            if (dk === 'neq'  && Math.abs(d) < EPS) ok = false;
            if (dk === 'pos'  && !(d >  EPS))       ok = false;
            if (dk === 'neg'  && !(d < -EPS))       ok = false;
            if (dk === 'zero' && !(Math.abs(d) < EPS)) ok = false;
          }

          if (ok && dmin > 0){
            var ad = Math.abs(parseFloat(r.deltaPretax || '0'));
            if (ad < dmin) ok = false;
          }

          if (ok && scanNorm){
            var packs = (r.packages||'').toUpperCase();
            if (packs.indexOf(scanNorm) === -1) ok = false;
            else tr.classList.add('scan-hit');
          }

          tr.style.display = ok ? '' : 'none';
          if (ok){ shown++; amt += +(r.total || 0); }
        });

        var pill = $id('pillFiltered');
        if (pill){
          if (shown === saleRows().length) {
            pill.style.display='none';
          } else {
            pill.style.display='';
            $id('filCnt').textContent = String(shown);
            $id('filAmt').textContent = fmt(amt);
          }
        }

        var sp = $id('pillScan');
        if (sp){
          if (scanNorm){
            sp.style.display='';
            $id('scanVal').textContent = scan;
          } else {
            sp.style.display='none';
          }
        }

        refreshKpis();
      }

      window.exportCsv = function(){
        try {
          var rows = saleRows().filter(function(tr){ return tr.style.display !== 'none'; });
          if (!rows.length) {
            flash('Nothing to export for the current filters.', 'warn');
            return;
          }

          var getTxt = function(sel, tr){ var el=tr.querySelector(sel); return (el?el.textContent.trim():''); };

          var header = [
            'DateTime','Sale #','Cashier','Invoice',
            'PreTax(Local)','Tax(Local)','Total(Local)',
            'Payment','Type','Link',
            'METRC Receipt #','METRC Time','METRC PreTax','Δ PreTax(Local−METRC)',
            'Packages'
          ];

          var data = rows.map(function(tr){
            return [
              getTxt('.js-time-cell', tr),
              getTxt('td:nth-child(2)', tr),
              getTxt('td:nth-child(3)', tr),
              getTxt('td:nth-child(4)', tr),
              un$(getTxt('.js-pre', tr)),
              un$(getTxt('.js-tax', tr)),
              un$(getTxt('.js-tot', tr)),
              getTxt('td:nth-child(8)', tr).replace(/\s+/g,' '),
              tr.querySelector('.js-type-cell .badge')?.textContent.trim() || '',
              tr.querySelector('.js-link-cell .badge')?.textContent.trim() || '',
              getTxt('.js-metrc-ref-cell', tr),
              getTxt('.js-metrc-time-cell', tr),
              un$(getTxt('.js-metrc-pre', tr)),
              un$(tr.querySelector('.js-metrc-delta')?.textContent || ''),
              (tr.dataset.packages || '')
            ];
          });

          var csv = [header].concat(data).map(function(row){
            return row.map(function(cell){
              var t = (cell==null?'':String(cell));
              if (/[",\n]/.test(t)) t = '"' + t.replace(/"/g,'""') + '"';
              return t;
            }).join(',');
          }).join('\n');

          var seeds = document.getElementById('routeSeeds')?.dataset || {};
          var fn = 'sales_' + (seeds.start||'start') + '_to_' + (seeds.end||'end') + '.csv';

          var blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
          var url = URL.createObjectURL(blob);
          var a = document.createElement('a');
          a.href = url;
          a.download = fn;
          document.body.appendChild(a);
          a.click();
          setTimeout(function(){ URL.revokeObjectURL(url); a.remove(); }, 0);

          flash('CSV exported: '+fn, 'ok');
        } catch(e){
          console.error(e);
          flash('Export failed: '+(e.message||e), 'warn');
        }
      };

      window.printReceipt = function(id){
        var row = document.querySelector('tr[data-sale-id="'+id+'"]');
        var rowUrl = row?.dataset?.receiptUrl || '';
        var tmpl = (document.getElementById('routeSeeds')?.dataset?.receipt) || '/sales/receipt/__SALE__';
        var href = rowUrl || tmpl.replace('__SALE__', String(id));
        var u = new URL(href, window.location.origin);
        u.searchParams.set('print', '1');
        u.searchParams.set('autoprint', '1');
        u.searchParams.set('embed', '1');
        var fr=document.createElement('iframe');
        fr.style.position='fixed';
        fr.style.right='-9999px';
        fr.style.bottom='-9999px';
        fr.width='0';
        fr.height='0';
        fr.src=u.toString();
        fr.onload=function(){
          try{ fr.contentWindow.focus(); fr.contentWindow.print(); }catch(_){}
          setTimeout(function(){ try{ fr.remove(); }catch(_){ } }, 8000);
        };
        document.body.appendChild(fr);
      };

      window.deleteSale = function(id){
        var ok = window.confirm('Delete sale #'+id+'? This cannot be undone.');
        if (!ok) return;
        try{
          var tmpl = (document.getElementById('routeSeeds')?.dataset?.cancel) || '/sales/cancel/__SALE__';
          var url = tmpl.replace('__SALE__', String(id));
          fetch(url, {
            method:'POST',
            headers:{
              'Content-Type':'application/json',
              'X-Requested-With':'XMLHttpRequest',
              'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')||{}).content || ''
            },
            credentials:'same-origin',
            body: JSON.stringify({ reason:'user_request' })
          }).then(function(r){
            if (!r.ok) throw new Error(r.status+' '+r.statusText);
            var tr = document.querySelector('tr[data-sale-id="'+id+'"]');
            if (tr) tr.remove();
            refreshKpis();
            applyFilters();
            flash('Sale #'+id+' deleted.', 'ok');
          }).catch(function(e){
            console.error(e);
            flash('Delete failed: '+(e.message||e), 'warn');
          });
        }catch(e){
          console.error(e);
          flash('Delete failed', 'warn');
        }
      };

      window.repushCorrected = async function(id){
        if (!window.confirm('Repush corrected sale #'+id+' to METRC?')) return;
        var ORG_ID2 = (document.getElementById('routeSeeds')?.dataset?.orgId) || '';
        var payload = { sale_id: Number(id), organization_id: ORG_ID2 || undefined };

        async function postJson(url, body){
          const res = await fetch(url, {
            method:'POST',
            headers:{
              'Content-Type':'application/json',
              'X-Requested-With':'XMLHttpRequest',
              'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')||{}).content || ''
            },
            credentials:'same-origin',
            body: JSON.stringify(body)
          });
          const txt = await res.text();
          var j = {};
          try{ j = JSON.parse(txt || '{}'); }catch(_){}
          if (!res.ok) throw new Error(j?.message || j?.error || txt || (res.status+' '+res.statusText));
          return j;
        }

        try{
          let resp = await postJson('/metrc/push/corrected', payload);
          try {
            if (window.hydrateAllRows) await window.hydrateAllRows();
            if (window.linkPaintCandidates) await window.linkPaintCandidates();
          } catch(_){}
          flash('Repushed sale #'+id+(resp?.metrc_response?.ReceiptNumber ? (' — Receipt '+resp.metrc_response.ReceiptNumber) : ''), 'ok');
        } catch(e){
          console.error(e);
          flash('Repush failed: '+(e.message||e), 'warn');
        }
      };

      // ---------- Auto-linking helpers ----------
      function stripRev (s){ return (s||'').toString().replace(/-R\d+\b/i,''); }
      function saleIdFromExternal (ex){
        var t = (ex||'').toString().trim().toUpperCase();
        var m = /INV[-_]*0*([0-9]{1,9})\b(?:-R\d+)?/i.exec(t);
        return m ? parseInt(m[1],10) : null;
      }

      // Use UTC epoch directly from <tr data-epoch-utc="...">
      function getSalePseudo (tr){
        var e = parseFloat(tr.dataset.epochUtc || tr.dataset.epoch || 'NaN');
        return isFinite(e) ? e : null;
      }

      function readReceiptsList () {
        return rcptRows().map(function(tr){
          var pseudo = parseFloat(tr.dataset.rcptEpoch || 'NaN');
          var pre    = parseFloat(tr.dataset.rcptPre   || '0');
          return {
            metrc_id: Number(tr.dataset.rcptMetrcId || '0'),
            pseudo: isFinite(pseudo) ? pseudo : null,
            pre: isFinite(pre) ? pre : 0,
            receipt: tr.dataset.rcptReceipt || '',
            external: tr.dataset.rcptExternal || '',
            tsStr: tr.dataset.rcptTs || ''
          };
        }).filter(function(r){ return r.metrc_id && r.pseudo != null; });
      }

      async function doLink (tr, targetRcpt) {
        var body = {
          sale_id: Number(tr.dataset.saleId),
          metrc_id: Number(targetRcpt.metrc_id),
          tolerance_seconds: 0, // exact only
          organization_id: ORG_ID || undefined,
          hard: true
        };
        var res = await fetchJson(LINK_TS_URL, 'POST', body, 20000);
        setLinkedBadge(tr, true);
        tr.dataset.metrcId = String(targetRcpt.metrc_id);
        var mp = (typeof res?.metrc_pre==='number') ? res.metrc_pre : targetRcpt.pre;
        paintMetrcCells(tr, mp, (res?.metrc_receipt_number || targetRcpt.receipt || ''), targetRcpt.tsStr || '');
        return true;
      }

      async function unlinkRow (tr){ setLinkedBadge(tr,false); }

      // Try by explicit external sale-id match
      async function linkViaExternalSaleId (tr){
        if (!ALLOW_CLIENT_LINKING) return false;
        var saleId = Number(tr.dataset.saleId || '0');
        if (!saleId) return false;
        var receipts = readReceiptsList().filter(function(r){
          return saleIdFromExternal(r.external) === saleId;
        });
        if (!receipts.length) return false;

        var salePseudo = getSalePseudo(tr);
        if (salePseudo != null){
          receipts.sort(function(a,b){ return Math.abs(a.pseudo - salePseudo) - Math.abs(b.pseudo - salePseudo); });
        }
        try { return await doLink(tr, receipts[0]); }
        catch(e){ console.warn('external-sale-id link failed', e); return false; }
      }

      // Try invoice text exact/base
      async function linkViaInvoiceMatch (tr){
        if (!ALLOW_CLIENT_LINKING) return false;

        var invRaw  = tr.dataset.invoice || '';
        var invNorm = (invRaw||'').toString().trim().toUpperCase();
        var invBase = (stripRev(invRaw)||'').toString().trim().toUpperCase();
        if (!invNorm && !invBase) return false;

        var invTok  = saleIdFromExternal(invRaw);
        var saleId  = Number(tr.dataset.saleId || '0');
        if (invTok != null && saleId && invTok !== saleId) return false;

        var receipts = readReceiptsList().filter(function(r){
          var extNorm = (r.external||'').toString().trim().toUpperCase();
          var extBase = (stripRev(r.external||'')||'').toString().trim().toUpperCase();
          return (extNorm && (extNorm === invNorm || extBase === invBase));
        });
        if (!receipts.length) return false;

        var salePseudo = getSalePseudo(tr);
        if (salePseudo != null){
          receipts.sort(function(a,b){ return Math.abs(a.pseudo - salePseudo) - Math.abs(b.pseudo - salePseudo); });
        }
        try { return await doLink(tr, receipts[0]); }
        catch(e){ console.warn('invoice-external link failed', e); return false; }
      }

      // Timestamp-only linking — disabled unless explicitly enabled. When enabled, exact-only.
      async function linkViaTimestampOnly (tr){
        if (!ALLOW_CLIENT_LINKING || !ALLOW_TIMESTAMP_AUTOLINK) return false;
        var salePseudo = getSalePseudo(tr);
        if (salePseudo == null) return false;
        var receipts = readReceiptsList();
        if (!receipts.length) return false;

        // exact match only
        var match = receipts.find(function(r){ return r.pseudo === salePseudo; });
        if (!match) return false;

        try { return await doLink(tr, match); }
        catch(e){ console.warn('timestamp-only link failed', e); return false; }
      }

      // Server candidates endpoint (kept but exact-only)
      async function tryServerCandidates (tr){
        if (!ALLOW_CLIENT_LINKING || !ALLOW_TIMESTAMP_AUTOLINK) return false;
        try{
          var url = new URL(CANDS_TS_URL, window.location.origin);
          if (ORG_ID) url.searchParams.set('organization_id', ORG_ID);
          url.searchParams.set('sale_id', tr.dataset.saleId);
          url.searchParams.set('minutes', String(0)); // exact-only (0 minute window)
          var j = await fetchJson(url.toString(), 'GET', null, 20000);
          var arr = Array.isArray(j && j.candidates) ? j.candidates : [];
          if (!arr.length) return false;

          // choose the one with seconds_diff === 0
          var pick = arr.find(function(c){ return Math.abs(+c.seconds_diff||0) === 0; });
          if (!pick) return false;

          return await doLink(tr, {
            metrc_id: pick.metrc_id,
            tsStr: pick.sales_date_time || '',
            pre: (pick.total_price!=null) ? +pick.total_price : 0,
            receipt: pick.receipt_number || '',
            external: pick.external || ''
          });
        }catch(_){
          return false;
        }
      }

      // HARD-OFF: never perform client-side linking unless flag is flipped
      async function tryAutoLinkRow (tr){
        if (!ALLOW_CLIENT_LINKING) return false;
        if (tr.dataset.linked === '1') return false;

        if (await linkViaExternalSaleId(tr)) return true;
        if (await linkViaInvoiceMatch(tr))  return true;

        if (ALLOW_TIMESTAMP_AUTOLINK) {
          if (await linkViaTimestampOnly(tr)) return true;
          if (await tryServerCandidates(tr))  return true;
        }

        setLinkedBadge(tr, false);
        return false;
      }

      function linkPaintCandidates(){
        var trs = saleRows();
        if (!trs.length) return Promise.resolve();
        var idx = 0;

        return new Promise(function(resolve){
          function step(){
            if (idx >= trs.length) {
              refreshKpis();
              return resolve();
            }
            var tr = trs[idx++];

            (function repaintDelta(){
              var metrcTxt = (tr.querySelector('.js-metrc-pre')||{}).textContent || '';
              var mp = parseFloat(un$(metrcTxt));
              if (isFinite(mp)) tr.dataset.metrcPretax = money2(mp).toFixed(2);

              var localPre = parseFloat(tr.dataset.pretax||'0');
              if (isFinite(mp)){
                var d = money2(localPre - mp);
                if (Math.abs(d) < EPS) d = 0;
                tr.dataset.deltaPretax = d.toFixed(2);
                var dltEl = tr.querySelector('.js-metrc-delta');
                var cls = d===0 ? 'muted' : (d>0 ? 'ok' : 'warn');
                if (dltEl) dltEl.innerHTML = '<span class="'+cls+'">'+fmt(d)+'</span>';
              }
            })();

            var linked = tr.dataset.linked === '1';
            if (linked || !ALLOW_CLIENT_LINKING){
              return setTimeout(step, 10);
            }

            tryAutoLinkRow(tr).finally(function(){ setTimeout(step, 20); });
          }
          step();
        });
      }
      window.linkPaintCandidates = linkPaintCandidates;

      function hydrateAllRows(){
        var trs = saleRows();
        if (!trs.length) {
          var k=$id('kpi-txns');
          if(k) k.textContent='0';
          return Promise.resolve();
        }
        var done = 0;
        setProgress('Fetching receipt data… 0/'+trs.length);
        var concurrency = 6, i = 0, active = 0;

        return new Promise(function(resolve){
          function next(){
            if (i >= trs.length && active === 0) {
              setProgress('');
              refreshKpis();
              return resolve();
            }
            while (active < concurrency && i < trs.length){
              (function(tr){
                i++;
                active++;
                var id = tr.dataset.saleId;

                Promise.resolve()
                  .then(function(){
                    return fetchReceiptNumbers(id).then(function(j){
                      updateRowFromReceipt(tr, j);
                      return j;
                    }).catch(function(){ return {}; });
                  })
                  .then(function(j){
                    return ensureRowHasReceiptTime(tr, j);
                  })
                  .finally(function(){
                    done++;
                    if (done % 3 === 0 || done === trs.length) setProgress('Fetching receipt data… '+done+'/'+trs.length);
                    active--;
                    next();
                  });

              })(trs[i]);
            }
          }
          next();
        });
      }
      window.hydrateAllRows = hydrateAllRows;

      (function initDatePicker(){
        var start = (document.getElementById('startDateInput')?.value)||'';
        var end   = (document.getElementById('endDateInput')?.value)||start;
        var ri = document.getElementById('rangeInput');
        if (!ri || !window.flatpickr) return;

        ri.value = start && end ? (start + ' to ' + end) : '';
        flatpickr(ri, {
          mode: 'range',
          dateFormat: 'Y-m-d',
          defaultDate: [start, end],
          onChange: function(selectedDates){
            var s = '', e = '';
            if (selectedDates && selectedDates.length) {
              var fmt = function(d){ return d.toISOString().slice(0,10); };
              s = fmt(selectedDates[0]);
              e = fmt(selectedDates[selectedDates.length-1] || selectedDates[0]);
            }
            var sI = document.getElementById('startDateInput');
            var eI = document.getElementById('endDateInput');
            if (sI) sI.value = s || '';
            if (eI) eI.value = e || s || '';
          }
        });
      })();

      (function wireFilters(){
        var ei = function(id){ return document.getElementById(id); };
        ['f-q','f-cashier','f-pay','f-linked','f-delta-kind','f-delta-min','f-scan'].forEach(function(id){
          var el = ei(id);
          if (!el) return;
          var evt = (id==='f-q' || id==='f-scan') ? 'keyup' : 'change';
          el.addEventListener(evt, function(ev){
            if ((id==='f-q' || id==='f-scan') && ev.key && ev.key!=='Enter') return;
            try { applyFilters(); } catch(e){}
          });
        });

        var m = ei('btnMismatches');
        if (m) m.addEventListener('click', function(){
          var dk = ei('f-delta-kind'); if (dk){ dk.value='neq'; }
          var dmin = ei('f-delta-min'); if (dmin){ dmin.value='0.00'; }
          try { applyFilters(); } catch(e){}
        });

        var c = ei('btnClearFilters');
        if (c) c.addEventListener('click', function(){
          ['f-q','f-cashier','f-pay','f-linked','f-delta-kind','f-delta-min','f-scan'].forEach(function(id){
            var el = ei(id); if (!el) return; el.value='';
          });
          try { applyFilters(); } catch(e){}
        });
      })();

      // ---- one-time reload helpers per date range (ensures receipts table includes freshly-synced rows)
      function _syncReloadKey(){ return '__synced__' + START_DATE + '__' + END_DATE; }
      function _needFirstReload(){ try { return !window.sessionStorage.getItem(_syncReloadKey()); } catch(_) { return true; } }
      function _markReloaded(){ try { window.sessionStorage.setItem(_syncReloadKey(), '1'); } catch(_){} }
      function _clearReloadFlag(){ try { window.sessionStorage.removeItem(_syncReloadKey()); } catch(_){} }

      (function(){
        if (window.__sales_bootstrap_done) return;
        window.__sales_bootstrap_done = true;

        document.addEventListener('DOMContentLoaded', function(){
          Promise.resolve()
            // SYNC receipts from METRC into metrc_receipts first (active + inactive),
            // then strict relink, then (one-time) reload so receipts table is server-rendered with new rows.
            .then(function(){ return runInlineSyncWindow(); })
            .then(function(){ return relinkWindow(); })
            .then(function(){
              if (_needFirstReload()){
                _markReloaded();
                location.reload();
                return Promise.reject('reloading');
              }
            })
            .then(function(){ return hydrateAllRows(); })
            .then(function(){ return linkPaintCandidates(); })
            .then(function(){ try { applyFilters(); } catch(_){} })
            .catch(function(e){ if (e !== 'reloading') console.warn('bootstrap err', e && e.message); });
        }, { once: true });
      })();

      // ======== EOD (compiled from view) ========
      function compileEodData(){
        var rows = saleRows();
        var overall = { txns:0, pre:0, tax:0, tot:0, card:0, cash:0, disc:0 };
        var metrc   = { eligible:0, non:0, linked:0, unlinked:0 };
        var byCashier = Object.create(null);

        rows.forEach(function(tr){
          var d = tr.dataset || {};
          var pre = +(d.pretax||0);
          var tax = +(d.tax||0);
          var tot = +(d.total||0); // GROSS (post-tax)
          var disc = +(d.disc||0);
          var change = +(d.change||0);
          var cardExplicit = +(d.card||0);
          var card;

          if (isFinite(cardExplicit) && cardExplicit > 0){
            card = cardExplicit + (isFinite(change) ? change : 0);
          } else if (d.pay === 'Card'){
            card = ceil10(tot);
          } else {
            card = 0;
          }

          var cash = tot - card;
          if (!isFinite(cash) || cash < 0) cash = 0;

          overall.txns++; overall.pre += pre; overall.tax += tax; overall.tot += tot; overall.card += card; overall.cash += cash; overall.disc += disc;

          var name = (d.cashierName || '—').trim() || '—';
          if (!byCashier[name]) byCashier[name] = { txns:0, pre:0, tax:0, tot:0, card:0, cash:0, disc:0 };
          var b = byCashier[name];
          b.txns++; b.pre+=pre; b.tax+=tax; b.tot+=tot; b.card+=card; b.cash+=cash; b.disc+=disc;

          if (d.eligible === '1') metrc.eligible++; else metrc.non++;
          if (d.linked === '1')   metrc.linked++;   else metrc.unlinked++;
        });

        return { overall, metrc, byCashier };
      }

      function renderEod(){
        var e = compileEodData();
        var fmt2 = fmt;
        var now = new Date();
        $id('eodGenTs').textContent = now.toLocaleString();

        var oT = $id('eodOverallTbody');
        if (oT){
          oT.innerHTML = [
            ['Transactions', String(e.overall.txns)],
            ['Pre-Tax', fmt2(e.overall.pre)],
            ['Card', fmt2(e.overall.card)],
            ['Cash (Gross − Card)', fmt2(e.overall.cash)],
            ['Tax', fmt2(e.overall.tax)],
            ['Total (Post-Tax)', fmt2(e.overall.tot)],
            ['Discounts', fmt2(-e.overall.disc)]
          ].map(function(r){ return '<tr><td>'+r[0]+'</td><td class="tr">'+r[1]+'</td></tr>'; }).join('');
        }

        var mT = $id('eodMetrcTbody');
        if (mT){
          mT.innerHTML = [
            ['METRC', String(e.metrc.eligible)],
            ['Non-METRC', String(e.metrc.non)],
            ['Linked', String(e.metrc.linked)],
            ['Unlinked', String(e.metrc.unlinked)]
          ].map(function(r){ return '<tr><td>'+r[0]+'</td><td class="tr">'+r[1]+'</td></tr>'; }).join('');
        }

        var cT = $id('eodByCashierTbody');
        if (cT){
          var names = Object.keys(e.byCashier).sort(function(a,b){ return a.localeCompare(b); });
          var rowsHtml = names.map(function(n){
            var x=e.byCashier[n];
            return '<tr>'+
              '<td>'+n+'</td>'+
              '<td class="tr">'+x.txns+'</td>'+
              '<td class="tr">'+fmt2(x.pre)+'</td>'+
              '<td class="tr">'+fmt2(x.card)+'</td>'+
              '<td class="tr">'+fmt2(x.cash)+'</td>'+
              '<td class="tr">'+fmt2(x.tax)+'</td>'+
              '<td class="tr">'+fmt2(x.tot)+'</td>'+
              '<td class="tr">'+fmt2(-x.disc)+'</td>'+
            '</tr>';
          }).join('');
          cT.innerHTML = rowsHtml || '<tr><td colspan="8" class="muted">No sales.</td></tr>';

          $id('eodSumTxns').textContent = String(e.overall.txns);
          $id('eodSumPre').textContent  = fmt2(e.overall.pre);
          $id('eodSumCard').textContent = fmt2(e.overall.card);
          $id('eodSumCash').textContent = fmt2(e.overall.cash);
          $id('eodSumTax').textContent  = fmt2(e.overall.tax);
          $id('eodSumTot').textContent  = fmt2(e.overall.tot);
          $id('eodSumDisc').textContent = fmt2(-e.overall.disc);
        }
      }

      function showEodAndPrint(){
        var card = $id('eodCard');
        if (!card) return;
        renderEod();
        card.style.display = 'block';
        setTimeout(function(){ window.print(); }, 50);
        setTimeout(function(){ card.style.display = 'none'; }, 500);
      }

      var btnEod = $id('btnPrintEod');
      if (btnEod) btnEod.addEventListener('click', showEodAndPrint);

      // =========================
      // SYNC: push -> sync -> relink -> refresh
      // =========================
      async function sleep (ms){ return new Promise(r=>setTimeout(r,ms)); }

      async function runInlineSyncWindow(){
        var initBody = {
          licenseNumber: LICENSE_NUMBER || undefined,
          start: START_DATE,
          end: END_DATE,
          mode: 'sales',
          page_size: 10,
        };
        var init = await fetchJson(SYNC_INLINE_INIT, 'POST', initBody, 30000);

        var state = {
          license: init.license || LICENSE_NUMBER || '',
          org_id: ORG_ID || undefined,
          mode: init.mode || 'sales',
          page: 1,
          page_size: (init.paging && init.paging.page_size) ? init.paging.page_size : 10,
          kind: 'active',
          sales: init.sales || {}
        };

        setProgress('Syncing receipts… active page 1');

        while (true){
          var body = {
            license: state.license,
            org_id: state.org_id,
            mode: state.mode,
            kind: state.kind,
            page: state.page,
            page_size: state.page_size,
            'sales.start_utc': state.sales.start_utc,
            'sales.end_utc': state.sales.end_utc
          };

          var step = await fetchJson(SYNC_INLINE_CHUNK, 'POST', body, 60000);
          if (step.throttled && step.retry_after){
            setProgress('Throttled by METRC, retry in '+step.retry_after+'s…');
            await sleep(step.retry_after * 1000);
          }

          if (step.done_kind){
            if (step.next_kind === 'done'){
              break;
            }
            state.kind = step.next_kind || 'inactive';
            state.page = 1;
            setProgress('Syncing receipts… '+state.kind+' page 1');
          } else {
            state.page = (step.next_page || (state.page+1));
            setProgress('Syncing receipts… '+state.kind+' page '+state.page);
          }

          await sleep(200);
        }

        setProgress('Sync complete.');
      }

      async function relinkWindow(){
        var body = {
          start_date: START_DATE,
          end_date: END_DATE,
          tolerance_seconds: 0,      // exact-only
          pre_abs_tolerance: PRE_ABS_TOL, // amount guard
          pre_pct_tolerance: PRE_PCT_TOL, // amount guard
          unlink_first: true,        // unlink stale links first
          hard: true,
          steal: true,
          organization_id: ORG_ID || undefined
        };
        setProgress('Linking sales to receipts…');
        await fetchJson(RELINK_WINDOW_URL, 'POST', body, 60000);
        setProgress('');
      }

      async function pushWindow(){
        var body = {
          start: START_DATE,
          end: END_DATE,
          organization_id: ORG_ID || undefined
        };
        setProgress('Pushing eligible sales to METRC…');
        var res = await fetchJson(PUSH_AND_SYNC_URL, 'POST', body, 120000);
        if (res && res.errors && res.errors.length){
          flash('Push errors: '+res.errors.length+' (see logs)', 'warn');
        } else {
          flash('Pushed '+(res.pushed||0)+' sale(s) to METRC.', 'ok');
        }
      }

      window.sync = async function(){
        try{
          _clearReloadFlag(); // allow one-time reload after manual push+sync+relink
          setProgress('Starting…');

          await pushWindow();
          await runInlineSyncWindow();
          await relinkWindow(); // strict relink as part of Sync, too

          if (_needFirstReload()){
            _markReloaded();
            location.reload();
            return;
          }

          await hydrateAllRows();
          await linkPaintCandidates();
          applyFilters();

          setProgress('All done.');
          flash('Push + Sync + Relink complete for '+START_DATE+' → '+END_DATE, 'ok');
        }catch(e){
          console.error(e);
          setProgress('');
          flash('Sync failed: '+(e.message||e), 'warn');
        }
      };
    })();
  </script>
@endpush
