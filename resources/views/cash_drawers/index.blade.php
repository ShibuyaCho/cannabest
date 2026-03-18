{{-- resources/views/drawers/index.blade.php --}}
@extends('layouts.app')

@section('content')
@php
    $symbol = setting_by_key('currency') ?? '$';
    /** @var \App\Models\User $user */
    $user   = auth()->user();
    $org    = $user->organization ?? null;

    // ---------- cents helpers ----------
    if (!function_exists('drawer_to_cents'))  { function drawer_to_cents($v){ return (int) round(((float)$v) * 100); } }
    if (!function_exists('drawer_from_cents')){ function drawer_from_cents($c){ return $c / 100; } }

    // ---------- fallback item math (only if we truly have no saved totals) ----------
    if (!function_exists('drawer_row_gross_cents')) {
        function drawer_row_gross_cents($it): int {
            $toC = fn($v) => (int) round(((float)$v) * 100);
            foreach (['line_total','line_total_gross','total_line','price_total','amount_line'] as $k) {
                if (isset($it->$k) && is_numeric($it->$k) && (float)$it->$k > 0) return $toC($it->$k);
            }
            $price=(float)($it->price ?? 0);
            $qty  =(float)($it->quantity ?? 0);
            $flag = !empty($it->price_is_line_total) || !empty($it->is_line_total);
            $frac = abs($qty - floor($qty)) > 0.0001;
            if ($flag || $frac) return $toC($price);
            $unit = (isset($it->unit_price) && is_numeric($it->unit_price)) ? (float)$it->unit_price : $price;
            return $toC($unit * max(0,$qty));
        }
    }
    if (!function_exists('drawer_row_net_cents')) {
        function drawer_row_net_cents(int $grossC, $it): int {
            $toC = fn($v) => (int) round(((float)$v) * 100);
            $type=null; $val=0.0;
            foreach (['inline_discount_type','line_discount_type','discount_type'] as $k) if (!empty($it->$k)) { $type=strtolower((string)$it->$k); break; }
            foreach (['inline_discount_value','line_discount_value','discount_value'] as $k) if (isset($it->$k) && is_numeric($it->$k)) { $val=(float)$it->$k; break; }
            if     ($type==='percent')                    return max(0, $grossC - (int) round($grossC * $val / 100));
            elseif ($type==='amount' || $type==='fixed')  return max(0, $grossC - $toC($val));
            return $grossC;
        }
    }

    // ---------- EXACT per-sale receipt numbers (no recompute if saved) ----------
    if (!function_exists('drawer_sale_due_cents')) {
        /**
         * Returns Total Due for one sale in CENTS:
         * 1) If sale->amount exists: TRUST IT (this is the receipt “Total Due” and what went to METRC/KPI).
         * 2) Else, if we have saved subtotal/taxes: due = subtotal + state_tax + county_tax + city_tax.
         * 3) Else (last resort), derive from items (gross − discounts) + org-tax (only if absolutely no saved tax).
         */
        function drawer_sale_due_cents($sale, $org): int {
            $toC   = fn($v) => (int) round(((float)$v) * 100);
            $fromC = fn($c) => $c / 100;

            // (1) Stored total from receipt/KPI/METRC
            if (is_numeric($sale->amount)) {
                return $toC(round((float)$sale->amount, 2));
            }

            // (2) Saved pre-tax + saved taxes (still “what the receipt said”)
            $subC = is_numeric($sale->subtotal)   ? $toC((float)$sale->subtotal)   : null;
            $stC  = is_numeric($sale->state_tax)  ? $toC((float)$sale->state_tax)  : 0;
            $coC  = is_numeric($sale->county_tax) ? $toC((float)$sale->county_tax) : 0;
            $ciC  = is_numeric($sale->city_tax)   ? $toC((float)$sale->city_tax)   : 0;
            if ($subC !== null) return $subC + $stC + $coC + $ciC;

            // (3) Last resort only: derive from items + only-if-needed tax from org rates
            $sale->loadMissing(['items']);
            $afterLineC = 0;
            foreach ($sale->items as $it) {
                $g = drawer_row_gross_cents($it);
                $n = drawer_row_net_cents($g, $it);
                $afterLineC += $n;
            }
            $orderDiscC = $toC((float)($sale->order_discount_value ?? 0));
            if ($orderDiscC === 0 && is_numeric($sale->discount) && (float)$sale->discount > 0) {
                $orderDiscC = $toC((float)$sale->discount);
            }
            $preC = max(0, $afterLineC - $orderDiscC);

            // If we had saved tax, we’d have hit (2); so now compute from org rates as a last resort.
            $custType = strtolower((string)($sale->customer_type ?? 'consumer'));
            $isExempt = in_array($custType, ['patient','caregiver'], true);
            $statePct  = (float)($org->state_tax  ?? 0);
            $countyPct = (float)($org->county_tax ?? 0);
            $cityPct   = (float)($org->city_tax   ?? 0);
            $rate      = ($statePct + $countyPct + $cityPct) / 100.0;
            $taxC      = (!$isExempt && $rate > 0 && $preC > 0) ? $toC(round($fromC($preC) * $rate, 2)) : 0;

            return $preC + $taxC;
        }
    }

    if (!function_exists('drawer_sale_card_paid_cents')) {
        /**
         * Card charge shown on receipt: ceil( (Total Due + saved change) / 1.00 ) * 1.00
         * (Cashback causes “change” on the receipt; round UP to a whole dollar.)
         */
        function drawer_sale_card_paid_cents($sale, $org): int {
            $toC = fn($v) => (int) round(((float)$v) * 100);
            $dueC = drawer_sale_due_cents($sale, $org);
            $chgC = is_numeric($sale->change) ? $toC((float)$sale->change) : 0;
            return (int) (ceil(($dueC + $chgC) / 100) * 100);
        }
    }
@endphp

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
  <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="container">
  <h1>Cash Drawers</h1>
  <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#createDrawerModal">
    + New Drawer
  </button>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Status</th>
        <th>Assigned User</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($drawers as $drawer)
      <tr>
        <td>{{ $drawer->id }}</td>
        <td>{{ $drawer->name }}</td>
        <td>{{ ucfirst($drawer->status) }}</td>
        <td>{{ optional($drawer->assignedUser)->name ?? '—' }}</td>
        <td>
          <button class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#editDrawerModal-{{ $drawer->id }}">Edit</button>
          <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#historyModal-{{ $drawer->id }}">History</button>

          @if($drawer->currentSession)
            <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#closeDrawerModal-{{ $drawer->id }}">Close</button>
          @else
            <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#assignDrawerModal-{{ $drawer->id }}">Open</button>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>

{{-- CREATE DRAWER MODAL --}}
<div class="modal fade" id="createDrawerModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('admin.drawers.store') }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5>New Drawer</h5>
          <button class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Name</label>
            <input name="name" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary">Create</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- EDIT DRAWER MODAL --}}
@foreach($drawers as $drawer)
<div class="modal fade" id="editDrawerModal-{{ $drawer->id }}" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('admin.drawers.update', $drawer->id) }}" method="POST">
      @csrf @method('PUT')
      <div class="modal-content">
        <div class="modal-header">
          <h5>Edit Drawer #{{ $drawer->id }}</h5>
          <button class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Name</label>
            <input name="name" type="text" class="form-control" value="{{ $drawer->name }}" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary">Save</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endforeach

{{-- ASSIGN / OPEN DRAWER MODAL --}}
@foreach($drawers as $drawer)
<div class="modal fade" id="assignDrawerModal-{{ $drawer->id }}" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('drawers.open') }}" method="POST">
      @csrf
      <input type="hidden" name="drawer_id" value="{{ $drawer->id }}">
      <div class="modal-content">
        <div class="modal-header">
          <h5>Open Drawer #{{ $drawer->id }}</h5>
          <button class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Assign To User</label>
            <select name="user_id" class="form-control" required>
              <option value="">— select user —</option>
              @php
                // If there's a relation use it; else fall back to all users (avoid org column errors)
                $orgUsers = ($org && method_exists($org, 'users')) ? $org->users : \App\Models\User::orderBy('name')->get();
              @endphp
              @foreach($orgUsers as $u)
                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Starting Amount</label>
            <input name="opening_amount" type="number" step="0.01" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-success">Open</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endforeach

{{-- CLOSE DRAWER MODAL (now normalizes "Collected" from receipt-numbers to match Sales KPIs) --}}
@foreach($drawers as $drawer)
  @if($drawer->currentSession)
    @php
      $session = $drawer->currentSession;

      // Pull the same sales window KPI/receipts use — no org filter, no recompute
      $sales = \App\Sale::query()
        ->where('status', 1)
        ->whereBetween('created_at', [$session->opened_at, $session->closed_at ?? now()])
        ->where(function($q) use ($session) {
            $q->where('drawer_session_id', $session->id)
              ->orWhere(function($qq) use ($session) {
                  $qq->whereNull('drawer_session_id')
                     ->where('user_id', $session->user_id);
              });
        })
        ->with(['items'])   // only needed for ancient rows with no saved totals
        ->get();

      $collectedC   = 0;   // Σ receipt Total Due (post-tax) — server side fallback
      $cardChargedC = 0;   // Σ receipt Paid (Card + Cashback) rounded up whole-dollar

      foreach ($sales as $s) {
          $dueC = drawer_sale_due_cents($s, $org);
          $collectedC += $dueC;

          if (strtolower((string)$s->payment_type) === 'card') {
              $cardChargedC += drawer_sale_card_paid_cents($s, $org);
          }
      }

      $collected    = drawer_from_cents($collectedC);
      $cardCharged  = drawer_from_cents($cardChargedC);
      $cashNet      = drawer_from_cents($collectedC - $cardChargedC);
      $expectedCash = round((float)$session->starting_amount + $cashNet, 2);

      // expose sale ids for client-side "receipt-numbers" exact sum
      $saleIds = $sales->pluck('id')->all();
    @endphp

    <div class="modal fade" id="closeDrawerModal-{{ $drawer->id }}" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <form action="{{ route('drawers.close') }}" method="POST">
          @csrf
          <input type="hidden" name="drawer_id" value="{{ $drawer->id }}">
          <div class="modal-content">
            <div class="modal-header">
              <h5>Close Drawer #{{ $drawer->id }}</h5>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
              <div
                class="print-area"
                data-expected="{{ number_format($expectedCash, 2, '.', '') }}"
                data-collected="{{ number_format($collected, 2, '.', '') }}"
                data-card="{{ number_format($cardCharged, 2, '.', '') }}"
                data-starting="{{ number_format($session->starting_amount, 2, '.', '') }}"
                data-sale-ids='@json($saleIds)'
                style="width:300px;margin:auto;font-family:monospace;font-size:12px;"
              >
                <div class="text-center mb-2">
                  <strong>{{ optional($session->closed_at)->format('M j, Y g:i A') ?? now()->format('M j, Y g:i A') }}</strong><br>
                  Drawer close for<br>
                  <strong>{{ $drawer->name }}</strong><br>
                  Counted by {{ $user->name }}
                </div>
                <table width="100%" cellpadding="2">
                  <tr>
                    <td>Beginning Balance</td>
                    <td class="text-right">{{ $symbol }}{{ number_format($session->starting_amount,2) }}</td>
                  </tr>
                  <tr>
                    <td>Collected (Post-Tax)</td>
                    <td class="text-right js-collected">{{ $symbol }}{{ number_format($collected,2) }}</td>
                  </tr>
                  <tr>
                    <td>Card Charged (incl. Cashback)</td>
                    <td class="text-right js-card">{{ $symbol }}{{ number_format($cardCharged,2) }}</td>
                  </tr>
                  <tr>
                    <td>Cash Net to Drawer</td>
                    <td class="text-right js-cashnet">{{ $symbol }}{{ number_format($cashNet,2) }}</td>
                  </tr>
                </table>
                <hr>
                <table width="100%" cellpadding="2" style="border:1px solid#000;">
                  <tr><td>Total Count</td><td class="print-count">{{ $symbol }}0.00</td></tr>
                  <tr><td>Expected</td><td class="js-expected">{{ $symbol }}{{ number_format($expectedCash,2) }}</td></tr>
                  <tr><td class="print-label">Under/Over</td><td class="print-diff">{{ $symbol }}{{ number_format($expectedCash,2) }}</td></tr>
                </table>
              </div>

              <hr>

              <h6>Count Cash</h6>
              <table class="table table-sm">
                @foreach(array_reverse([100,50,20,10,5,1,0.25,0.10,0.05,0.01]) as $den)
                  <tr>
                    <td>{{ $symbol }}{{ number_format($den,2) }}</td>
                    <td><input type="number" min="0" step="1" class="form-control denom-count" data-denom="{{ $den }}" value="0"></td>
                    <td class="subtotal">0.00</td>
                  </tr>
                @endforeach
              </table>
              <input type="hidden" name="closing_amount" class="closingAmount">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" onclick="printModal('#closeDrawerModal-{{ $drawer->id }}')">Print</button>
              <button class="btn btn-primary">Confirm Close</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  @endif
@endforeach

{{-- HISTORY MODAL --}}
@foreach($drawers as $drawer)
<div class="modal fade" id="historyModal-{{ $drawer->id }}" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5>History — Drawer #{{ $drawer->id }}</h5>
        <button class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <table class="table table-striped">
          <thead><tr><th>Opened At</th><th>Closed At</th><th>Net</th><th>Actions</th></tr></thead>
          <tbody>
            @foreach($drawer->sessions as $s)
              <tr>
                <td>{{ optional($s->opened_at)->format('Y-m-d H:i') }}</td>
                <td>{{ optional($s->closed_at)->format('Y-m-d H:i') ?? '—' }}</td>
                <td>
                  @if($s->closing_amount !== null)
                    {{ $symbol }}{{ number_format(((float)$s->closing_amount) - ((float)$s->starting_amount),2) }}
                  @else
                    —
                  @endif
                </td>
                <td>
                  @if($s->closed_at)
                    <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#detailModal-{{ $s->id }}">Reprint</button>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endforeach

{{-- DETAIL / REPRINT MODAL --}}
@foreach($drawers as $drawer)
  @foreach($drawer->sessions as $session)
    @if($session->closed_at)
      @php
        $sales = \App\Sale::query()
          ->where('status', 1)
          ->whereBetween('created_at', [$session->opened_at, $session->closed_at])
          ->where(function($q) use ($session) {
              $q->where('drawer_session_id', $session->id)
                ->orWhere(function($qq) use ($session) {
                    $qq->whereNull('drawer_session_id')
                       ->where('user_id', $session->user_id);
                });
          })
          ->with(['items'])
          ->get();

        $collectedC   = 0;
        $cardChargedC = 0;
        foreach ($sales as $s) {
            $collectedC += drawer_sale_due_cents($s, $org);
            if (strtolower((string)$s->payment_type) === 'card') {
                $cardChargedC += drawer_sale_card_paid_cents($s, $org);
            }
        }

        $collected    = drawer_from_cents($collectedC);
        $cardCharged  = drawer_from_cents($cardChargedC);
        $cashNet      = drawer_from_cents($collectedC - $cardChargedC);
        $expectedCash = round((float)$session->starting_amount + $cashNet, 2);
      @endphp

      <div class="modal fade" id="detailModal-{{ $session->id }}" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5>Reprint Receipt — Session {{ $session->id }}</h5>
              <button class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
              <div class="print-area" data-expected="{{ $expectedCash }}" style="width:300px;margin:auto;font-family:monospace;font-size:12px;">
                <div class="text-center mb-2">
                  <strong>{{ optional($session->closed_at)->format('M j, Y g:i A') }}</strong><br>
                  Drawer close receipt for<br>
                  <strong>{{ $drawer->name }}</strong><br>
                  Counted by {{ optional($session->user)->name ?? '—' }}
                </div>

                <table width="100%" cellpadding="2">
                  <tr>
                    <td>Beginning Balance</td>
                    <td class="text-right">{{ $symbol }}{{ number_format($session->starting_amount,2) }}</td>
                  </tr>
                  <tr>
                    <td>Collected (Post-Tax)</td>
                    <td class="text-right">{{ $symbol }}{{ number_format($collected,2) }}</td>
                  </tr>
                  <tr>
                    <td>Card Charged (incl. Cashback)</td>
                    <td class="text-right">{{ $symbol }}{{ number_format($cardCharged,2) }}</td>
                  </tr>
                  <tr>
                    <td>Cash Net to Drawer</td>
                    <td class="text-right">{{ $symbol }}{{ number_format($cashNet,2) }}</td>
                  </tr>
                  <tr>
                    <td>Total Count</td>
                    <td class="print-count">{{ $symbol }}{{ number_format((float)($session->closing_amount ?? 0),2) }}</td>
                  </tr>
                  <tr>
                    <td>Expected Count</td>
                    <td>{{ $symbol }}{{ number_format($expectedCash,2) }}</td>
                  </tr>
                </table>

                <hr>

                <table width="100%" cellpadding="2" style="border:1px solid #000;">
                  <tr>
                    <td class="print-label">Under/Over</td>
                    <td class="print-diff">{{ $symbol }}{{ number_format(((float)($session->closing_amount ?? 0)) - $expectedCash,2) }}</td>
                  </tr>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" onclick="printModal('#detailModal-{{ $session->id }}')">Print</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    @endif
  @endforeach
@endforeach
@endsection

@push('scripts')
<script>
  const SYMBOL = @json($symbol);

  (function($){
    function updateDenominations(){
      let total = 0;
      $('.denom-count').each(function(){
        const count = parseInt(this.value,10) || 0;
        const denom = parseFloat($(this).data('denom')) || 0;
        const sub   = count * denom;
        $(this).closest('tr').find('.subtotal').text(sub.toFixed(2));
        total += sub;
      });

      $('input.closingAmount').val(total.toFixed(2));
      $('.print-area').each(function(){
        const pa   = $(this);
        const exp  = parseFloat(pa.data('expected')) || 0;
        const diff = total - exp;

        pa.find('.print-count').text(SYMBOL + total.toFixed(2));
        pa.find('.print-label').text(diff < 0 ? 'Under' : diff > 0 ? 'Over' : 'Balanced');
        pa.find('.print-diff').text(SYMBOL + Math.abs(diff).toFixed(2));
      });
    }

    $(document).on('input','.denom-count',updateDenominations);
    $('.modal').on('shown.bs.modal',updateDenominations);
  })(jQuery);

  window.printModal = function(sel){
    const mdl  = document.querySelector(sel);
    const area = mdl.querySelector('.print-area');
    const w    = window.open('','_blank','width=400,height=600');
    w.document.write(`
      <html>
        <head>
          <style>
            @page{margin:0}
            body{font-family:monospace;font-size:12px;margin:0;padding:0}
            table{width:100%;border-collapse:collapse}
            td{padding:2px}
          </style>
        </head>
        <body>${area.outerHTML}</body>
      </html>
    `);
    w.document.close();
    w.focus();
    w.print();
    w.onafterprint = ()=> w.close();
  };
</script>

{{-- === Normalize "Collected (Post-Tax)" using /sales/receipt-numbers to match Sales KPIs === --}}
<script>
  (function($){
    // Try to reuse route seeds if present elsewhere; fall back to hardcoded template
    const ROUTE_SEEDS = document.getElementById('routeSeeds')?.dataset || {};
    const RECEIPTNUM_URL_TMPL = ROUTE_SEEDS.receiptnum || '/sales/receipt-numbers/__SALE__';

    async function sumReceiptsTotals(saleIds){
      let cents = 0;
      for (const id of saleIds){
        try{
          const r = await fetch(RECEIPTNUM_URL_TMPL.replace('__SALE__', String(id)), { credentials: 'same-origin' });
          if (!r.ok) continue;
          const j = await r.json();
          const tot = parseFloat(j?.total_due ?? 0);
          if (!isNaN(tot)) cents += Math.round(tot * 100);
        }catch(_){}
      }
      return cents; // integer cents
    }

    async function recalcCollectedFromReceipts(modalEl){
      const pa = modalEl.querySelector('.print-area');
      if (!pa) return;

      let ids = [];
      try { ids = JSON.parse(pa.dataset.saleIds || '[]') || []; } catch(_){}
      if (!ids.length) return;

      const cents = await sumReceiptsTotals(ids);
      if (!cents && cents !== 0) return;

      const collected = cents / 100;
      const card     = parseFloat(pa.dataset.card || '0') || 0;
      const starting = parseFloat(pa.dataset.starting || '0') || 0;

      const cashNet  = +(collected - card).toFixed(2);
      const expected = +(starting + cashNet).toFixed(2);

      // Update DOM to match receipts/KPIs
      const fmt = (n) => SYMBOL + (Number(n).toFixed(2));
      const colCell = pa.querySelector('.js-collected');
      const netCell = pa.querySelector('.js-cashnet');
      const expCell = pa.querySelector('.js-expected');

      if (colCell) colCell.textContent = fmt(collected);
      if (netCell) netCell.textContent = fmt(cashNet);
      if (expCell) expCell.textContent = fmt(expected);

      // Update data-expected so Under/Over uses normalized expected
      pa.dataset.expected = expected.toFixed(2);

      // Re-run denomination calc (if user already entered counts)
      setTimeout(() => $(modalEl).find('.denom-count').trigger('input'), 0);
    }

    // Hook: when any Close Drawer modal opens, normalize its totals
    $('.modal').on('shown.bs.modal', function(){
      if (this.id && this.id.startsWith('closeDrawerModal-')) {
        recalcCollectedFromReceipts(this);
      }
    });
  })(jQuery);
</script>
@endpush
