@extends('layouts.frontend')

@section('content')
    <h1>Wholesale Accounts</h1>
    @foreach($wholesalers as $wholesaler)
        <div class="wholesaler-card">
            <h2><a href="{{ route('wholesale.show', $wholesaler->id) }}">{{ $wholesaler->name }}</a></h2>
            <p>Brands: {{ $wholesaler->brands->pluck('name')->implode(', ') }}</p>
            <p>Total Products: {{ $wholesaler->products->count() }}</p>
        </div>
    @endforeach
@endsection