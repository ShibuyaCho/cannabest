@extends('layouts.app')
@section('content')
<div class="container">
    <div class="row center-card">
        <div class="col-md-6 col-md-offset-3">
            <div class="category-card">
                <form action="{{ isset($category)
                ? route('categories.update', $category->id)
                : route('categories.store') }}"
  method="POST"
  enctype="multipart/form-data">
                    @csrf
                    @if(isset($category))
                        @method('put')
                    @endif

                    <!-- Editable image -->
                    <div class="category-img">
                        <label for="fileInput" style="cursor:pointer;">
                            @if(isset($category) && file_exists(public_path('uploads/category/' . $category->id . '.jpg')))
                                <img id="previewImage" src="{{ asset('uploads/category/' . $category->id . '.jpg') }}" alt="{{ $category->name }}">
                            @else
                                <img id="previewImage" src="{{ asset('herbs/noimage.jpg') }}" alt="Category Image">
                            @endif
                        </label>
                        <input type="file" name="file" id="fileInput" style="display:none;">
                    </div>

                    <!-- Category Name -->
                    <div class="form-group">
                        <label for="name" class="category-name">Category Name</label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="{{ isset($category) ? $category->name : old('name') }}" 
                               placeholder="Enter category name" required>
                    </div>

                    <!-- Sales Limit Category (New Field) -->
                    <div class="form-group">
                        <label for="sales_limit_category" class="control-label">Sales Limit Category</label>
                        <select name="sales_limit_category" id="sales_limit_category" class="form-control">
                            <option value="">None</option>
                            <option value="Flower/Joints" {{ (isset($category) && $category->sales_limit_category=="Flower/Joints") ? 'selected' : '' }}>Flower/Joints</option>
                            <option value="edibles" {{ (isset($category) && $category->sales_limit_category=="edibles") ? 'selected' : '' }}>Edibles</option>
                            <option value="Extracts/Concentrates" {{ (isset($category) && $category->sales_limit_category=="Extracts/Concentrates") ? 'selected' : '' }}>Extracts/Concentrates</option>
                            <option value="inhalable cannabinoid" {{ (isset($category) && $category->sales_limit_category=="inhalable cannabinoid") ? 'selected' : '' }}>Inhalable Cannabinoid</option>
                            <option value="Clones" {{ (isset($category) && $category->sales_limit_category=="Clones") ? 'selected' : '' }}>Clones</option>
                            <option value="Tinctures" {{ (isset($category) && $category->sales_limit_category=="Tinctures") ? 'selected' : '' }}>Tinctures</option>
                            <option value="Seeds" {{ (isset($category) && $category->sales_limit_category=="Seeds") ? 'selected' : '' }}>Seeds</option>
                        </select>
                    </div>
                    <div class="hr-line-dashed"></div>
    <!-- Taxable? -->
    <div class="form-group form-check text-left">
        <input 
            type="checkbox" 
            class="form-check-input" 
            id="taxable" 
            name="taxable" 
            value="1"
            {{ old('taxable', isset($category) ? $category->taxable : true) ? 'checked' : '' }}
        >
        <label class="form-check-label" for="taxable">
            Taxable
        </label>
    </div>
    <div class="hr-line-dashed"></div>
                    <!-- Submit Button -->
                  <input type="submit"
         value="{{ isset($category) ? 'Save Changes' : 'Save Category' }}"
         class="btn btn-primary">
  <a href="{{ route('categories.index') }}" class="btn btn-default">Cancel</a>
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
</style>

<!-- JavaScript: Update preview image when a file is selected -->
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