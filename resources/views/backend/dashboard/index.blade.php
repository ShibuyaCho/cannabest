{{-- resources/views/backend/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('content')
@php
  $start = $start ?? request('start_date', now()->toDateString());
  $end   = $end   ?? request('end_date',   now()->toDateString());

  // From controller:
  //   $saleIds     : array of sale IDs in window (status=1, org-scoped, not voided)
  //   $salePayMap  : [sale_id => 'Cash'|'Card'|'Other'] computed EXACTLY like Sales index ($payClass)
  $saleIds    = $saleIds    ?? [];
  $salePayMap = $salePayMap ?? [];

  // Source-of-truth endpoints (same as Sales page)
  $receiptUrlTemplate        = $receiptUrlTemplate ?? url('/sales/receipt/__SALE__').'?print=1';
  $receiptNumbersUrlTemplate = $receiptNumbersUrlTemplate ?? url('/sales/receipt/__SALE__/numbers'); // JSON first
@endphp

<style>
  .wrapper-content { padding-top:60px!important }
  .page-heading, .breadcrumb { display:none }
  .dash-wrap{ max-width:1000px; margin:10px auto; padding:8px }
  .card{ border:1px solid #e5e7eb; border-radius:12px; background:#fff }
  .card-header{ padding:12px 14px; border-bottom:1px solid #eef0f3; display:flex; justify-content:space-between; gap:12px; align-items:end; flex-wrap:wrap }
  .card-body{ padding:12px 14px }
  .grid-kpi{ display:grid; grid-template-columns:repeat(4,1fr); gap:8px }
  @media (max-width:900px){ .grid-kpi{ grid-template-columns:repeat(2,1fr) } }
  .tile .label{ font-size:12px; color:#6b7280; margin-bottom:4px }
  .tile .value{ font-size:22px; font-weight:800 }
  .muted{ color:#6b7280 }
  .btn{ padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; cursor:pointer }
  .btn.primary{ background:#111827; color:#fff; border-color:#111827 }
  .log{ font:12px ui-monospace,Menlo,Consolas,monospace; background:#0b1020; color:#cbd5e1; border-radius:8px; padding:10px; white-space:pre-wrap; max-height:220px; overflow:auto }
</style>

<div class="dash-wrap">
  <div class="card">
    <div class="card-header">
      <div>
        <div style="font-weight:800">Sales KPIs (by Receipt — identical to Sales page)</div>
        <div class="muted" style="font-size:12px">
          Card / Cash classification uses <code>sales.payment_type</code> ⇒ <strong>$payClass</strong> just like Sales table. Totals hydrate from <code>/sales/receipt/{id}/numbers</code>.
        </div>
      </div>
      <form id="rangeForm" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap">
        <div>
          <label class="muted" style="font-size:12px">From</label>
          <input type="date" id="start_date" name="start_date" value="{{ $start }}" class="form-control">
        </div>
        <div>
          <label class="muted" style="font-size:12px">To</label>
          <input type="date" id="end_date" name="end_date" value="{{ $end }}" class="form-control">
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap">
          <button type="button" id="applyBtn" class="btn primary">Apply</button>
          <button type="button" id="refreshBtn" class="btn">Refresh</button>
          <a class="btn" id="openSales" href="{{ url('/sales') }}?start_date={{ $start }}&end_date={{ $end }}" target="_blank" rel="noopener">Open Sales</a>
        </div>
      </form>
    </div>

    <div class="card-body">
      <div class="grid-kpi">
        @foreach ([['Gross (Post-Tax)','grossPost'],['Pre-Tax','preTax'],['Tax','tax'],['Net (Total − Tax)','net'],['Expected Cash','expectedCash'],['Card','card'],['Txns','txns'],['AOV (Pre-Tax)','aov']] as $tile)
          <div class="card tile">
            <div class="card-body">
              <div class="label">{{ $tile[0] }}</div>
              <div class="value" id="kpi-{{ $tile[1] }}">—</div>
            </div>
          </div>
        @endforeach
      </div>

      <div id="log" class="log" style="margin-top:10px">Loading…</div>
    </div>
  </div>
</div>

<script>
(() => {
  const SALE_IDS  = @json($saleIds);
  const SALE_PAY  = @json($salePayMap); // { [saleId]: 'Cash'|'Card'|'Other' } — EXACTLY like Sales page
  const RECEIPT_URL_TMPL         = @json($receiptUrlTemplate);
  const RECEIPT_NUMBERS_URL_TMPL = @json($receiptNumbersUrlTemplate);

  const qs = (s, r=document)=>r.querySelector(s);
  const logBox = qs('#log');
  const fmtMoney = n => isFinite(n) ? '$' + Number(n).toLocaleString(undefined, { minimumFractionDigits:2, maximumFractionDigits:2 }) : '—';
  const log = (m, cls='') => { const d=document.createElement('div'); if(cls) d.className=cls; d.textContent=m; logBox.appendChild(d); logBox.scrollTop=logBox.scrollHeight; };
  const clearLog = (m='') => { logBox.textContent=''; if(m) log(m,'muted'); };

  // EXACT same acceptance rule as Sales index
  const ceil10 = n => (n > 0 ? Math.ceil(n / 10) * 10 : 0);

  function setKPIs(k){
    qs('#kpi-grossPost').textContent    = fmtMoney(k.grossPost ?? 0);
    qs('#kpi-preTax').textContent       = fmtMoney(k.preTax ?? 0);
    qs('#kpi-tax').textContent          = fmtMoney(k.tax ?? 0);
    qs('#kpi-net').textContent          = fmtMoney(k.net ?? 0);
    qs('#kpi-expectedCash').textContent = fmtMoney(k.expectedCash ?? 0);
    qs('#kpi-card').textContent         = fmtMoney(k.card ?? 0);
    qs('#kpi-txns').textContent         = k.txns ?? 0;
    qs('#kpi-aov').textContent          = fmtMoney(k.aov ?? 0);
  }

  function parseMoneyLike(s){
    if (s == null) return 0;
    let t = String(s).trim();
    const parenNeg = t.includes('(') && t.includes(')');
    t = t.replace(/[^\d.\-]/g,'');
    let v = parseFloat(t);
    if (!Number.isFinite(v)) v = 0;
    if (parenNeg && v > 0) v = -v;
    return v;
  }

  function readNumbersJSON(r){
    const pre   = parseMoneyLike(r.pre_tax   ?? r.preTax   ?? r?.totals?.pre_tax   ?? 0);
    const tax   = parseMoneyLike(r.tax       ?? r?.totals?.tax       ?? 0);
    const total = parseMoneyLike(r.total_due ?? r.totalDue ?? r?.totals?.total     ?? 0);

    let paid     = parseMoneyLike(r.paid   ?? 0);
    let change   = parseMoneyLike(r.change ?? 0);

    // If receipt JSON is missing "paid", use "total" as a safe fallback (Sales page's r.paid is from the same source)
    if (!paid && total) paid = total;

    return { preTax: pre, tax, totalDue: total, paid, change };
  }

  async function fetchReceiptNumbers(id){
    const url = RECEIPT_NUMBERS_URL_TMPL.replace('__SALE__', id);
    const res = await fetch(url, { credentials:'same-origin' });
    if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
    return res.json();
  }

  async function run(){
    try{
      clearLog('Fetching receipt numbers…');

      if (!SALE_IDS.length){
        setKPIs({ preTax:0, tax:0, grossPost:0, net:0, expectedCash:0, card:0, txns:0, aov:0 });
        log('No completed sales in this window.','muted');
        return;
      }

      // Sum in CENTS (exact)
      let preC=0, taxC=0, totalC=0, netC=0, expectedCashC=0, cardAccC=0, txns=0;

      const q = SALE_IDS.slice();
      const W = Math.min(4, q.length);
      let done = 0;

      async function worker(){
        while (q.length){
          const id = q.shift();
          try{
            // 1) Numbers JSON (same source Sales page hydrates from)
            let pre=0, tax=0, total=0, paid=0, change=0;

            try{
              const r = await fetchReceiptNumbers(id);
              const v = readNumbersJSON(r || {});
              pre=v.preTax; tax=v.tax; total=v.totalDue; paid=v.paid; change=v.change;
            }catch(_){ /* keep zeros so row is skipped if truly missing */ }

            // If nothing meaningful, skip
            if ((total <= 0) && (paid <= 0)) {
              // no-op
            } else {
              // To cents for rollups
              const preCt   = Math.round(pre   * 100);
              const taxCt   = Math.round(tax   * 100);
              const totalCt = Math.round(total * 100);
              const paidCt  = Math.round(paid  * 100);
              const chgCt   = Math.round((change||0) * 100);

              preC   += preCt;
              taxC   += taxCt;
              totalC += totalCt;
              netC   += Math.max(0, totalCt - taxCt);

              // EXACT classification as Sales index (server computed $payClass)
              const payClass = SALE_PAY[String(id)] || 'Other';

              if (payClass === 'Cash') {
                // Expected Cash = Paid − Change (min 0) for cash rows
                expectedCashC += Math.max(0, paidCt - chgCt);
              } else if (payClass === 'Card') {
                // Card = ceil to $10 of PAID for card rows
                const accepted = Math.ceil(paid / 10) * 10; // dollars
                cardAccC += Math.round(accepted * 100);
              }
              // "Other" contributes to neither card nor expected cash.

              txns++;
            }
          }catch(e){
            log(`Sale #${id} failed: ${e.message}`, 'muted');
          }finally{
            done++;
            if (done % 10 === 0 || done === SALE_IDS.length) log(`Processed ${done}/${SALE_IDS.length}…`, 'muted');
          }
        }
      }
      await Promise.all(Array.from({length:W}, worker));

      const aovC = txns ? Math.round(preC / txns) : 0;

      setKPIs({
        preTax:      +(preC/100).toFixed(2),
        tax:         +(taxC/100).toFixed(2),
        grossPost:   +(totalC/100).toFixed(2),
        net:         +(netC/100).toFixed(2),
        expectedCash:+(expectedCashC/100).toFixed(2),
        card:        +(cardAccC/100).toFixed(2),
        txns,
        aov:         +(aovC/100).toFixed(2),
      });

      log(`Done. Aggregated ${txns} receipt(s) using Sales page payment classification.`, 'ok');
    }catch(e){
      log(`Error: ${e.message}`, 'err');
      console.error(e);
    }
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    const link = document.getElementById('openSales');
    const updateLink = () => {
      const s = document.getElementById('start_date').value;
      const e = document.getElementById('end_date').value;
      link.href = `{{ url('/sales') }}?start_date=${encodeURIComponent(s)}&end_date=${encodeURIComponent(e)}`;
    };
    document.getElementById('start_date').addEventListener('change', updateLink);
    document.getElementById('end_date').addEventListener('change', updateLink);
    updateLink();

    document.getElementById('applyBtn').addEventListener('click', ()=>{
      const s = document.getElementById('start_date').value;
      const e = document.getElementById('end_date').value;
      const u = new URL(location.href);
      u.searchParams.set('start_date', s);
      u.searchParams.set('end_date', e);
      location.href = u.toString();
    });

    document.getElementById('refreshBtn').addEventListener('click', ()=> run());

    run();
  });
})();
</script>
@endsection
