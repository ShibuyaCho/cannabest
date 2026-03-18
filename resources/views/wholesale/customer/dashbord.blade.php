@extends('layouts.wholesale-customer')

@section('content')
    <h1 class="mb-4">Wholesale Organizations</h1>
    <div class="row">
        @foreach($organizations as $organization)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $organization->name }}</h5>
                        <p class="card-text">{{ $organization->description ?? 'No description available' }}</p>
                        <p class="card-text"><small class="text-muted">Brands: {{ $organization->brands->count() }}</small></p>
                        <a href="{{ route('wholesale.customer.organization-brands', $organization) }}" class="btn btn-primary">View Brands</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection