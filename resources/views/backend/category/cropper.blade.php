@extends('layouts.app')

@section('content')
<link href="{{ asset('assets/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">

<!-- Custom CSS for the uploader with a circular preview -->
<style>
    .upload-pic { 
        height: 200px;
        width: 200px; 
        background: #ccc;
        margin: 10px;
        border-radius: 50%;
        overflow: hidden;
        cursor: pointer;
    }
    .upload-pic img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .upload-pic-new {
        margin: 10px 0;
        cursor: pointer;
    }
</style>

<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Add Category</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ url('') }}">Home</a>
            </li>
            <li>
                <a href="{{ url('categories') }}">Categories</a>
            </li>
            <li class="active">
                <strong>Add New</strong>
            </li>
        </ol>
    </div>
    <div class="col-lg-2"></div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    <form id="categoryForm" action="{{ url('categories') }}" class="form-horizontal" method="POST" enctype="multipart/form-data">
        {{ csrf_field() }}
        <!-- Category Details -->
        <div class="form-group">
            <label class="col-sm-2 control-label">Name</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
        </div>
        <input type="hidden" name="sort" value="0">
        <!-- We removed the cropped_value hidden field since we're not cropping -->

        <!-- Image Upload Area -->
        <div class="form-group">
            <label class="col-sm-2 control-label">Category Image</label>
            <div class="col-sm-10">
                <label title="Upload image file" for="cropper" style="cursor:pointer">
                    <div class="upload-pic img-circle">
                        <!-- Default image -->
                        <img id="image_source" class="img-circle" src="{{ asset('herbs/noimage.jpg') }}" alt="Category Image">
                    </div>
                </label>
                <input type="file" name="file" id="cropper" style="display:none" />
            </div>
        </div>

        <!-- Submit Button -->
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button class="btn btn-primary" type="submit">Save Category</button>
            </div>
        </div>
    </form>
</div>

<!-- Simple JS to update preview image without cropper -->
<script>
document.getElementById('cropper').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (!file) return;
    
    var allowedTypes = ["image/jpeg", "image/png", "image/jpg"];
    if (allowedTypes.indexOf(file.type) < 0) {
        alert("Please select a valid image file (jpeg, png, jpg).");
        return;
    }
    
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('image_source').setAttribute('src', e.target.result);
    };
    reader.readAsDataURL(file);
});
</script>

@endsection
