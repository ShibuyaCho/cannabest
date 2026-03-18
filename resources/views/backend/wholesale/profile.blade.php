@extends('layouts.Wholesale')

@section('content')
<div class="container">
    <h1 class="text-center mb-4">{{ $user->name }}'s Wholesale Profile</h1>

    <div class="mb-5">
        <h2 class="mb-3">Account Details</h2>
        <ul class="list-group">
            <li class="list-group-item"><strong>Name:</strong> {{ $user->name }}</li>
            <li class="list-group-item"><strong>Email:</strong> {{ $user->email }}</li>
            <li class="list-group-item"><strong>License Number:</strong> {{ $organization->license_number ?? 'Not set' }}</li>
            <li class="list-group-item"><strong>Organization:</strong> {{ $organization->name }}</li>
        </ul>
    </div>

    <h2 class="mb-3">Your Brands</h2>
    @if($brands->isNotEmpty())
        <div class="row">
            @foreach($brands as $brand)
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        @if($brand->image)
                            <img src="{{ asset($brand->image) }}" class="card-img-top" alt="{{ $brand->name }}">
                        @endif
                        <div class="card-body">
                            <h5 class="card-title">{{ $brand->name }}</h5>
                            <p class="card-text">{{ Str::limit($brand->description, 100) }}</p>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('wholesale.brands.edit', $brand) }}" class="btn btn-sm btn-primary">Edit</a>
                            <form action="{{ route('wholesale.brands.destroy', $brand) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this brand?')">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info">
            <p>You haven't added any brands yet. Start by adding your first brand!</p>
        </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('wholesale.brands.create') }}" class="btn btn-primary">Add New Brand</a>
    </div>
</div>
@endsection