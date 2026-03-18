@extends('layouts.Wholesale')

@section('content')
<div class="container">
    <h1 class="text-center mb-4">Manage Brands</h1>

    @include('backend.partials.wholesaleNotification')

    <div class="row" id="brandCards">
        <!-- Create New Brand Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Create New Brand</h5>
                    <form action="{{ route('wholesale.brands.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="name">Brand Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="image">Brand Image</label>
                            <input type="file" class="form-control-file @error('image') is-invalid @enderror" id="image" name="image">
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mt-3">
                            <img id="image-preview" src="#" alt="Image preview" style="max-width: 100%; display: none;">
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Create Brand</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Existing Brands -->
        @foreach($brands->sortBy('name') as $brand)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="{{ asset('/' . $brand->image) }}" class="card-img-top" alt="{{ $brand->name }}">
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
</div>

<script>
    document.getElementById('image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('image-preview');
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
    });
</script>
@endsection