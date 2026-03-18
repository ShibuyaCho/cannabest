@extends('layouts.app')

@section('content')
<div class="container">
    <h1>wholesale Marketplace</h1>
    <p>Browse our wholesale partners and their products:</p>

    @foreach($organizations as $organization)
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">{{ $organization->name }}</h5>
                <p class="card-text">Products available: {{ $organization->products_count }}</p>
                <a href="{{ route('login') }}" class="btn btn-primary">Log in to view products</a>
            </div>
        </div>
    @endforeach

    <div class="mt-4">
        <p>Don't have an account? <a href="{{ route('register') }}">Sign up now</a> to start shopping!</p>
    </div>
</div>
@endsection