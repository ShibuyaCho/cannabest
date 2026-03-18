@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row center-card">
        <div class="col-md-6 col-md-offset-3">
            <div class="category-card">
                <form action="{{ url('categories') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <!-- Editable image: click to select a new image -->
                    <div class="category-img">
                        <label for="fileInput" style="cursor:pointer;">
                            <img id="previewImage" src="{{ asset('herbs/noimage.jpg') }}" alt="Category Image">
                        </label>
                        <input type="file" name="file" id="fileInput" style="display:none;">
                    </div>
                    
                    <!-- Editable category name -->
                    <div class="form-group">
                        <label for="name" class="category-name">Category Name</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Enter category name" required>
                    </div>
                    
                    <!-- New: Sales Limit Category Selection -->
                    <div class="form-group">
                        <label for="sales_limit_category" class="control-label">Sales Limit Category</label>
                        <select name="sales_limit_category" id="sales_limit_category" class="form-control">
                            <option value="">None</option>
                            <option value="Flower/Joints">Flower/Joints</option>
                            <option value="edibles">Edibles</option>
                            <option value="Extracts/Concentrates">Extracts/Concentrates</option>
                            <option value="inhalable cannabinoid">Inhalable Cannabinoid</option>
                            <option value="Clones">Clones</option>
                            <option value="Tinctures">Tinctures</option>
                            <option value="Seeds">Marijuana Seeds</option>
                        </select>
                        <p class="help-block">Choose which sales limit bar this category will affect (if any).</p>
                    </div>
                    <!-- End Sales Limit Category Selection -->
                    
                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Save Category</button>
                        <a href="{{ url('categories') }}" class="btn btn-default">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
    .center-card {
        min-height: 80vh;
        display: flex;
        align-items: left;
        justify-content: left;
    }
    .category-card {
        border: 1px solid #ddd;
        padding: 15px;
        text-align: center;
        background: #fff;
        border-radius: 8px;
        transition: box-shadow 0.3s ease;
    }
    .category-card:hover {
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }
    .category-img {
        width: 200px;
        height: 200px;
        margin: 0 auto 15px;
        overflow: hidden;
        border-radius: 50%;
    }
    .category-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .category-name {
        margin-bottom: 15px;
        font-weight: bold;
        font-size: 1.2em;
    }
    .form-control {
        max-width: 100%;
    }
</style>

<!-- JavaScript: Update preview image immediately when a new file is selected -->
<script>
document.getElementById('fileInput').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if(file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endsection
