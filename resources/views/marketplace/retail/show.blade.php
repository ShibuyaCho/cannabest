@extends('layouts.app')

@section('content')
<div class="container py-4">
  <a href="{{ route('retail.public-marketplace') }}" class="btn btn-outline-secondary mb-3">&larr; Back</a>

  <div class="row g-4">
    <div class="col-md-5">
      <img class="img-fluid rounded" src="{{ $item->image ?: asset('uploads/no-image.png') }}" alt="{{ $item->name }}">
    </div>
    <div class="col-md-7">
      <h2 class="mb-1">{{ $item->name }}</h2>
      <div class="text-muted mb-2">
        {{ $item->category }} @if($item->producer) • {{ $item->producer }} @endif
      </div>
      <div class="h3 mb-3">${{ number_format((float)($item->price ?? 0), 2) }}</div>

      <dl class="row">
        <dt class="col-sm-3">PKG ID</dt><dd class="col-sm-9">{{ $item->label }}</dd>
        <dt class="col-sm-3">Stock</dt><dd class="col-sm-9">{{ (int)$item->storeQty }}</dd>
      </dl>

      <button class="btn btn-success" disabled>Add to cart (public demo)</button>
    </div>
  </div>
</div>
@endsection
