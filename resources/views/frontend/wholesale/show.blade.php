@extends('layouts.frontend')

@section('content')
    <h1>{{ $wholesaler->name }}</h1>
    <h2>Brands</h2>
    @foreach($wholesaler->brands as $brand)
        <div class="brand-card">
            <h3><a href="{{ route('wholesale.brand', $brand->id) }}">{{ $brand->name }}</a></h3>
            <p>Products: {{ $brand->products->count() }}</p>
        </div>
    @endforeach

    <h2>Products</h2>
    @foreach($wholesaler->products as $product)
        <div class="product-card">
            <h3><a href="{{ route('wholesale.product', $product->id) }}">{{ $product->name }}</a></h3>
            <p>Price: ${{ $product->price }}</p>
            <p>Inventory: {{ $product->quantity }}</p>
        </div>
    @endforeach
@endsection