@extends('layouts.frontend')

@section('content')
    <h1>{{ $brand->name }}</h1>
    <h2>Products</h2>
    @foreach($brand->products as $product)
        <div class="product-card">
            <h3><a href="{{ route('wholesale.product', $product->id) }}">{{ $product->name }}</a></h3>
            <p>Price: ${{ $product->price }}</p>
            <p>Inventory: {{ $product->quantity }}</p>
        </div>
    @endforeach
@endsection