@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h1 class="mb-3">Dispensary Marketplace</h1>

  <form class="row g-2 mb-3" method="get">
    <div class="col-sm-8">
      <input type="search" class="form-control" name="q" value="{{ $filters['q'] ?? '' }}"
             placeholder="Search by dispensary name, business name, license, address, or county…">
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
    @forelse($orgs as $o)
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card h-100">
          <div class="ratio ratio-16x9 bg-light">
            <img src="{{ $o->image ?: asset('uploads/no-image.png') }}" class="object-fit-cover" alt="{{ $o->name }}">
          </div>
          <div class="card-body">
            <h5 class="card-title mb-1 text-truncate" title="{{ $o->name }}">{{ $o->name }}</h5>
            @if($o->business_name)
              <div class="small text-muted mb-2">{{ $o->business_name }}</div>
            @endif
            <div class="small">
              @if($o->physical_address)
                <div><span class="text-muted">Address:</span> {{ $o->physical_address }}</div>
              @endif
              @if($o->license_number)
                <div><span class="text-muted">License:</span> {{ $o->license_number }}</div>
              @endif
              <div class="mt-2">
                <span class="badge bg-secondary">Taxes: {{ (int)$o->state_tax }}% state</span>
                @if(!is_null($o->county_tax)) <span class="badge bg-secondary ms-1">{{ (int)$o->county_tax }}% county</span> @endif
                @if(!is_null($o->city_tax))   <span class="badge bg-secondary ms-1">{{ (int)$o->city_tax }}% city</span> @endif
              </div>
            </div>
          </div>
          <div class="card-footer bg-transparent border-0">
            <a class="btn btn-sm btn-success w-100" href="{{ route('retail.org.menu', $o->id) }}">View Menu</a>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <div class="alert alert-info mb-0">No retail organizations found.</div>
      </div>
    @endforelse
  </div>

  <div class="mt-3">
    {{ $orgs->links() }}
  </div>
</div>
@endsection
