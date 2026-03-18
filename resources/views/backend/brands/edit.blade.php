@extends('layouts.Wholesale')

@section('content')
<div class="container">
    <h1 class="text-center mb-4">Edit Brand: {{ $brand->name }}</h1>

    <form action="{{ route('wholesale.brands.update', $brand) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="image">Brand Image</label>
            <input type="file" class="form-control-file" id="image" name="image">
        </div>
        <div class="mt-3">
            <img id="image-preview" src="{{ $brand->image ? asset('/' . $brand->image) : '#' }}" alt="Image preview" style="max-width: 200px; {{ $brand->image ? '' : 'display: none;' }}">
        </div>
        <div class="form-group mt-3">
            <label for="name">Brand Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $brand->name }}" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ $brand->description }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Update Brand</button>
    </form>
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
        
        if (file) {
            reader.readAsDataURL(file);
        }
    });
</script>
@endsection