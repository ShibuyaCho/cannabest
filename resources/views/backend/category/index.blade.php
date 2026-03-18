@extends('layouts.app')

@section('content')
<link href="{{ asset('assets/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">

<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>@lang('common.categories')</h2>
        <ol class="breadcrumb">
          
        </ol>
    </div>
    <div class="col-lg-2 text-right">
        <a href="{{ url('categories/create') }}" class="btn btn-primary btn-lg">
            @lang('common.add_new')
        </a>
    </div>
</div>

<!-- Custom CSS for grid, circular images and delete button -->
<style>
    .category-card {
        border: 1px solid #ddd;
        margin-bottom: 15px;
        padding: 10px;
        text-align: center;
        cursor: pointer;
        transition: box-shadow 0.3s ease;
        position: relative;
    }
    .category-card:hover {
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }
    .category-img {
        width: 200px;
        height: 200px;
        margin: 0 auto;
        overflow: hidden;
        border-radius: 50%;
    }
    .category-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .category-name {
        margin-top: 10px;
        font-weight: bold;
        font-size: 1.2em;
    }
    .delete-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        color: #fff;
        background: red;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        text-align: center;
        line-height: 24px;
        font-weight: bold;
        cursor: pointer;
        z-index: 10;
    }
</style>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        @forelse($categories as $category)
            <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                <div class="category-card">
                    <!-- Delete Button -->
                    <span class="delete-btn" onclick="event.stopPropagation(); if(confirm('Are you sure you want to delete this category?')) { document.getElementById('delete-form-{{ $category->id }}').submit(); }">&times;</span>
                    
                    <!-- Image and edit link -->
                    <a href="{{ url('categories/'.$category->id.'/edit') }}">
                        <div class="category-img">
                            @if(file_exists(public_path('uploads/category/' . $category->id . '.jpg')))
                                <img src="{{ asset('uploads/category/' . $category->id . '.jpg') }}" alt="{{ $category->name }}">
                            @else
                                <img src="{{ asset('herbs/noimage.jpg') }}" alt="{{ $category->name }}">
                            @endif
                        </div>
                    </a>
                    
                    <div class="category-name">
                        {{ $category->name }}
                    </div>
                    
                    <!-- Hidden delete form -->
                    <form id="delete-form-{{ $category->id }}" action="{{ url('categories/' . $category->id) }}" method="POST" style="display: none;">
                        @csrf
                        @method('delete')
                    </form>
                </div>
            </div>
        @empty
            <div class="col-xs-12">
                <p>@lang('common.no_record_found')</p>
            </div>
        @endforelse
    </div>
    <div class="row">
        <div class="col-xs-12 text-center">
            {!! $categories->render() !!}
        </div>
    </div>
</div>
@endsection
