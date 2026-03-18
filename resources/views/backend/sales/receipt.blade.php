{{-- resources/views/sales/receipt.blade.php --}}
@extends('layouts.app')

@php
use Illuminate\Support\Facades\Route;

/** ---------- SETTINGS ---------- */
$salesBackUrl = Route::has('sales.create') ? route('sales.create')
              : (Route::has('sales.index') ? route('sales.index') : url('/sales'));

$currency   = setting_by_key('currency') ?: '$';
$countyTax  = (float)(setting_by_key('county_tax') ?: 0);
$cityTax    = (float)(setting_by_key('CityTax')    ?: 0);
$stateTax   = (float)(setting_by_key('StateTax')   ?: 0);
$taxPercent = $countyTax + $cityTax + $stateTax;

$bizName  = setting_by_key('business_name') ?: '';
$bizPhone = setting_by_key('company_phone') ?: '';
$bizAddr  = trim(implode(' ', array_filter([
    setting_by_key('company_address') ?: '',
    setting_by_key('company_city') ?: '',
    setting_by_key('company_state') ?: '',
    setting_by_key('company_zip') ?: '',
])));

$packagerLicense = optional($sale->branch)->license
                ?? optional(optional($sale->user)->organization)->license
                ?? setting_by_key('company_license')
                ?? '';

$items = $sale->items ?? $sale->saleItems ?? [];

/** ---------- RECEIPT OVERRIDES ---------- */
$receiptOverrides = $sale->receipt_overrides ?? [];
if (is_string($receiptOverrides)) {
    try { $receiptOverrides = json_decode($receiptOverrides, true) ?: []; } catch (\Throwable $e) { $receiptOverrides = []; }
}
if (!is_array($receiptOverrides)) { $receiptOverrides = []; }

/** ---------- METRC MAP (by label) ---------- */
$metrcMap = [];
foreach ($items as $row) {
    $inv = $row->inventory ?? null;

    $rowPkgLabel = $row->package_label
        ?? $row->metrc_label
        ?? ($inv->Label ?? $inv->label ?? null);

    $m = $row->metrc_package
      ?? $row->metrcPackage
      ?? ($inv->metrc_package ?? $inv->metrcPackage ?? null);

    if ($m instanceof \Illuminate\Database\Eloquent\Model) $m = $m->toArray();
    if (is_string($m)) { try { $m = json_decode($m, true) ?: []; } catch (\Throwable $e) { $m = []; } }
    if (is_array($m) && isset($m[0]) && is_array($m[0])) $m = $m[0];

    $labs = $row->metrc_full_labs ?? ($inv->metrc_full_labs ?? []);
    if ($labs instanceof \Illuminate\Support\Collection) $labs = $labs->toArray();
    if (is_string($labs)) { try { $labs = json_decode($labs, true) ?: []; } catch (\Throwable $e) { $labs = []; } }
    if (!is_array($labs)) $labs = [];

    $labels = [];
    if (is_array($m)) {
        foreach (['Label','label','PackageLabel'] as $k) if (!empty($m[$k])) $labels[] = $m[$k];
        if (!empty($m['payload'])) {
            $p = is_string($m['payload']) ? (json_decode($m['payload'], true) ?: []) : (is_array($m['payload']) ? $m['payload'] : []);
            foreach (['Label','label','PackageLabel'] as $k) if (!empty($p[$k])) $labels[] = $p[$k];
            if (!empty($p['Item'])) foreach (['Label','label','PackageLabel'] as $k) if (!empty($p['Item'][$k])) $labels[] = $p[$k];
        }
    }
    if ($rowPkgLabel) $labels[] = $rowPkgLabel;
    $labels = array_values(array_unique(array_filter($labels)));

    foreach ($labels as $L) $metrcMap[$L] = ['metrc' => $m ?: (object)[], 'labs' => $labs];
}

/** ---------- HELPERS ---------- */
function a2($v) {
    if ($v instanceof \Illuminate\Support\Collection) return $v->toArray();
    if (is_string($v)) { try { $d = json_decode($v, true); return is_array($d) ? $d : []; } catch (\Throwable $e) { return []; } }
    if (is_object($v)) return (array)$v;
    return is_array($v) ? $v : [];
}
function money($v){
    if ($v === null) return 0.0;
    if (is_string($v)) {
        $vv = preg_replace('/[^\d\.\-\+]/', '', $v);
        if ($vv === '' || $vv === '-' || $vv === '+') return 0.0;
        return (float)$vv;
    }
    return is_numeric($v) ? (float)$v : 0.0;
}
function flat_assoc($arr, $prefix=''){
    $out = [];
    foreach ((array)$arr as $k=>$v) {
        $key = $prefix === '' ? (string)$k : ($prefix.'.'.$k);
        if (is_array($v)) { $out += flat_assoc($v,$key); }
        else $out[$key] = $v;
    }
    return $out;
}
function first_num($flat, array $keys){
    foreach ($keys as $k) if (array_key_exists($k, $flat)) { $n = money($flat[$k]); if ($n > 0) return $n; }
    return 0.0;
}
function first_str($flat, array $keys){
    foreach ($keys as $k) if (array_key_exists($k, $flat)) { $s = trim((string)$flat[$k]); if ($s !== '') return $s; }
    return '';
}
function regex_pick_max($flat, $pattern){
    $max = 0.0;
    foreach ($flat as $k=>$v) {
        if (@preg_match($pattern, $k)) {
            $n = money($v);
            if ($n > $max) $max = $n;
        }
    }
    return $max;
}

/** ---------- PAYMENT EXTRACTION (robust) ---------- */
$paid = ['cash'=>0.0, 'card'=>0.0, 'other'=>0.0, 'card_last4'=>[], 'method'=>null];
$method = strtolower($sale->payment_type ?? $sale->paymentType ?? '');

if (method_exists($sale, 'payments') && ($sale->payments ?? null)) {
    foreach ($sale->payments as $p) {
        $m = strtolower(trim($p->method ?? $p->type ?? ''));
        $amt = money($p->amount ?? $p->value ?? 0);
        $l4 = trim((string)($p->last4 ?? $p->card_last4 ?? ''));
        if ($m === 'cash') $paid['cash'] += $amt;
        elseif (in_array($m, ['card','credit','debit'])) { $paid['card'] += $amt; if ($l4!=='') $paid['card_last4'][] = $l4; }
        elseif ($m !== '') $paid['other'] += $amt;
    }
}
if (method_exists($sale, 'payment') && ($sale->payment ?? null)) {
    $p = $sale->payment;
    $m = strtolower(trim($p->method ?? $p->type ?? ''));
    $amt = money($p->amount ?? $p->value ?? 0);
    $l4 = trim((string)($p->last4 ?? $p->card_last4 ?? ''));
    if ($m === 'cash') $paid['cash'] += $amt;
    elseif (in_array($m, ['card','credit','debit'])) { $paid['card'] += $amt; if ($l4!=='') $paid['card_last4'][] = $l4; }
    elseif ($m !== '') $paid['other'] += $amt;
}

/* common arrays/maps */
foreach (['tenders','paid','transactions'] as $bucket) {
    $arr = a2($sale->{$bucket} ?? null);
    if (!$arr) continue;
    if (isset($arr[0]) && is_array($arr[0])) {
        foreach ($arr as $t) {
            $m = strtolower(trim($t['method'] ?? $t['type'] ?? $t['channel'] ?? ''));
            $amt = money($t['amount'] ?? $t['value'] ?? $t['total'] ?? 0);
            $l4 = trim((string)($t['last4'] ?? $t['card_last4'] ?? ''));
            if ($m === 'cash') $paid['cash'] += $amt;
            elseif (in_array($m, ['card','credit','debit'])) { $paid['card'] += $amt; if ($l4!=='') $paid['card_last4'][] = $l4; }
            elseif ($m !== '') $paid['other'] += $amt;
        }
    } else {
        $paid['cash'] += money($arr['cash'] ?? $arr['cash_received'] ?? $arr['cashReceived'] ?? $arr['cash_total'] ?? $arr['cashTotal'] ?? $arr['cashTendered'] ?? $arr['cash_tendered'] ?? $arr['tendered_cash'] ?? 0);
        $paid['card'] += money($arr['card'] ?? $arr['card_total'] ?? $arr['cardTotal'] ?? $arr['charged'] ?? $arr['charged_total'] ?? $arr['chargedTotal'] ?? $arr['authorized_amount'] ?? $arr['card_amount'] ?? $arr['cardAmount'] ?? 0);
        $l4 = trim((string)($arr['card_last4'] ?? $arr['last4'] ?? $arr['last_4'] ?? $arr['cc_last4'] ?? ''));
        if ($l4!=='') $paid['card_last4'][] = $l4;
        if (isset($arr['method'])) $method = $method ?: strtolower(trim($arr['method']));
    }
}

/* flat columns */
$paid['cash'] += money($sale->paid_cash ?? $sale->cash_received ?? $sale->cashReceived ?? $sale->cash ?? 0);
$paid['card'] += money($sale->paid_card ?? $sale->card_total ?? $sale->cardTotal ?? $sale->card ?? $sale->card_amount ?? $sale->authorized_amount ?? 0);
$l4flat = trim((string)($sale->card_last4 ?? $sale->cardLast4 ?? $sale->cc_last4 ?? ''));
if ($l4flat!=='') $paid['card_last4'][] = $l4flat;

/* JSON blobs */
$blobs = [
    a2($sale->payment_meta ?? null),
    a2($sale->payment_details ?? null),
    a2($sale->meta ?? null),
    a2($sale->data ?? null),
    a2($sale->details ?? null),
    a2($sale->payload ?? null),
    a2($sale->request_payload ?? null),
    a2($sale->extra ?? null),
    a2($sale->extra_data ?? null),
    a2($sale->checkout ?? null),
    a2($sale->params ?? null),
];
foreach ($blobs as $blob) {
    if (!$blob) continue;
    $paid['cash'] += money($blob['cash'] ?? $blob['cash_received'] ?? $blob['cashReceived'] ?? $blob['cash_total'] ?? $blob['cashTotal'] ?? $blob['cashTendered'] ?? $blob['cash_tendered'] ?? $blob['tendered_cash'] ?? 0);
    $paid['card'] += money($blob['card'] ?? $blob['card_total'] ?? $blob['cardTotal'] ?? $blob['charged'] ?? $blob['charged_total'] ?? $blob['chargedTotal'] ?? $blob['authorized_amount'] ?? $blob['auth_amount'] ?? $blob['card_amount'] ?? $blob['cardAmount'] ?? 0);
    $l4 = trim((string)($blob['card_last4'] ?? $blob['last4'] ?? $blob['last_4'] ?? $blob['cc_last4'] ?? ''));
    if ($l4!=='') $paid['card_last4'][] = $l4;
    if (isset($blob['payment_type']) || isset($blob['paymentType'])) $method = $method ?: strtolower(trim($blob['payment_type'] ?? $blob['paymentType']));
}

/* flattened sweep + regex (catches odd names) */
$flat = flat_assoc(a2($sale->toArray()));
if ($paid['card'] <= 0) {
    $paid['card'] += first_num($flat, [
        'card_total','cardTotal','card_amount','cardAmount','amount_card',
        'authorized_amount','authorizedAmount','auth_amount','authAmount',
        'charged_total','chargedTotal','charge_total','chargeTotal',
        'payment.card_total','payment.cardAmount','payment.card_amount',
        'meta.card_total','payment_meta.card_total','payment_details.card_total',
        'data.card_total','payload.card_total','request_payload.card_total',
        'extra.card_total','extra_data.card_total','checkout.card_total','params.card_total',
        'reader.amount','terminal.amount','transaction.amount'
    ]);
    if ($paid['card'] <= 0) {
        $paid['card'] += regex_pick_max($flat, '/(card|credit|debit).*?(amount|total|paid|charge|auth)/i');
    }
}
if ($paid['cash'] <= 0) {
    $paid['cash'] += first_num($flat, [
        'cash_received','cashReceived','paid_cash','cash','cash_total','cashTotal',
        'cashTendered','cash_tendered','tendered_cash',
        'payment.cash_received','meta.cash_received','data.cash_received',
        'payload.cash_received','request_payload.cash_received',
    ]);
    if ($paid['cash'] <= 0) {
        $paid['cash'] += regex_pick_max($flat, '/(cash|tendered).*?(amount|total|paid|received)/i');
    }
}
$l4any = first_str($flat, [
    'card_last4','cardLast4','cc_last4','payment.card_last4','meta.card_last4','data.card_last4','payload.card_last4','request_payload.card_last4'
]);
if ($l4any !== '') $paid['card_last4'][] = $l4any;

/* decide method if empty */
if ($method === '') {
    if ($paid['cash']>0 && $paid['card']>0) $method = 'split';
    elseif ($paid['card']>0) $method = 'card';
    elseif ($paid['cash']>0) $method = 'cash';
    elseif ($paid['other']>0) $method = 'other';
    else $method = '—';
}
$paid['method'] = $method;
$paid['card_last4'] = array_values(array_unique(array_filter(array_map('strval', $paid['card_last4']))));
$paidLast4Text = count($paid['card_last4']) ? ' • ' . implode(' + ', array_map(fn($x) => '****'.substr($x, -4), $paid['card_last4'])) : '';
@endphp

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="{{ asset('assets/css/plugins/toastr/toastr.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/plugins/sweetalert/sweetalert.css') }}">

<style>
.receipt-wrap { max-width: 980px; margin: 0 auto; }
.receipt-card { background:#fff; border:1px solid #e5e8f0; border-radius:14px; box-shadow:0 10px 30px rgba(10,20,40,.05); }
.receipt-card .card-header{ display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid #eef2f7;}
.receipt-card .card-header h3{ margin:0; font-weight:800; letter-spacing:.2px; }
.receipt-card .card-body{ padding:18px; }
.receipt-meta { display:grid; grid-template-columns:repeat(3,1fr); gap:10px 20px; font-size:14px; }
.receipt-meta div b{ display:block; font-size:12px; color:#667085; text-transform:uppercase; letter-spacing:.06em; }
.table { width:100%; border-collapse:collapse; }
.table thead th { background:#f9fafb; font-weight:700; font-size:13px; color:#475467; border-bottom:1px solid #eef2f7; padding:10px; }
.table tbody td { font-size:14px; padding:10px; border-bottom:1px solid #f2f4f7; vertical-align:middle; }
.table tfoot td { padding:10px; font-size:14px; }
.table .text-right{ text-align:right; }
.badge { display:inline-block; padding:2px 6px; font-size:12px; border-radius:6px; background:#eef7ff; color:#0b72e7; border:1px solid #cfe6ff; font-weight:700; }
.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border-radius:8px; border:1px solid #d0d5dd; background:#fff; cursor:pointer; }
.btn:hover{ background:#f9fafb; }
.btn-primary{ background:#0ea5e9; border-color:#0ea5e9; color:#fff; }
.btn-primary:hover{ filter:brightness(.95); }
.actions-col { white-space:nowrap; }
h4.totline { margin:4px 0; }
.total-box { display:grid; grid-template-columns:1fr auto; gap:6px; max-width:420px; margin-left:auto; }
.payment-box { display:grid; grid-template-columns:1fr auto; gap:6px; max-width:420px; margin-left:auto; margin-top:10px; }
@media print { .no-print { display:none !important; } .receipt-card { border:0; box-shadow:none; } }
</style>

<div class="receipt-wrap">
  <div class="receipt-card">
    <div class="card-header">
      <div style="display:flex;align-items:center;gap:10px;">
        <a href="{{ $salesBackUrl }}" class="btn no-print">← Back to Sales</a>
        <h3>Receipt #{{ $sale->id ?? '—' }}</h3>
      </div>
      <div class="no-print" style="display:flex;gap:8px;flex-wrap:wrap;">
        <button id="printReceiptBtn" class="btn btn-primary"><i class="fa fa-print"></i> Print Receipt</button>
      </div>
    </div>

    <div class="card-body">
      <div class="receipt-meta">
        <div>
          <b>Business</b>
          @if($bizName !== '')<div>{{ $bizName }}</div>@endif
          @if($bizPhone)<div>{{ $bizPhone }}</div>@endif
          @if($bizAddr)<div>{{ $bizAddr }}</div>@endif
          @if($packagerLicense)<div>Packaging Lic: {{ $packagerLicense }}</div>@endif
        </div>
        <div>
          <b>Sale</b>
          <div>ID: {{ $sale->id ?? '—' }}</div>
          <div>Date: {{ optional($sale->created_at)->format('M d, Y g:i A') ?? now()->format('M d, Y g:i A') }}</div>
          <div>Cashier: {{ optional($sale->user)->name ?? '—' }}</div>
        </div>
        <div>
          <b>Customer</b>
          <div>{{ $sale->customer_name ?? $sale->customerName ?? ($sale->customer->name ?? '—') }}</div>
          <div>Type: {{ ucfirst($sale->customer_type ?? $sale->customerType ?? 'consumer') }}</div>
          @php
            $nums = $sale->receipt_numbers ?? null;
            if(!$nums){
              try { $nums = app(\App\Http\Controllers\SaleController::class)->receiptNumbers($sale->id)->getData(true) ?? null; }
              catch (\Throwable $e) { $nums = null; }
            }
          @endphp
          @if(!empty($nums['patient']) || !empty($nums['caregiver']))
            <div>Patient #: {{ $nums['patient'] ?? '—' }}</div>
            <div>Caregiver #: {{ $nums['caregiver'] ?? '—' }}</div>
          @elseif(!empty($sale->med_number) || !empty($sale->caregiver_number))
            <div>Patient #: {{ $sale->med_number ?? '—' }}</div>
            <div>Caregiver #: {{ $sale->caregiver_number ?? '—' }}</div>
          @endif
        </div>
      </div>

      <hr>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Details</th>
              <th class="text-right">Qty</th>
              <th class="text-right">Price</th>
              <th class="text-right">Line Total</th>
              <th class="no-print">Actions</th>
            </tr>
          </thead>
          <tbody>
          @php $sub=0; $taxable=0; $inlineDiscountTotal=0; @endphp
          @foreach($items as $row)
            @php
              $inv   = $row->inventory ?? null;
              $name  = $row->name ?? optional($inv)->name ?? optional($row->product)->name ?? '—';
              $qty   = (float)($row->quantity ?? $row->qty ?? 0);
              $unit  = (float)($row->unit_price ?? $row->price ?? 0);
              $line  = (float)($row->line_total ?? ($row->price_is_line_total ? ($row->price ?? 0) : ($unit * max(1,(int)$qty))));
              $discT = $row->inline_discount_type ?? null;
              $discV = (float)($row->inline_discount_value ?? 0);
              if($discT==='percent') { $lineAfterInline = max(0, $line - ($line * $discV/100)); }
              elseif($discT==='fixed') { $lineAfterInline = max(0, $line - $discV); }
              else { $lineAfterInline = $line; }
              $inlineDiscount = $line - $lineAfterInline;
              $sub += $lineAfterInline;

              $catName = optional(optional($inv)->categoryDetail)->name ?? '';
              $isTaxExempt = in_array(\Illuminate\Support\Str::lower($catName), ['accessories','apparel','hemp']) ? 1 : 0;
              if(!$isTaxExempt) $taxable += $lineAfterInline;

              $inlineDiscountTotal += $inlineDiscount;

              $m = $row->metrc_package ?? ($inv->metrc_package ?? null);
              if ($m instanceof \Illuminate\Database\Eloquent\Model) $m = $m->toArray();
              if (is_string($m)) { try { $m = json_decode($m, true) ?: []; } catch(\Throwable $e) { $m = []; } }
              if (is_array($m) && isset($m[0]) && is_array($m[0])) $m = $m[0];

              $payload = is_array($m) && isset($m['payload'])
                  ? (is_string($m['payload']) ? (json_decode($m['payload'], true) ?: []) : (is_array($m['payload']) ? $m['payload'] : []))
                  : [];
              $metrcLabel = $m['Label'] ?? $m['label'] ?? $m['PackageLabel']
                         ?? $payload['Label'] ?? $payload['label'] ?? $payload['PackageLabel'] ?? null;

              $pkgId = $metrcLabel
                    ?? $row->package_label
                    ?? $row->metrc_label
                    ?? ($inv->Label ?? $inv->label ?? null);

              $sku   = $row->sku ?? ($inv->sku ?? ($pkgId ?? ''));
              $labs  = $row->metrc_full_labs ?? ($inv->metrc_full_labs ?? []);
              if ($labs instanceof \Illuminate\Support\Collection) $labs = $labs->toArray();

              $invBrief = null;
              if ($inv) {
                  $invBrief = [
                      'supplier_name'    => $inv->supplier_name ?? $inv->vendor_name ?? optional($inv->supplier)->name ?? optional($inv->brand)->name ?? null,
                      'producer_license' => $inv->producer_license ?? $inv->license_number ?? $inv->license ?? null,
                      'thc_percent'      => $inv->thc_percent ?? $inv->TotalPotentialTHCPercent ?? null,
                      'cbd_percent'      => $inv->cbd_percent ?? $inv->TotalPotentialCBDPercent ?? null,
                  ];
              }

              $override = [];
              if ($pkgId && is_array($receiptOverrides)) $override = $receiptOverrides[$pkgId] ?? [];
            @endphp

            <tr class="receipt-row"
                data-id="{{ $inv->id ?? $row->product_id ?? $row->inventory_id ?? '' }}"
                data-name="{{ $name }}"
                data-sku="{{ $sku }}"
                data-label="{{ $pkgId }}"
                data-grams="{{ number_format((float)$qty, 2, '.', '') }}"
                data-metrc='@json($m ?? (object)[])'
                data-labs='@json($labs ?? [])'
                data-inv='@json($invBrief ?? (object)[])'
                data-override='@json($override ?? (object)[])'
            >
              <td>
                <div style="font-weight:700;">{!! e($name) !!}</div>
                @if($pkgId)
                  <div><span class="badge">Pkg: {{ $pkgId }}</span></div>
                @endif
                @if($discT && $discV>0)
                  <div class="badge" style="background:#fff6e5;border-color:#fdd99b;color:#8a5500;">
                    Discount: {{ $discT==='percent' ? number_format($discV,0).'%' : $currency.number_format($discV,2) }}
                  </div>
                @endif
              </td>
              <td>
                @if($row->price_is_line_total ?? false)
                  <div>Tiered Flower</div>
                @else
                  <div>Unit Price: {{ $currency }}{{ number_format($unit, 2) }}</div>
                @endif
              </td>
              <td class="text-right">{{ $row->price_is_line_total ? number_format($qty,2) : number_format(max(1,(int)$qty)) }}</td>
              <td class="text-right">{{ $currency }}{{ number_format($row->price_is_line_total ? ($row->unit_price ?? ($line / max($qty,1))) : $unit, 2) }}</td>
              <td class="text-right">{{ $currency }}{{ number_format($lineAfterInline, 2) }}</td>
              <td class="no-print actions-col">
                <button class="btn btn-primary btn-sm reprint-label" title="Reprint 4×2 Label">Reprint</button>
                @if($pkgId)
                  <button class="btn btn-sm view-metrc" title="View METRC">METRC</button>
                @endif
                <button class="btn btn-sm edit-line" title="Edit label fields">Edit</button>
              </td>
            </tr>
          @endforeach
          </tbody>

          <tfoot>
            @php
              $orderDiscType  = $sale->order_discount_type  ?? $sale->orderDiscountType  ?? null;
              $orderDiscValue = (float)($sale->order_discount_value ?? $sale->orderDiscountValue ?? 0);
              $orderDiscount  = 0.0;
              if($orderDiscValue>0){
                $orderDiscount = $orderDiscType==='percent' ? ($sub * $orderDiscValue/100) : min($orderDiscValue, $sub);
              }
              $taxablePortion = $sub>0 ? ($taxable / $sub) : 0;
              $taxAmount = (($sale->customer_type ?? $sale->customerType ?? 'consumer') === 'consumer')
                           ? (($taxable - $orderDiscount * $taxablePortion) * ($taxPercent/100))
                           : 0;
              $total = round($sub - $orderDiscount + $taxAmount, 2);

              $changeFromDb = money($sale->change_due ?? $sale->changeDue ?? $sale->change ?? 0);

              /* compute tender/change (NO card-from-change fallback) */
              $tendered = round(($paid['cash'] + $paid['card'] + $paid['other']), 2);

              if ($tendered > 0) {
                  $change = max(0, round($tendered - $total, 2));
              } else {
                  if ($paid['method'] === 'card') {
                      // Card-only but no explicit amount → assume exact charge
                      $paid['card'] = $total;
                      $tendered = $total;
                      $change = 0.0;
                  } elseif ($paid['method'] === 'cash') {
                      // Cash-only and a change amount exists → use it
                      if ($changeFromDb > 0) {
                          $paid['cash'] = round($total + $changeFromDb, 2);
                          $tendered = $paid['cash'];
                          $change = round($changeFromDb, 2);
                      } else {
                          $tendered = 0.0; $change = 0.0;
                      }
                  } else {
                      $tendered = 0.0; $change = 0.0;
                  }
              }

              // Split: never infer from change; only backfill the missing leg if total known
              if ($paid['method'] === 'split' && $tendered > 0) {
                  if ($paid['cash'] <= 0 && $paid['card'] > 0) $paid['cash'] = max(0, round($tendered - $paid['card'], 2));
                  if ($paid['card'] <= 0 && $paid['cash'] > 0) $paid['card'] = max(0, round($tendered - $paid['cash'], 2));
              }
            @endphp
            <tr>
              <td colspan="6">
                <div class="total-box">
                  <div>Subtotal</div><div class="text-right">{{ $currency }}{{ number_format($sub,2) }}</div>
                  <div>Item Discounts</div><div class="text-right">- {{ $currency }}{{ number_format($inlineDiscountTotal,2) }}</div>
                  <div>Order Discount @if($orderDiscType) ({{ $orderDiscType==='percent' ? $orderDiscValue.'%' : $currency.number_format($orderDiscValue,2) }}) @endif</div>
                  <div class="text-right">- {{ $currency }}{{ number_format($orderDiscount,2) }}</div>
                  <div>Tax ({{ number_format($taxPercent,2) }}%)</div><div class="text-right">{{ $currency }}{{ number_format($taxAmount,2) }}</div>
                  <hr style="grid-column:1 / span 2; border:0; border-top:1px solid #e5e7eb;">
                  <h4 class="totline"><b>Total</b></h4>
                  <h4 class="totline text-right"><b>{{ $currency }}{{ number_format($total,2) }}</b></h4>
                </div>

                <div class="payment-box">
                  <div>Payment Method</div>
                  <div class="text-right">
                    @if($paid['method']==='split') Split (Cash + Card)
                    @elseif($paid['method']==='—' || $paid['method']==='') —
                    @else {{ ucfirst($paid['method']) }}
                    @endif
                  </div>

                  <div>Paid (Cash)</div>
                  <div class="text-right">{{ $currency }}{{ number_format($paid['cash'], 2) }}</div>

                  <div>Paid (Card){{ $paidLast4Text }}</div>
                  <div class="text-right">{{ $currency }}{{ number_format($paid['card'], 2) }}</div>

                  @if($paid['other'] > 0)
                    <div>Paid (Other)</div>
                    <div class="text-right">{{ $currency }}{{ number_format($paid['other'], 2) }}</div>
                  @endif

                  <div>Total Tendered</div>
                  <div class="text-right">{{ $currency }}{{ number_format($tendered, 2) }}</div>

                  <hr style="grid-column:1 / span 2; border:0; border-top:1px solid #e5e7eb; margin:2px 0 0 0;">
                  <h4 class="totline">Change Due</h4>
                  <h4 class="totline text-right">{{ $currency }}{{ number_format(max(0,$change), 2) }}</h4>
                </div>
              </td>
            </tr>
          </tfoot>
        </table>

        @if(request()->boolean('paydebug'))
          <pre class="no-print" style="background:#f7f7f8;border:1px solid #eee;border-radius:8px;padding:10px;margin-top:12px;font-size:12px;">
PAY DEBUG
method: {{ $paid['method'] }}
cash:   {{ number_format($paid['cash'], 2) }}
card:   {{ number_format($paid['card'], 2) }}
other:  {{ number_format($paid['other'], 2) }}
last4:  {{ implode(', ', $paid['card_last4']) ?: '—' }}
change_from_db: {{ number_format($changeFromDb, 2) }}
tendered: {{ number_format($tendered, 2) }}
          </pre>
        @endif

      </div>
    </div>
  </div>
</div>

{{-- METRC Modal --}}
<div class="modal fade" id="metrcModal" tabindex="-1" data-bs-focus="false" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">METRC Package Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height:60vh;overflow:auto;">
          <table class="table table-striped mb-0">
            <tbody id="metrcModalBody"></tbody>
            <tbody id="metrcLabBody" style="display:none;"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button id="showLabBtn" class="btn btn-info">View Full Tests</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- Edit Line Modal --}}
<div class="modal fade" id="editLineModal" tabindex="-1" data-bs-focus="false" aria-hidden="true" aria-labelledby="editLineTitle">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="editLineTitle" class="modal-title">Edit Label Fields</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editLineForm" class="row g-3">
          <input type="hidden" id="ovLabel">
          <div class="col-12">
            <label class="form-label">Product Name</label>
            <input type="text" class="form-control" id="ovName">
          </div>
          <div class="col-md-6">
            <label class="form-label">Producer Name</label>
            <input type="text" class="form-control" id="ovProducerName">
          </div>
          <div class="col-md-6">
            <label class="form-label">Producer License</label>
            <input type="text" class="form-control" id="ovProducerLicense">
          </div>
          <div class="col-md-6">
            <label class="form-label">Packaging (Org) License</label>
            <input type="text" class="form-control" id="ovPackagerLicense" value="{{ $packagerLicense }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">THC %</label>
            <input type="number" step="0.01" class="form-control" id="ovThcPct">
          </div>
          <div class="col-md-3">
            <label class="form-label">CBD %</label>
            <input type="number" step="0.01" class="form-control" id="ovCbdPct">
          </div>
          <div class="col-md-4">
            <label class="form-label">SKU / Label</label>
            <input type="text" class="form-control" id="ovSku">
          </div>
          <div class="col-md-4">
            <label class="form-label">Weight (g)</label>
            <input type="number" step="0.01" class="form-control" id="ovGrams">
          </div>
          <div class="col-md-4">
            <label class="form-label">Print Size</label>
            <select id="ovSize" class="form-select">
              <option value="4x2" selected>4×2 in</option>
              <option value="3x2">3×2 in</option>
              <option value="2x1">2×1 in</option>
            </select>
          </div>
        </form>
        <small class="text-muted">These overrides persist for this package label and will be applied on future prints.</small>
      </div>
      <div class="modal-footer">
        <button id="saveOverrideBtn" class="btn btn-primary">Save</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  window.METRC_MAP = @json($metrcMap, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  window.RECEIPT_OVERRIDES = @json($receiptOverrides, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  window.PACKAGER_LIC_DEFAULT = @json($packagerLicense);
  window.SALE_ID = @json($sale->id);
</script>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script>
(function($){
  $(function(){

    const esc = (s)=> String(s==null?'':s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
      .replace(/'/g,'&#39;');

    function printViaIframe(htmlDoc) {
      let iframe = document.getElementById('label_print_iframe');
      if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'label_print_iframe';
        iframe.style.position = 'fixed'; iframe.style.right = '0'; iframe.style.bottom = '0';
        iframe.style.width = '0'; iframe.style.height = '0'; iframe.style.border = '0';
        iframe.setAttribute('aria-hidden','true'); iframe.setAttribute('tabindex','-1');
        document.body.appendChild(iframe);
      }
      const win = iframe.contentWindow || iframe;
      const doc = win.document || iframe.contentDocument;
      doc.open(); doc.write(htmlDoc); doc.close();

      const finalize = ()=>{ try { win.focus(); win.print(); } catch(_){} };
      const imgs = doc.images;
      if (!imgs || !imgs.length) { finalize(); return; }
      let loaded = 0;
      const check = ()=>{ if (loaded >= imgs.length) finalize(); };
      [...imgs].forEach(img=>{
        if (img.complete) { loaded++; check(); }
        else { img.addEventListener('load', ()=>{loaded++;check();});
               img.addEventListener('error',()=>{loaded++;check();}); }
      });
      setTimeout(check, 1000);
    }

    $('#printReceiptBtn').on('click', function(){
      const html = document.querySelector('.receipt-card').outerHTML;
      const css  = `
        @page { size: auto; margin: 12mm; }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
        .no-print { display:none !important; }
        .receipt-card { border:0; box-shadow:none; }
        .table { width:100%; border-collapse:collapse; }
        .table thead th { background:#f9fafb; border-bottom:1px solid #eef2f7; }
        .table td, .table th { border-bottom:1px solid #f2f4f7; padding:8px; font-size:12px; }
      `;
      const doc = `<!doctype html><html><head><meta charset="utf-8"><style>${css}</style></head><body>${html}</body></html>`;
      printViaIframe(doc);
    });

    // METRC viewer
    const normMetrc = (m)=> Array.isArray(m) ? (m[0]||{}) : (m||{});
    function flattenRows(obj){
      let rows = '';
      const walk=(o,p='')=>{
        if (Array.isArray(o)) return o.forEach((v,i)=>walk(v,`${p}[${i}]`));
        if (o && typeof o==='object') return Object.entries(o).forEach(([k,v])=>walk(v,p?`${p}.${k}`:k));
        rows += `<tr><th>${esc(p)}</th><td>${esc(o)}</td></tr>`;
      };
      walk(obj);
      return rows || '<tr><td class="p-3 text-muted">No METRC data for this label.</td></tr>';
    }
    $(document).on('click','.view-metrc',function(){
      const $row = $(this).closest('tr.receipt-row');
      const label = $row.attr('data-label') || '';
      let metrc = normMetrc(JSON.parse($row.attr('data-metrc') || '{}'));
      let labs  = JSON.parse($row.attr('data-labs') || '[]');
      if ((!metrc || !Object.keys(metrc).length) && label && window.METRC_MAP && window.METRC_MAP[label]) {
        metrc = normMetrc(window.METRC_MAP[label].metrc || {});
        labs  = window.METRC_MAP[label].labs || [];
      }
      $('#metrcModalBody').html(flattenRows(metrc));
      if(Array.isArray(labs) && labs.length){
        let labRows=''; labs.forEach(l=>{ Object.entries(l||{}).forEach(([k,v])=>labRows+=`<tr><th>${esc(k)}</th><td>${esc(v)}</td></tr>`); labRows+='<tr><td colspan="2"><hr></td></tr>';});
        $('#metrcLabBody').html(labRows).hide(); $('#showLabBtn').show();
      } else { $('#metrcLabBody').empty().hide(); $('#showLabBtn').hide(); }
      const el = document.getElementById('metrcModal');
      el.setAttribute('data-bs-focus','false');
      bootstrap.Modal.getOrCreateInstance(el,{focus:false}).show();
    });
    $('#showLabBtn').on('click', function(){
      $('#metrcModalBody, #metrcLabBody').toggle();
      $(this).text($('#metrcLabBody').is(':visible') ? 'Back to Package' : 'View Full Tests');
    });

    // Edit & save overrides
    $(document).on('click','.edit-line',function(){
      const $row = $(this).closest('tr.receipt-row');
      const label = $row.attr('data-label') || '';
      const name  = $row.attr('data-name') || '';
      const sku   = $row.attr('data-sku')  || '';
      const grams = parseFloat($row.attr('data-grams')) || 1;

      let override = {};
      try { override = JSON.parse($row.attr('data-override') || '{}'); } catch(_) {}

      $('#ovLabel').val(label);
      $('#ovName').val(override.name ?? name);
      $('#ovProducerName').val(override.producer_name ?? '');
      $('#ovProducerLicense').val(override.producer_license ?? '');
      $('#ovPackagerLicense').val(override.packager_license ?? (window.PACKAGER_LIC_DEFAULT || ''));
      $('#ovThcPct').val(override.thc_pct ?? '');
      $('#ovCbdPct').val(override.cbd_pct ?? '');
      $('#ovSku').val(override.sku ?? (sku || label));
      $('#ovGrams').val(override.grams ?? grams.toFixed(2));
      $('#ovSize').val(override.size ?? '4x2');

      const el = document.getElementById('editLineModal');
      el.setAttribute('data-bs-focus','false');
      bootstrap.Modal.getOrCreateInstance(el,{focus:false}).show();
    });

    $('#saveOverrideBtn').on('click', function(){
      const label = $('#ovLabel').val();
      if (!label) { toastr.error('No package label found for this line.'); return; }

      const override = {
        name: $('#ovName').val().trim(),
        producer_name: $('#ovProducerName').val().trim(),
        producer_license: $('#ovProducerLicense').val().trim(),
        packager_license: $('#ovPackagerLicense').val().trim(),
        thc_pct: $('#ovThcPct').val() === '' ? null : parseFloat($('#ovThcPct').val()),
        cbd_pct: $('#ovCbdPct').val() === '' ? null : parseFloat($('#ovCbdPct').val()),
        sku: $('#ovSku').val().trim(),
        grams: $('#ovGrams').val() === '' ? null : parseFloat($('#ovGrams').val()),
        size: $('#ovSize').val()
      };

      window.RECEIPT_OVERRIDES = window.RECEIPT_OVERRIDES || {};
      window.RECEIPT_OVERRIDES[label] = override;

      const $row = $(`tr.receipt-row[data-label="${label}"]`);
      $row.attr('data-override', JSON.stringify(override));

      const url = `/sales/${encodeURIComponent(window.SALE_ID)}/receipt-overrides`;
      const payload = { _token: $('meta[name="csrf-token"]').attr('content'), overrides: [{ label, fields: override }] };

      $.post(url, payload)
        .done(()=>{ toastr.success('Saved.'); bootstrap.Modal.getOrCreateInstance(document.getElementById('editLineModal')).hide(); })
        .fail((xhr)=>{ toastr.error('Save failed.'); console.error(xhr?.responseText || xhr); });
    });

  });
})(jQuery);
</script>
@endsection
