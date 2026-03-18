@extends('layouts.frontend')

@section('content')
    <h1>{{ $product->name }}</h1>
    <p>Brand: {{ $product->brand->name }}</p>
    <p>Price: ${{ $product->price }}</p>
    <p>Inventory: {{ $product->quantity }}</p>
    <p>Description: {{ $product->description }}</p>
@endsection