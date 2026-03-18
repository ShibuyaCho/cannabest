@extends('layouts.Wholesale')

@section('content')
<div class="container">
    <h1 class="mb-4">Brands</h1>
    
    <a href="{{ route('wholesale.brands.create') }}" class="btn btn-primary mb-4">Create New Brand</a>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($brands && $brands->isNotEmpty())
        <div class="row">
            @foreach($brands as $brand)
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        @if($brand->image)
                            <img src="{{ asset($brand->image) }}" class="card-img-top" alt="{{ $brand->name }}">
                        @else
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <span class="text-muted">No Image</span>
                            </div>
                        @endif
                        <div class="card-body">
                            <h5 class="card-title">{{ $brand->name }}</h5>
                            <p class="card-text">{{ Str::limit($brand->description, 100) }}</p>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('wholesale.brands.edit', $brand->id) }}" class="btn btn-sm btn-primary">Edit</a>
                            <form action="{{ route('wholesale.brands.destroy', $brand->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this brand?')">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $brands->links() }}
    @else
        <div class="alert alert-info">
            No brands found. Start by creating a new brand!
        </div>
    @endif
</div>
@endsection