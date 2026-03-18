@extends('layouts.wholesale-customer')

@section('content')
    @php
        $customContent = \App\Models\CustomizableContent::getContentForPage('brand-products');
        $primaryColor = $customContent['primary_color'] ?? '#007bff';
        $secondaryColor = $customContent['secondary_color'] ?? '#6c757d';
    @endphp

    <div class="container-fluid custom-container">
        <!-- Organization Header -->
        <div class="row mb-4 organization-header">
            <div class="col-md-3 text-center">
                @if($brand->image)
                    <img src="{{ asset($brand->logo) }}" alt="{{ $brand->name }} Logo" class="img-fluid custom-logo">
                @else
                    <div class="placeholder-logo">{{ $brand->name[0] }}</div>
                @endif
            </div>
            <div class="col-md-9">
                <h1 class="organization-name">{{ $brand->name }}</h1>
                <p class="organization-description">{{ $customContent['brand_description'] ?? $brand->description ?? 'No description available.' }}</p>
            </div>
        </div>

        <!-- Branded Products -->
        @foreach($brandedProducts as $brand)
            <div class="row mb-4 brand-info">
                <div class="col-12">
                    <h2 class="brand-title">{{ $brand->name }}</h2>
                    <p class="brand-description">{{ $brand->description ?? 'No brand description available.' }}</p>
                </div>
            </div>

            <div class="row">
                @forelse($brand->wholesaleProducts as $product)
                    <div class="col-lg-3 col-md-4 mb-4">
                        @include('wholesale.customer.partials.product-card', ['product' => $product])
                    </div>
                @empty
                    <div class="col-12">
                        <p class="no-products">No products available for this brand.</p>
                    </div>
                @endforelse
            </div>
        @endforeach

        <!-- Unbranded Products -->
        @if($unbrandedProducts->isNotEmpty())
            <div class="row mb-4 brand-info">
                <div class="col-12">
                    <h2 class="brand-title">Other Products</h2>
                    <p class="brand-description">Products without a specific brand.</p>
                </div>
            </div>

            <div class="row">
                @foreach($unbrandedProducts as $product)
                    <div class="col-lg-3 col-md-4 mb-4">
                        @include('wholesale.customer.partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        @endif

        @if($brandedProducts->isEmpty() && $unbrandedProducts->isEmpty())
            <div class="row">
                <div class="col-12">
                    <p class="no-products">No products available for this organization.</p>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('styles')
<style>
    :root {
        --primary-color: {{ $primaryColor }};
        --secondary-color: {{ $secondaryColor }};
        --text-color: {{ $customContent['text_color'] ?? '#333333' }};
        --background-color: {{ $customContent['background_color'] ?? '#ffffff' }};
        --card-bg-color: {{ $customContent['product_card_bg'] ?? '#ffffff' }};
        --button-color: {{ $customContent['button_color'] ?? '#007bff' }};
        --button-text-color: {{ $customContent['button_text_color'] ?? '#ffffff' }};
        --font-family: {{ $customContent['font_family'] ?? "'Arial', sans-serif" }};
        --base-font-size: {{ $customContent['base_font_size'] ?? '16px' }};
    }

    body {
        font-family: var(--font-family);
        font-size: var(--base-font-size);
        color: var(--text-color);
        background-color: var(--background-color);
    }

    .custom-container {
        padding: 2rem;
    }

    .organization-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        padding: 2rem;
        border-radius: 10px;
        color: #ffffff;
    }

    .custom-logo {
        max-width: 200px;
        max-height: 200px;
    }

    .organization-name {
        font-size: 2.5rem;
        font-weight: bold;
    }

    .organization-description {
        font-size: 1.1rem;
    }

    .brand-info {
        background-color: rgba(var(--primary-color), 0.1);
        padding: 1.5rem;
        border-radius: 10px;
    }

    .brand-title {
        font-size: 2rem;
        color: var(--primary-color);
    }

    .brand-description {
        font-size: 1.1rem;
    }

    .custom-card {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        background: linear-gradient(135deg, var(--card-bg-color), #ffffff);
        transition: transform 0.3s ease;
    }

    .custom-card:hover {
        transform: translateY(-5px);
    }

    .card-img-container {
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .custom-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .custom-card-title {
        font-size: 1.3rem;
        color: var(--primary-color);
    }

    .custom-card-text {
        font-size: 1rem;
    }

    .price-tiers {
        margin-bottom: 1rem;
    }

    .price-tier {
        font-weight: bold;
        color: var(--secondary-color);
    }

    .custom-button {
        background-color: var(--button-color);
        color: var(--button-text-color);
        border: none;
        transition: all 0.3s ease;
    }

    .custom-button:hover {
        opacity: 0.9;
        transform: scale(1.05);
    }

    .placeholder-logo, .placeholder-image {
        width: 200px;
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f0f0f0;
        color: #999;
        font-size: 2rem;
        border-radius: 50%;
    }

    .no-products {
        font-size: 1.2rem;
        color: var(--secondary-color);
        text-align: center;
        padding: 2rem;
    }
</style>

@if(isset($customContent['custom_css']))
<style id="custom-css">
    {!! preg_replace('/([^}]+)\s*{/', '$1 {!important', $customContent['custom_css']) !!}
</style>
@endif
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const priceTierSelect = document.getElementById('price-tier');
        const addToCartButtons = document.querySelectorAll('.add-to-cart');

        function updatePriceTierDisplay() {
            const selectedTier = priceTierSelect.value;
            document.querySelectorAll('.price-tier').forEach(el => el.style.display = 'none');
            document.querySelectorAll(`.price-tier-${selectedTier}`).forEach(el => el.style.display = 'block');
        }

        if (priceTierSelect) {
            priceTierSelect.addEventListener('change', updatePriceTierDisplay);
            updatePriceTierDisplay();
        }

        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const selectedTier = priceTierSelect ? priceTierSelect.value : null;
                // Implement your add to cart logic here
                alert(`Product ${productId} added to cart${selectedTier ? ` with price tier ${selectedTier}` : ''}`);
            });
        });
    });

    {!! $customContent['custom_js'] ?? '' !!}
</script>
@endpush