<div class="card h-100 custom-card">
    <div class="card-img-container">
        @if($product->image)
            <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="custom-img">
        @else
            <div class="placeholder-image">No Image</div>
        @endif
    </div>
    <div class="card-body d-flex flex-column">
        <h5 class="card-title custom-card-title">{{ $product->name }}</h5>
        <p class="card-text custom-card-text flex-grow-1">{{ Str::limit($product->description, 100) }}</p>
        @if($price)
            <p class="price-tier">Price: ${{ number_format($price, 2) }}</p>
        @else
            <p class="price-tier">Price not available</p>
        @endif
        <button class="btn custom-button add-to-cart w-100" data-product-id="{{ $product->id }}" data-product-price="{{ $price ?? 0 }}">
            <i class="fas fa-cart-plus"></i> Add to Cart
        </button>
    </div>
</div>