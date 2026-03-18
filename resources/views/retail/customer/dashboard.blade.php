@extends('layouts.customer')

@section('content')
    <h1 class="mb-4">Nearby Dispensaries</h1>
    <div class="row">
        @foreach($nearbyDispensaries as $dispensary)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $dispensary->name }}</h5>
                        <p class="card-text">{{ $dispensary->description ?? 'No description available' }}</p>
                        <p class="card-text"><small class="text-muted">Products: {{ $dispensary->products->count() }}</small></p>
                        <p class="card-text"><small class="text-muted">Distance: {{ number_format($dispensary->distance, 2) }} miles</small></p>
                        <a href="{{ route('retail.customer.organization-products', $dispensary) }}" class="btn btn-primary">View Products</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection