@extends('layouts.app')

@section('content')
<link href="{{ asset('assets/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">

<div class="row wrapper border-bottom white-bg page-heading">
  <div class="col-lg-10">
    <h2>Edit Product</h2>
    <ol class="breadcrumb">
      <li>
        <a href="{{ url('') }}">Home</a>
      </li>
      <li>
        <a href="{{ url('products') }}">Products</a>
      </li>
      <li class="active">
        <strong>Edit</strong>
      </li>
    </ol>
  </div>
  <div class="col-lg-2"></div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
  <div class="row">
    <!-- Edit Form Section -->
    <div class="col-lg-8">
      <div class="custom-edit-card">
        <div class="custom-form-title">
          <h5>Edit Product</h5>
        </div>
        <div class="custom-form-content">
          <form action="{{ url('products/' . $product->id) }}" method="POST" enctype="multipart/form-data" class="custom-form">
            @method('put')
            {{ csrf_field() }}

            <!-- Image Upload Section -->
            <div class="custom-image-upload">
              <label for="fileInput" class="custom-image-label">
                @if(file_exists(public_path('public/uploads/products/' . $product->id . '.jpg')))
                  <img id="previewImage" src="{{ asset('uploads/products/' . $product->id . '.jpg') }}" alt="{{ strip_tags($product->name) }}">
                @else
                  <img id="previewImage" src="{{ asset('herbs/noimage.jpg') }}" alt="{{ strip_tags($product->name) }}">
                @endif
              </label>
              <input type="file" name="file" id="fileInput" class="custom-file-input" style="display: none;">
            </div>

            <!-- Name Field -->
            <div class="custom-form-group">
              <label class="custom-form-label">Name</label>
              <div class="custom-form-control">
                <input type="text" class="custom-input" id="name" name="name" value="{{ old('name', $product->name) }}">
              </div>
            </div>

            <!-- Description Field -->
            <div class="custom-form-group">
              <label class="custom-form-label">Description</label>
              <div class="custom-form-control">
                <input type="text" class="custom-input" id="description" name="description" value="{{ old('description', $product->description) }}">
              </div>
            </div>

            <!-- Category Dropdown -->
            <div class="custom-form-group">
              <label class="custom-form-label">Category</label>
              <div class="custom-form-control">
                <select class="custom-select" id="category_id" name="category_id">
                  <option value="" disabled selected>-- Select Category --</option>
                  @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>
                      {{ $cat->name }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>

            <!-- Price / Discount Tiers Section -->
            @if(isset($discountTiers) && count($discountTiers) > 0)
              <div class="custom-form-group">
                <label class="custom-form-label">Discount Tiers</label>
                <div class="custom-form-control">
                  <select class="custom-select" name="selected_discount_tier" id="selected_discount_tier">
                    <option value="">-- Select Tier --</option>
                    @foreach($discountTiers as $tier)
                      <option value="{{ $tier['name'] }}" 
                        {{ old('selected_discount_tier', $product->discount_tiers['name'] ?? '') == $tier['name'] ? 'selected' : '' }}>
                        {{ $tier['name'] }}
                      </option>
                    @endforeach
                  </select>
                </div>
              </div>
            @else
              <div class="custom-form-group">
                <label class="custom-form-label">Price</label>
                <div class="custom-form-control">
                  <input type="text" class="custom-input" id="price" name="price" value="{{ old('price', $product->price) }}">
                </div>
              </div>
            @endif

            <!-- Cost Field -->
            <div class="custom-form-group">
              <label class="custom-form-label">Cost</label>
              <div class="custom-form-control">
                <input type="text" class="custom-input" id="cost" name="cost" value="{{ old('cost', $product->cost) }}">
              </div>
            </div>

            <!-- Unit of Measure Field -->
            <div class="custom-form-group">
              <label class="custom-form-label">Unit of Measure</label>
              <div class="custom-form-control">
                <input type="text" class="custom-input" id="unitofmeasurename" name="unitofmeasurename" value="{{ old('unitofmeasurename', $product->unitofmeasurename) }}">
              </div>
            </div>

            <!-- Submit Button -->
            <div class="custom-form-group custom-submit">
              <a class="btn btn-white" href="{{ url('products') }}">Cancel</a>
              <button class="btn btn-primary" type="submit">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Custom CSS -->
<style>
  .custom-edit-card {
    border: 1px solid #ddd;
    background: #fff;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
  }
  .custom-form-title h5 {
    margin: 0 0 10px;
    font-size: 1.3em;
    font-weight: bold;
  }
  .custom-form {
    margin-top: 15px;
  }
  .custom-form-group {
    margin-bottom: 12px;
  }
  .custom-form-label {
    font-weight: 600;
    margin-bottom: 5px;
  }
  .custom-form-control {
    margin-bottom: 5px;
  }
  .custom-input, .custom-select {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
  }
  .custom-submit {
    text-align: right;
  }
  .custom-image-upload {
    text-align: center;
    margin-bottom: 15px;
  }
  .custom-image-upload img {
    width: 33%;
    display: block;
    margin: 0 auto;
  }
</style>

@verbatim
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Image preview for file upload
$('#fileInput').on('change', function(e){
  var file = e.target.files[0];
  if(file){
    var reader = new FileReader();
    reader.onload = function(e){
      $('#previewImage').attr('src', e.target.result);
    };
    reader.readAsDataURL(file);
  }
});
</script>
@endverbatim

@endsection
