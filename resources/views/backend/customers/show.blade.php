@extends('layouts.app')

@section('content')
@php
  use App\Sale;

  /** @var \App\Models\Customer $customer */

  // Pick the lookup email:
  //   - Prefer the customer's email column
  //   - If empty, but "name" looks like an email (your test), use that
  $lookupEmail = trim((string) ($customer->email ?? ''));
  if ($lookupEmail === '' && filter_var($customer->name, FILTER_VALIDATE_EMAIL)) {
      $lookupEmail = trim((string) $customer->name);
  }

  $start = request('start_date');
  $end   = request('end_date');

  // Query sales by email saved in the `name` field ONLY (no other columns assumed)
  $salesQuery = Sale::query()
    ->when($lookupEmail !== '', function ($q) use ($lookupEmail) {
        $q->whereRaw('LOWER(`name`) = ?', [strtolower($lookupEmail)]);
    })
    ->when($start, fn($q) => $q->whereDate('created_at', '>=', $start))
    ->when($end,   fn($q) => $q->whereDate('created_at', '<=', $end))
    ->orderByDesc('created_at');

  $sales   = $salesQuery->paginate(25)->appends(request()->query());
  $countAll = (clone $salesQuery)->count();
  $sumAll   = (clone $salesQuery)->sum('amount'); // uses your controller's final total column
@endphp

<style>
  .customer-header {
    display: grid; grid-template-columns: 70px 1fr auto; gap: 14px; align-items: center;
    padding: 14px; border: 1px solid #e6e9ef; border-radius: 14px; background: #fff; margin-bottom: 16px;
  }
  .avatar {
    width: 70px; height: 70px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    background: #f3f6fb; color: #4b5563; font-weight: 800; font-size: 22px; overflow: hidden;
  }
  .hdr-main { min-width: 0; }
  .hdr-name { font-size: 18px; font-weight: 800; line-height: 1.2; }
  .hdr-meta { font-size: 13px; color: #6b7280; }
  .hdr-stats { text-align: right; font-size: 13px; }
  .hdr-stats .big { font-weight: 800; font-size: 16px; color: #0f172a; }

  .filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px; }
  .filters input { min-width: 160px; }
</style>

<div class="row wrapper border-bottom white-bg page-heading">
  <div class="col-lg-8">
    <h2>Customer</h2>
    <ol class="breadcrumb">
      <li><a href="{{ route('customers.index') }}">Customers</a></li>
      <li class="active"><strong>Show</strong></li>
    </ol>
  </div>
  <div class="col-lg-4 text-right" style="padding-top:18px;">
    <a href="{{ route('customers.index') }}" class="btn btn-default btn-sm"><i class="fa fa-angle-left"></i> Back</a>
    <a href="{{ url('customers/' . $customer->id . '/edit') }}" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i> Edit</a>
  </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
  <div class="row">
    <div class="col-lg-12">

      {{-- Customer header card --}}
      <div class="customer-header">
        @php $letter = strtoupper(mb_substr($customer->name ?: ($customer->email ?? '?'), 0, 1)); @endphp
        <div class="avatar" aria-hidden="true">{{ $letter }}</div>

        <div class="hdr-main">
          <div class="hdr-name">{{ $customer->name ?? '—' }}</div>
          <div class="hdr-meta">
            <div><i class="fa fa-envelope-o"></i> {{ $customer->email ?: '—' }}</div>
            <div><i class="fa fa-phone"></i> {{ $customer->phone ?: '—' }}</div>
            <div><i class="fa fa-calendar-o"></i> Added: {{ optional($customer->created_at)->format('Y-m-d') }}</div>
            @if($lookupEmail)
              <div class="m-t-xs"><span class="label label-default">Searching sales by email: {{ $lookupEmail }}</span></div>
            @else
              <div class="m-t-xs text-muted"><em>No email available to match sales.</em></div>
            @endif
          </div>
        </div>

        <div class="hdr-stats">
          <div>Total sales</div>
          <div class="big">{{ number_format($countAll) }}</div>
          <div class="m-t-xs">Lifetime spend</div>
          <div class="big">${{ number_format($sumAll, 2) }}</div>
        </div>
      </div>

      {{-- Sales list --}}
      <div class="ibox float-e-margins">
        <div class="ibox-title">
          <h5>Past Sales</h5>
          <div class="pull-right">
            <form method="get" action="{{ route('customers.show', $customer->id) }}" class="filters">
              <input type="date" name="start_date" class="form-control" value="{{ $start }}">
              <input type="date" name="end_date" class="form-control" value="{{ $end }}">
              <button class="btn btn-default btn-sm"><i class="fa fa-filter"></i> Filter</button>
              @if($start || $end)
                <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-link btn-sm">Clear</a>
              @endif
            </form>
          </div>
        </div>

        <div class="ibox-content">
          @if($lookupEmail === '')
            <div class="text-center text-muted p-3">This customer has no email to match against sales.</div>
          @elseif($sales->count() === 0)
            <div class="text-center text-muted p-3">No sales found for {{ $lookupEmail }}.</div>
          @else
            <div class="table-responsive">
              <table class="table table-striped table-bordered table-hover">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Total (Post-Tax)</th>
                    <th>Paid</th>
                    <th>Change</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th style="width:120px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($sales as $idx => $sale)
                    <tr>
                      <td>{{ $sales->firstItem() + $idx }}</td>
                      <td>{{ \Carbon\Carbon::parse($sale->created_at)->format('Y-m-d H:i') }}</td>
                      <td>${{ number_format((float)$sale->amount, 2) }}</td>
                      <td>${{ number_format((float)($sale->total_given ?? 0), 2) }}</td>
                      <td>${{ number_format((float)($sale->change ?? 0), 2) }}</td>
                      <td><span class="label label-{{ $sale->payment_type==='card'?'info':'success' }}">{{ ucfirst($sale->payment_type) }}</span></td>
                      <td><span class="label label-{{ $sale->status==1 ? 'primary' : 'danger' }}">{{ $sale->status==1 ? 'Completed' : 'Canceled' }}</span></td>
                      <td>
                        <a href="{{ url('sales/receipt/'.$sale->id) }}" target="_blank" class="btn btn-default btn-xs">
                          <i class="fa fa-file-text-o"></i> Receipt
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="text-center">
              {{ $sales->links() }}
            </div>
          @endif
        </div>
      </div>

    </div>
  </div>
</div>
@endsection
