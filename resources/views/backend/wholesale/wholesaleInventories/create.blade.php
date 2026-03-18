@extends('layouts.Wholesale')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .card {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transition: 0.3s;
        margin-bottom: 20px;
        background-color:rgb(224, 224, 224);
        border: 1px solid #e9ecef;
        width: 100%;
        position: relative;
    }
    .card:hover {
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }
    .card-body {
        padding: 2.5rem 2rem 2rem;
    }
    .form-group {
        margin-bottom: 20px;
    }
    #productContainer {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }
    .remove-product {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 10;
    }
    #addProductCard {
         display: flex;
    align-items: center;
    justify-content: center;
        position: absolute;
        top: 10px;
        right: 10px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        font-size: 20px;
        line-height: 26px;
        padding: 0;
        z-index: 1000;
        vertical-align: middle; 
        text-align: center;
    }
    .content-card {
        position: relative;
        background-color:rgb(119, 119, 119);
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-20">
            <div class="content-card">
                <h1 class="text-center mb-4">Add New Wholesale Products</h1>
                <button type="button" id="addProductCard" class="btn btn-secondary">+</button>

                <form id="productForm" action="{{ route('admin.wholesale.wholesaleInventories.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div id="productContainer" class="row justify-content-center">
                        <!-- Product cards will be added here -->
                    </div>
                    <div class="text-center mb-4">
                        <button type="submit" class="btn btn-primary">Add Products and Inventory</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Product Card Template -->
<template id="productCardTemplate">
    <div class="col-md-6 col-lg-5 mb-4 product-card">
        <div class="card">
            <button type="button" class="btn btn-danger btn-sm remove-product">&times;</button>
            <div class="card-body">
                <div class="form-group">
                    <label for="products[].name">Product Name</label>
                    <input type="text" name="products[][name]" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="products[].display_name">Display Name</label>
                    <input type="text" name="products[][display_name]" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="products[].description">Description</label>
                    <textarea name="products[][description]" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label for="products[].category_id">Category</label>
                    <select name="products[][category_id]" class="form-control" required>
                        <option value="">Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="products[].sku">SKU</label>
                    <input type="text" name="products[][sku]" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="products[].quantity">Quantity</label>
                    <input type="number" name="products[][quantity]" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="products[].price">Price</label>
                    <input type="number" step="0.01" name="products[][price]" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="products[].weight">Weight (grams)</label>
                    <input type="number" step="0.01" name="products[][weight]" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="products[].UnitThcContent">THC Content (%)</label>
                    <input type="number" step="0.01" name="products[][UnitThcContent]" class="form-control">
                </div>
                <div class="form-group">
                    <label for="products[].UnitCbdContent">CBD Content (%)</label>
                    <input type="number" step="0.01" name="products[][UnitCbdContent]" class="form-control">
                </div>
                <div class="form-group">
                    <label for="products[].status">Status</label>
                    <select name="products[][status]" class="form-control" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="products[].image">Product Image</label>
                    <input type="file" name="products[][image]" class="form-control-file">
                </div>
                <div class="form-group">
                    <label for="products[].remove_background">Remove Background</label>
                    <input type="checkbox" name="products[][remove_background]" value="1">
                </div>
            </div>
        </div>
    </div>
</template>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function addProductCard() {
        var template = document.getElementById('productCardTemplate');
        var productCard = template.content.cloneNode(true);
        var index = $('.product-card').length;
        $(productCard).find('input, select, textarea').each(function() {
            var name = $(this).attr('name');
            $(this).attr('name', name.replace('[]', '[' + index + ']'));
        });
        document.getElementById('productContainer').appendChild(productCard);
    }

    // Add initial product card
    addProductCard();

    // Add product card button
    $('#addProductCard').on('click', function() {
        addProductCard();
    });

    // Remove product card
    $(document).on('click', '.remove-product', function() {
        $(this).closest('.product-card').remove();
    });

    $('#productForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                alert('Products and inventory added successfully!');
                window.location.href = '{{ route("wholesale.products.index") }}';
            },
            error: function(xhr) {
                alert('Error: ' + xhr.responseText);
            }
        });
    });
});
</script>
@endsection