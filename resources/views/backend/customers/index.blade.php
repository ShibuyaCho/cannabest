@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/plugins/dataTables/datatables.min.css') }}">

<style>
  .customer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
  }
  .customer-card {
    display: grid;
    grid-template-columns: 56px 1fr;
    grid-template-rows: auto auto;
    grid-template-areas:
      "avatar main"
      "actions actions";
    gap: 12px;
    padding: 14px;
    border: 1px solid #e6e9ef;
    border-radius: 14px;
    background: #fff;
    position: relative;
    transition: box-shadow .18s ease, transform .12s ease;
  }
  .customer-card:hover { box-shadow: 0 6px 18px rgba(15,23,42,.08); transform: translateY(-1px); }

  .avatar {
    grid-area: avatar;
    width: 56px; height: 56px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    background: #f3f6fb; color: #4b5563; font-weight: 800; font-size: 18px;
    overflow: hidden;
  }
  .avatar img { width: 100%; height: 100%; object-fit: cover; }

  .card-main { grid-area: main; min-width: 0; }
  .name { font-weight: 800; font-size: 16px; line-height: 1.2; margin-bottom: 4px; }
  .meta { font-size: 13px; color: #6b7280; line-height: 1.35; }
  .badge-soft {
    display: inline-block; font-size: 12px; padding: 2px 6px; border-radius: 6px;
    background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0; margin-left: 6px;
  }

  /* Actions row spans full width under the content so buttons have room */
  .card-actions {
    grid-area: actions;
    display: flex; flex-wrap: wrap; gap: 6px;
    margin-top: 2px;
  }
  .card-actions .btn-xs { white-space: nowrap; }
  /* Keep actions clickable above the stretched link */
  .action-btns { position: relative; z-index: 2; }
  .stretched-link { position: absolute; inset: 0; z-index: 1; }

  /* Compact buttons on narrow screens */
  @media (max-width: 520px){
    .card-actions .btn-xs { padding: 4px 6px; }
    .card-actions .btn-xs .label-text { display: none; }
    .card-actions .btn-xs i { margin: 0; }
  }

  .search-wrap { margin-bottom: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
  .search-wrap .input-group { max-width: 420px; }
</style>

<div class="row wrapper border-bottom white-bg page-heading">
  <div class="col-lg-10">
    <h2>Customers</h2>
  </div>
  <div class="col-lg-2"></div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
  <div class="row">
    <div class="col-lg-12">
      <div class="ibox float-e-margins">
        <div class="ibox-title">
          <h5>Customers</h5>
          <div class="ibox-tools">
            <a href="{{ url('customers/create') }}" class="btn btn-primary btn-xs">Add New</a>
            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
          </div>
        </div>

        <div class="ibox-content">
          <form method="get" action="{{ route('customers.index') }}" class="search-wrap">
            <div class="input-group">
              <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="Search name, email, or phone">
              <span class="input-group-btn">
                <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
              </span>
            </div>
            @if(!empty($q))
              <a href="{{ route('customers.index') }}" class="btn btn-link">Clear</a>
            @endif
          </form>

          @if($customers->count() === 0)
            <div class="text-center text-muted p-3">No customers found.</div>
          @else
            <div class="customer-grid">
              @foreach ($customers as $customer)
                @php
                  $letter = strtoupper(mb_substr($customer->name ?: ($customer->email ?? '?'), 0, 1));
                  $showUrl = route('customers.show', $customer->id);
                @endphp

                <div class="customer-card">
                  <a href="{{ $showUrl }}" class="stretched-link" aria-label="View {{ $customer->name ?? $customer->email }}"></a>

                  <div class="avatar" aria-hidden="true">{{ $letter }}</div>

                  <div class="card-main">
                    <div class="name">
                      {{ $customer->name ?? '—' }}
                      @if($customer->created_at)
                        <span class="badge-soft" title="Created">{{ $customer->created_at->format('Y-m-d') }}</span>
                      @endif
                    </div>
                    <div class="meta">
                      <div><i class="fa fa-envelope-o"></i> {{ $customer->email ?: '—' }}</div>
                      <div><i class="fa fa-phone"></i> {{ $customer->phone ?: '—' }}</div>
                    </div>
                  </div>

                  <div class="card-actions action-btns">
                    <a href="{{ $showUrl }}" class="btn btn-default btn-xs">
                      <i class="fa fa-list"></i> <span class="label-text">View Sales</span>
                    </a>
                    <a href="{{ url('customers/' . $customer->id . '/edit') }}" class="btn btn-primary btn-xs">
                      <i class="fa fa-pencil"></i> <span class="label-text">Edit</span>
                    </a>
                    <form action="{{ url('customers/' . $customer->id) }}" method="POST" class="d-inline" style="display:inline;" onsubmit="return confirm('Delete this customer?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-danger btn-xs">
                        <i class="fa fa-trash"></i> <span class="label-text">Delete</span>
                      </button>
                    </form>
                  </div>
                </div>
              @endforeach
            </div>

            <div class="text-center m-t-md">
              {{ $customers->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

{{-- (Optional) keep plugin include; not used for cards --}}
<script src="{{ asset('assets/js/plugins/dataTables/datatables.min.js') }}"></script>
@endsection
