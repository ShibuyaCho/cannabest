{{-- resources/views/sales/partials/metrc-panel.blade.php --}}
@php
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\Schema;
  use Carbon\Carbon;

  // ---- helpers ----
  $symbol = setting_by_key('currency') ?? '$';

  $storeTz = (function(){
    $raw = trim((string)(function_exists('setting_by_key') ? (setting_by_key('store_timezone') ?? '') : ''));
    if ($raw === '') $raw = (string) (config('app.timezone') ?: 'UTC');
    $map = [
      'PT'=>'America/Los_Angeles','PST'=>'America/Los_Angeles','PDT'=>'America/Los_Angeles',
      'MT'=>'America/Denver','MST'=>'America/Denver','MDT'=>'America/Denver',
      'CT'=>'America/Chicago','CST'=>'America/Chicago','CDT'=>'America/Chicago',
      'ET'=>'America/New_York','EST'=>'America/New_York','EDT'=>'America/New_York',
      'AKST'=>'America/Anchorage','AKDT'=>'America/Anchorage',
      'HST'=>'Pacific/Honolulu','HDT'=>'Pacific/Honolulu',
      'Pacific Standard Time'=>'America/Los_Angeles',
      'Mountain Standard Time'=>'America/Denver',
      'Central Standard Time'=>'America/Chicago',
      'Eastern Standard Time'=>'America/New_York',
    ];
    if (isset($map[$raw])) $raw = $map[$raw];
    return in_array($raw, \DateTimeZone::listIdentifiers(), true) ? $raw : 'UTC';
  })();

  $appTz = (string)(config('app.timezone') ?: 'UTC');

  // INV normalizer
  $invNorm = function(?string $s): ?string {
    $s = strtoupper(trim((string)$s));
    if ($s === '') return null;
    $s = preg_replace('/\s+/', '', $s);
    $s = preg_replace('/-R\d+$/', '', $s);
    if (!preg_match('/^INV\d{6}/', $s)) return null;
    return substr($s, 0, 9);
  };

  // POS invoice
  $posInvoice = function($row) {
    if (Schema::hasColumn('sales','invoice_number') && !empty($row->invoice_number)) return (string)$row->invoice_number;
    if (Schema::hasColumn('sales','invoice_no') && !empty($row->invoice_no)) return (string)$row->invoice_no;
    return null;
  };

  $mny = fn($n) => $symbol . number_format((float)$n, 2);

  // window (read from request; same as your page)
  $startDate = request('start_date', now($storeTz)->format('Y-m-d'));
  $endDate   = request('end_date',   now($storeTz)->format('Y-m-d'));
  $startLocal= $startDate . ' 00:00:00';
  $endLocal  = $endDate   . ' 23:59:59';

  // for METRC, use UTC window derived from Store Local
  $fromUtc = Carbon::parse($startLocal, $storeTz)->utc()->toDateTimeString();
  $toUtc   = Carbon::parse($endLocal,   $storeTz)->utc()->toDateTimeString();

  // license (pass as $license or $license_number from controller/view)
  $licenseNumber = $license ?? ($license_number ?? '');

  // POS rows in local window (completed)
  $orgId = auth()->user()->organization_id ?? null;
  $posQ = DB::table('sales as s')
           ->where('s.status', 1)
           ->whereBetween('s.created_at', [$startLocal, $endLocal]);

  if ($orgId !== null) {
    if (Schema::hasColumn('sales', 'organization_id')) {
      $posQ->where('s.organization_id', $orgId);
    } else {
      $posQ->join('users as u','u.id','=','s.user_id')
           ->where('u.organization_id', $orgId);
    }
  }

  $posCols = ['s.id','s.user_id','s.created_at','s.metrc_receipt_id','s.metrc_status'];
  foreach (['amount','payment_type','total_given','change','customer_type','invoice_number','invoice_no','discount','discount_amount','discount_total','discounts_total','tax'] as $c) {
    if (Schema::hasColumn('sales',$c)) $posCols[] = 's.'.$c;
  }
  $posRows   = $posQ->select($posCols)->orderBy('s.created_at','asc')->get();
  $cashiers  = DB::table('users')->pluck('name','id');

  // POS aggregates (pre-tax/tax are heuristic unless you already store them)
  $org   = auth()->user()->organization;
  $state = (float)($org->state_tax  ?? 0);
  $county= (float)($org->county_tax ?? 0);
  $city  = (float)($org->city_tax   ?? 0);
  $rate  = ($state + $county + $city) / 100.0;

  $sumPost = 0; $sumTax = 0; $sumPre = 0; $sumDisc = 0;
  foreach ($posRows as $row) {
    $due = round((float)($row->amount ?? 0), 2);
    $disc = 0.0;
    foreach (['discount_total','discounts_total','discount_amount','discount'] as $dc) {
      if (Schema::hasColumn('sales',$dc) && isset($row->$dc)) { $disc = (float)$row->$dc; break; }
    }
    $disc = round($disc, 2);
    $sumDisc += $disc;

    if (strtolower((string)($row->customer_type ?? 'consumer')) === 'consumer' && $rate > 0) {
      $pre = round($due / (1.0 + $rate), 2);
      $tax = round($due - $pre, 2);
    } else {
      $pre = $due;
      $tax = 0.00;
    }
    $sumPost += $due;
    $sumTax  += $tax;
    $sumPre  += $pre;
  }

  // METRC receipts in UTC window, filtered to INV###### external key
  $metrcRows = collect();
  if ($licenseNumber) {
    $raw = DB::table('metrc_receipts')
        ->where('license_number', $licenseNumber)
        ->whereBetween('sales_date_time', [$fromUtc, $toUtc])
        ->orderBy('sales_date_time','asc')
        ->get(['id','metrc_id','receipt_number','external_receipt_number','sales_date_time','total_price']);
    $metrcRows = $raw->filter(function($r) use ($invNorm){
      $k = $invNorm($r->external_receipt_number ?? null);
      if (!$k) return false;
      $r->external_inv9 = $k;
      return true;
    })->values();
  }

  $metrcCount = $metrcRows->count();
  $metrcSum   = round((float)$metrcRows->sum('total_price'), 2);
@endphp

<style>
  .metrc-grid { display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px; }
  @media (max-width: 1024px){ .metrc-grid { grid-template-columns: 1fr; } }
  .metrc-card { border:1px solid #e5e7eb; border-radius:14px; background:#fff; overflow:hidden; }
  .metrc-hd { padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:10px; border-bottom:1px solid #eef0f3; background:linear-gradient(180deg,#fbfbfc,#f6f7fb); }
  .metrc-ttl { font-weight:800; }
  .metrc-kpis { display:flex; gap:10px; flex-wrap:wrap; }
  .metrc-pill { background:#f9fafb; border:1px solid #eef0f3; border-radius:999px; padding:6px 10px; font-size:12px; }
  .metrc-body { padding:0; }
  .metrc-scroll { max-height: calc(100vh - 260px); overflow:auto; }
  .metrc-tbl { width:100%; border-collapse:collapse; }
  .metrc-tbl thead th { position:sticky; top:0; z-index:1; background:#f9fafb; border-bottom:1px solid #eef0f3; font-size:12px; font-weight:700; color:#374151; text-align:left; padding:9px 10px;}
  .metrc-tbl tbody td { border-bottom:1px solid #f1f5f9; padding:9px 10px; font-size:13px; color:#111827; vertical-align:top; }
  .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
  .muted { color:#6b7280; }
  .nowrap { white-space:nowrap; }
  .tag { display:inline-block; font-size:11px; padding:3px 7px; border-radius:999px; background:#eef2ff; color:#3730a3; border:1px solid #e0e7ff; }
  .btnx { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:8px; border:1px solid #e5e7eb; background:#fff; cursor:pointer; font-size:12px; }
  .btnx.primary { background:#111827; color:#fff; border-color:#111827;}
  .note { color:#6b7280; font-size:11px; padding:8px 12px; border-top:1px solid #eef0f3; background:#fafafa; }
</style>

<div class="metrc-grid">
  {{-- LEFT: Cannabest (POS) --}}
  <div class="metrc-card">
    <div class="metrc-hd">
      <div class="metrc-ttl">Cannabest Sales (Local primary)</div>
      <div class="metrc-kpis">
        <span class="metrc-pill">Txns <strong>{{ number_format($posRows->count()) }}</strong></span>
        <span class="metrc-pill">Pre-Tax <strong>{{ $mny($sumPre) }}</strong></span>
        <span class="metrc-pill">Tax <strong>{{ $mny($sumTax) }}</strong></span>
        <span class="metrc-pill">Discounts <strong>{{ $mny($sumDisc) }}</strong></span>
        <span class="metrc-pill">Total <strong>{{ $mny($sumPost) }}</strong></span>
      </div>
    </div>
    <div class="metrc-body metrc-scroll">
      <table class="metrc-tbl">
        <thead>
          <tr>
            <th>Time (Local)</th>
            <th class="muted">UTC</th>
            <th>Sale #</th>
            <th>Cashier</th>
            <th>Invoice / INV key</th>
            <th class="nowrap">Discount</th>
            <th class="nowrap">Total</th>
            <th>Pay</th>
            <th>METRC</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        @forelse($posRows as $s)
          @php
            $local = Carbon::parse($s->created_at, $appTz)->timezone($storeTz)->format('Y-m-d H:i:s');
            $utc   = Carbon::parse($s->created_at, $appTz)->utc()->format('Y-m-d H:i:s');
            $invoiceRaw = $posInvoice($s);
            $inv9       = $invNorm($invoiceRaw);
            $disc = 0.0;
            foreach (['discount_total','discounts_total','discount_amount','discount'] as $dc) {
              if (Schema::hasColumn('sales',$dc) && isset($s->$dc)) { $disc = (float)$s->$dc; break; }
            }
            $disc = round($disc, 2);
            $amt  = round((float)($s->amount ?? 0), 2);
            $pay  = isset($s->payment_type) ? ucfirst((string)$s->payment_type) : '—';
            $linked = !empty($s->metrc_receipt_id) || (isset($s->metrc_status) && $s->metrc_status === 'linked');
          @endphp
          <tr>
            <td class="mono">{{ $local }}</td>
            <td class="mono muted">{{ $utc }}</td>
            <td class="mono">#{{ $s->id }}</td>
            <td>{{ $cashiers[$s->user_id] ?? '—' }}</td>
            <td>
              <div class="mono">{{ $invoiceRaw ?: '—' }}</div>
              <div class="muted mono">INV key: {{ $inv9 ?: '—' }}</div>
            </td>
            <td class="nowrap">{{ $mny($disc) }}</td>
            <td class="nowrap">{{ $mny($amt) }}</td>
            <td><span class="tag">{{ $pay }}</span></td>
            <td>
              @if($linked)
                <span class="tag" style="background:#ecfdf5; color:#065f46; border-color:#d1fae5;">linked</span>
              @else
                <span class="tag" style="background:#fff7ed; color:#9a3412; border-color:#fed7aa;">unlinked</span>
              @endif
            </td>
            <td class="nowrap">
              @if(!$linked)
                <button class="btnx primary" onclick="openLinker({{ $s->id }})">Link</button>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="10" class="muted">No completed sales in this window.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div class="note">Linking marks a sale as <strong>linked</strong>; your METRC reporter should skip linked sales unless voided.</div>
  </div>

  {{-- RIGHT: METRC (INV###### only; UTC stored, Local shown too) --}}
  <div class="metrc-card">
    <div class="metrc-hd">
      <div class="metrc-ttl">METRC Receipts (INV######)</div>
      <div class="metrc-kpis">
        <span class="metrc-pill">Receipts <strong>{{ number_format($metrcCount) }}</strong></span>
        <span class="metrc-pill">Total <strong>{{ $mny($metrcSum) }}</strong></span>
        <span class="metrc-pill">UTC Window <span class="mono">{{ $fromUtc }}</span> → <span class="mono">{{ $toUtc }}</span></span>
      </div>
    </div>
    <div class="metrc-body metrc-scroll">
      <table class="metrc-tbl">
        <thead>
          <tr>
            <th>Time (Local)</th>
            <th class="muted">UTC</th>
            <th>METRC Id</th>
            <th>Receipt #</th>
            <th>INV key</th>
            <th class="nowrap">$ Total</th>
          </tr>
        </thead>
        <tbody>
        @forelse($metrcRows as $r)
          @php
            $utc   = Carbon::parse($r->sales_date_time, 'UTC')->format('Y-m-d H:i:s');
            $local = Carbon::parse($r->sales_date_time, 'UTC')->timezone($storeTz)->format('Y-m-d H:i:s');
          @endphp
          <tr>
            <td class="mono">{{ $local }}</td>
            <td class="mono muted">{{ $utc }}</td>
            <td class="mono">{{ $r->metrc_id }}</td>
            <td class="mono">{{ $r->receipt_number ?? '—' }}</td>
            <td class="mono">{{ $r->external_inv9 }}</td>
            <td class="nowrap">{{ $mny($r->total_price ?? 0) }}</td>
          </tr>
        @empty
          <tr><td colspan="6" class="muted">No METRC receipts (INV######) in this window.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div class="note">Only receipts whose External begins with <span class="mono">INV</span> + 6 digits are shown; value is normalized to first 9 chars (e.g. <span class="mono">INV000123</span>).</div>
  </div>
</div>

{{-- Linker Modal (shared) --}}
<div id="nearbyModal" class="modal fade" tabindex="-1">
  <div class="modal-dialog" style="max-width:900px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5>Link POS Sale → METRC</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="nearbyBody">
        <div class="alert alert-info">Loading…</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } });

  function getLicense() { return @json($licenseNumber ?? ''); }

  function extractError(x){
    try{
      if (x && x.responseJSON) {
        const r = x.responseJSON;
        if (typeof r === 'string') return r;
        if (r.message) return r.message;
        if (Array.isArray(r.errors)) return r.errors.join(', ');
        if (r.error) return r.error;
      }
      if (x && x.responseText) {
        try { const r = JSON.parse(x.responseText); if (r.message) return r.message; } catch(_){}
        return (x.responseText || '').slice(0,600);
      }
      if (x && x.status) return x.status + ' ' + (x.statusText||'');
      if (x && x.message) return x.message;
    }catch(e){}
    return 'Unknown error';
  }

  function getJSONWithRetry(url, data, {retries=2, baseDelay=400} = {}){
    return new Promise((resolve,reject)=>{
      const attempt = (n) => {
        $.getJSON(url, data).done(resolve).fail((xhr)=>{
          const s = xhr.status || 0;
          if (n < retries && (s === 0 || s === 429 || (s >= 500 && s <= 599))) {
            const delay = baseDelay * Math.pow(2, n);
            setTimeout(()=> attempt(n+1), delay);
          } else {
            reject(xhr);
          }
        });
      };
      attempt(0);
    });
  }

  async function openLinker(saleId){
    const license = getLicense();
    if (!license) { alert('License required'); return; }

    $('#nearbyBody').html(`
      <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:8px;">
        <div><strong>Sale #${saleId}</strong></div>
        <label style="margin:0 6px 0 12px;">Window</label>
        <select id="nl-min" class="form-control" style="width:auto; display:inline-block;">
          <option value="15">±15 min</option>
          <option value="30">±30 min</option>
          <option value="60" selected>±60 min</option>
          <option value="120">±120 min</option>
        </select>
        <input id="nl-q" class="form-control" placeholder="Filter by METRC id / receipt # / INV###### / total…" style="max-width:260px;">
        <button id="nl-refresh" class="btn btn-light btn-sm">Refresh</button>
      </div>
      <div id="nl-pos" class="text-muted" style="margin-bottom:8px;"></div>
      <div id="nl-table"><div class="alert alert-info">Loading…</div></div>
    `);
    $('#nearbyModal').modal('show');

    const render = async ()=>{
      const minutes = parseInt(document.getElementById('nl-min').value||'60',10);
      try {
        const cands = await getJSONWithRetry('/metrc/reconcile/candidates', { sale_id: saleId, licenseNumber: license, minutes }, {retries:2});
        const dbg   = await getJSONWithRetry('/metrc/reconcile/debug-sale', { sale_id: saleId, licenseNumber: license, minutes }, {retries:2});
        const when = (dbg?.sale_time || '—') + ' UTC';
        document.getElementById('nl-pos').innerHTML = `Sale time (as UTC center): <span class="mono">${when}</span> · Window: ±${minutes} min`;

        let rows = (cands?.candidates || []).slice();
        const q = (document.getElementById('nl-q').value||'').trim().toUpperCase();
        if (q) {
          rows = rows.filter(r=>{
            const hay = [r.metrc_id, r.receipt_number, r.external, r.ext_key_metrc, r.total_price]
              .map(x => (x==null?'':String(x).toUpperCase())).join(' ');
            return hay.includes(q);
          });
        }
        rows.sort((a,b)=>{
          const as = a?.match?.score ?? 0, bs = b?.match?.score ?? 0;
          if (as !== bs) return bs - as;
          const ad = a?.seconds_diff ?? 9e9, bd = b?.seconds_diff ?? 9e9;
          if (ad !== bd) return ad - bd;
          const at = parseFloat(a?.total_price ?? 0), bt = parseFloat(b?.total_price ?? 0);
          return bt - at;
        });

        let html = `
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>METRC Id</th>
                  <th>Receipt #</th>
                  <th>External</th>
                  <th>When (UTC)</th>
                  <th>Δ sec</th>
                  <th>Items</th>
                  <th>$ Total</th>
                  <th>Labels</th>
                  <th>Qty</th>
                  <th>Score</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
        `;
        if (!rows.length) {
          html += `<tr><td colspan="11"><div class="alert alert-warning">No METRC receipts found in window.</div></td></tr>`;
        } else {
          rows.forEach(r=>{
            const m = r.match || {};
            html += `
              <tr>
                <td class="mono">${r.metrc_id ?? '—'}</td>
                <td class="mono">${r.receipt_number ?? '—'}</td>
                <td class="mono">${r.ext_key_metrc ?? (r.external ?? '—')}</td>
                <td class="mono">${r.sales_date_time ?? '—'}</td>
                <td>${r.seconds_diff ?? '—'}</td>
                <td>${r.items ?? '—'}</td>
                <td class="text-right">${(r.total_price ?? '—')}</td>
                <td>${m.labelsMatch ? '✓' : '—'}</td>
                <td>${m.qtyClose ? '✓' : '—'}</td>
                <td>${typeof m.score === 'number' ? m.score : '—'}</td>
                <td><button class="btn btn-dark btn-sm" onclick="linkSpecific(${saleId}, ${r.metrc_id})">Link</button></td>
              </tr>
            `;
          });
        }
        html += '</tbody></table></div>';
        document.getElementById('nl-table').innerHTML = html;

      } catch(e) {
        document.getElementById('nl-table').innerHTML = '<div class="alert alert-danger">Failed: '+ extractError(e) +'</div>';
      }
    };

    document.getElementById('nl-refresh').onclick = render;
    document.getElementById('nl-min').onchange = render;
    let t=null; document.getElementById('nl-q').addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(render,250); });

    render();
  }

  async function linkSpecific(saleId, metrcId){
    try{
      await $.post('/metrc/reconcile/link', { sale_id: saleId, metrc_id: metrcId, licenseNumber: getLicense() });
      $('#nearbyModal').modal('hide');
      alert('Linked sale #'+ saleId +' → METRC '+ metrcId +'. This sale will be skipped on re-report unless voided.');
      location.reload();
    }catch(e){
      alert('Link failed: ' + extractError(e));
    }
  }
</script>
@endpush
