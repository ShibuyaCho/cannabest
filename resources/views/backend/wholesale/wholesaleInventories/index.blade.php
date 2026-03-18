@extends('layouts.Wholesale')

@section('content')
<div class="container">
  <h2 class="text-center mb-4">Wholesale Companies</h2>

  {{-- Global search --}}
  <div class="form-group mb-4">
    <input
      type="text"
      id="globalSearch"
      class="form-control"
      placeholder="Search by company, brand, or product…"
    >
  </div>

  {{-- Cards grid --}}
  <div class="row" id="wholesaleCards">
    @foreach($wholesales as $w)
      @php
        // split brands into an array
        $brandList = $w->brandNames
          ? array_map('trim', explode(',', $w->brandNames))
          : [];

        // collect all pName values from the products JSON
        $prodList = collect($w->products)
          ->pluck('pName')
          ->filter()        // remove null or empty
          ->all();
      @endphp

      <div
        class="col-md-4 mb-4 wholesale-card"
        data-name="{{ $w->name }}"
        data-brands='@json($brandList)'
        data-products='@json($prodList)'
      >
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">{{ $w->name }}</h5>
            <p class="card-text mb-1">
              <strong>Brands:</strong>
              @if(count($brandList))
                {{ implode(', ', $brandList) }}
              @else
                <span class="text-muted">—</span>
              @endif
            </p>
            <p class="card-text">
              <strong>Products:</strong>
              @if(count($prodList))
                {{ implode(', ', $prodList) }}
              @else
                <span class="text-muted">—</span>
              @endif
            </p>
            <a
              href="{{ route('wholesaleInventories.show', $w) }}"
              class="btn btn-primary mt-auto"
            >View Details</a>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>

{{-- Fuse.js fuzzy search on name, brands, products --}}
<script src="https://cdn.jsdelivr.net/npm/fuse.js@6.6.2"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Build array of { element, name, brands, products }
    const cards = Array.from(document.querySelectorAll('.wholesale-card'))
      .map(el => ({
        element: el,
        name: el.dataset.name,
        brands: JSON.parse(el.dataset.brands),
        products: JSON.parse(el.dataset.products),
      }));

    // Configure Fuse to search across all three keys
    const fuse = new Fuse(cards, {
      keys: ['name', 'brands', 'products'],
      threshold: 0.3,  // lower = stricter, higher = more fuzzy
    });

    const input = document.getElementById('globalSearch');
    input.addEventListener('input', () => {
      const term = input.value.trim();
      // if empty search, show all cards
      const matched = term
        ? fuse.search(term).map(result => result.item.element)
        : cards.map(c => c.element);

      // toggle visibility
      cards.forEach(c => {
        c.element.style.display = matched.includes(c.element) ? '' : 'none';
      });
    });
  });
</script>
@endsection
