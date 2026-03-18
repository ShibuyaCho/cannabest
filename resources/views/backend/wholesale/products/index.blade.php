@extends('layouts.Wholesale')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .category-buttons {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .category-button {
        margin: 5px;
        padding: 10px 20px;
        font-size: 16px;
        border: none;
        border-radius: 20px;
        background-color: #f8f9fa;
        color: #333;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .category-button.active {
        background-color: #007bff;
        color: white;
    }
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    .product-card {
        border: 1px solid #e7eaec;
        border-radius: 8px;
        overflow: hidden;
        transition: box-shadow 0.3s ease;
        text-decoration: none; /* Added to remove underline from links */
        color: inherit; /* Added to keep text color consistent */
        display: block;
    }
    .product-card:hover {
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .product-image-container {
        width: 200px;
        height: 200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 50%;
        background-color: #f8f9fa;
    }
    .product-image {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    .product-details {
        padding: 15px;
    }
    .product-name {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    .product-price {
        font-size: 16px;
        color:rgb(0, 0, 0);
        font-weight: bold;
    }
    .product-quantity {
        font-size: 18px;
        color: #333;
        margin-top: 10px;
    }
    .product-quantity span {
        font-weight: bold;
        font-size: 24px;
        color: #007bff;
    }
    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
    }
</style>

<div class="container-fluid">
    <h1 class="text-center mb-4">Wholesale Products</h1>
    
    <div class="category-buttons">
        <button class="category-button active" data-category="all">All</button>
        @foreach($categories as $category)
            <button class="category-button" data-category="{{ $category->id }}">{{ $category->name }}</button>
        @endforeach
    </div>

    <div class="product-grid">
        @forelse ($products as $product)
            <a href="{{ route('wholesale.products.edit', $product->id) }}" class="product-card" data-category="{{ $product->category_id }}">
                <div class="product-image-container">
                    @if($product->image)
                        <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="product-image">
                    @else
                        <img src="{{ asset('images/placeholder.jpg') }}" alt="Placeholder" class="product-image">
                    @endif
                </div>
                <div class="product-details">
                    <h5 class="product-name">{{ $product->name }}</h5>
                    <p class="product-price">${{ number_format($product->wholesaleInventories->first()->price ?? 0, 2) }}</p>
                    <p class="product-quantity"><strong>Quantity:</strong> {{ number_format($product->total_quantity, 1) }}</p>
                </div>
            </a>
        @empty
            <div class="col-12 text-center">
                <p>No products found.</p>
            </div>
        @endforelse
    </div>
    
   <div class="action-buttons">
    @if(auth()->user()->organization && auth()->user()->organization->license_number)
        <a href="{{ route('wholesale.products.create') }}" class="btn btn-primary">Import Products</a>
    @endif
<a href="{{ route('admin.wholesale.inventories.create') }}" class="btn btn-success">Add Inventory</a>
</div>
    
    <div class="d-flex justify-content-center mt-4">
        {{ $products->links() }}
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.category-button').click(function() {
        $('.category-button').removeClass('active');
        $(this).addClass('active');
        
        var category = $(this).data('category');
        if (category === 'all') {
            $('.product-card').show();
        } else {
            $('.product-card').hide();
            $('.product-card[data-category="' + category + '"]').show();
        }
    });
});
</script>
@endsection