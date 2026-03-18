@extends('layouts.app')

@section('content')
<div class="container py-4">
  <a href="{{ route('retail.public-marketplace') }}" class="btn btn-outline-secondary mb-3">&larr; All Dispensaries</a>

  <div class="d-flex align-items-center mb-3">
    <img src="{{ $org->image ?: asset('uploads/no-image.png') }}" alt="{{ $org->name }}"
         class="me-3 rounded" style="width:64px;height:64px;object-fit:cover;">
    <div>
      <h2 class="mb-0">{{ $org->name }}</h2>
      <div class="text-muted small">
        @if($org->business_name) {{ $org->business_name }} &middot; @endif
        @if($org->license_number) License {{ $org->license_number }} &middot; @endif
        Taxes: {{ (int)$org->state_tax }}% state
        @if(!is_null($org->county_tax)) • {{ (int)$org->county_tax }}% county @endif
        @if(!is_null($org->city_tax)) • {{ (int)$org->city_tax }}% city @endif
      </div>
    </div>
  </div>

  <form class="row g-2 mb-3" method="get">
    <div class="col-sm-8">
      <input type="search" class="form-control" name="q" value="{{ $filters['q'] ?? '' }}"
             placeholder="Search this menu by name, producer, or PKG ID…">
    </div>
    <div class="col-sm-2">
      <select class="form-control" name="per_page" onchange="this.form.submit()">
        @foreach([12,24,36,48] as $n)
          <option value="{{ $n }}" @selected(($filters['per_page'] ?? 24) == $n)>{{ $n }}/page</option>
        @endforeach
      </select>
    </div>
    <div class="col-sm-2">
      <button class="btn btn-success w-100">Search</button>
    </div>
  </form>

  <div class="row g-3">
    @forelse($products as $p)
      <div class="col-6 col-md-4 col-lg-3">
        <div class="card h-100">
          <img src="{{ $p->image ?: asset('uploads/no-image.png') }}" class="card-img-top" alt="{{ $p->name }}">
          <div class="card-body">
            <div class="small text-muted">
              @if($p->producer) {{ $p->producer }} @endif
            </div>
            <h6 class="card-title text-truncate" title="{{ $p->name }}">{{ $p->name }}</h6>
            <div class="mb-2 small text-muted">PKG ID: {{ $p->label }}</div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="fw-bold">${{ number_format((float)($p->price ?? 0), 2) }}</span>
              <span class="badge bg-secondary">Stock: {{ (int)$p->storeQty }}</span>
            </div>
          </div>
          <div class="card-footer bg-transparent border-0">
            {{-- Public demo: view only --}}
            <button class="btn btn-sm btn-success w-100" disabled>Add to cart</button>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <div class="alert alert-info mb-0">No items in this menu.</div>
      </div>
    @endforelse
  </div>

  <div class="mt-3">
    {{ $products->links() }}
  </div>
</div>
@endsection
