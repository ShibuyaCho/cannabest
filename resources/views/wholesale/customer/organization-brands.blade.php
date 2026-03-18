@extends('layouts.wholesale-customer')

@section('content')
@php
    $primaryColor = $customStyles['primary_color'] ?? '#007bff';
    $secondaryColor = $customStyles['secondary_color'] ?? '#6c757d';
    $textColor = $customStyles['text_color'] ?? '#333333';
    $backgroundColor = $customStyles['background_color'] ?? '#ffffff';
    $cardBgColor = $customStyles['product_card_bg'] ?? '#ffffff';
    $buttonColor = $customStyles['button_color'] ?? '#007bff';
    $buttonTextColor = $customStyles['button_text_color'] ?? '#ffffff';
    $fontFamily = $customStyles['font_family'] ?? "'Arial', sans-serif";
    $baseFontSize = $customStyles['base_font_size'] ?? '16px';
@endphp

<div class="container-fluid custom-container">
    <!-- Back to Organizations Button -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('wholesale.customer.dashboard') }}" class="btn btn-secondary back-button">
                <i class="fas fa-arrow-left"></i> Back to Organizations
            </a>
        </div>
    </div>


<!-- Organization Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="organization-header">
            <div class="logo-container">
                @if($organization->image)
                    <img src="{{ asset($organization->image) }}" alt="{{ $organization->name }} Logo" class="custom-logo">
                @else
                    <div class="placeholder-logo">
                        <i class="fas fa-building"></i>
                    </div>
                @endif
            </div>
            <div class="organization-info">
                <h1 class="organization-name">{{ $organization->name }}</h1>
                <p class="organization-description">{{ $organization->description ?? 'No description available.' }}</p>
            </div>
        </div>
    </div>
</div>

    <!-- Featured Products Section -->
    @if($featuredProducts->isNotEmpty())
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="section-title">Featured Products</h2>
            </div>
            @foreach($featuredProducts as $product)
                <div class="col-lg-3 col-md-4 mb-4">
                    @include('wholesale.customer.partials.product-card', [
                        'product' => $product,
                        'price' => $product->wholesaleInventories->first()->price ?? null
                    ])
                </div>
            @endforeach
        </div>
    @endif

    <!-- Products by Brand Section -->
    @foreach($brands as $brand)
        @if($brand->wholesaleProducts->isNotEmpty())
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="section-title">{{ $brand->name }}</h2>
                    <p>{{ $brand->description ?? 'No description available.' }}</p>
                </div>
                @foreach($brand->wholesaleProducts as $product)
                    <div class="col-lg-3 col-md-4 mb-4">
                        @include('wholesale.customer.partials.product-card', [
                            'product' => $product,
                            'price' => $product->wholesaleInventories->first()->price ?? null
                        ])
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach

    <!-- Unbranded Products Section -->
    @if($unbrandedProducts->isNotEmpty())
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="section-title">Other Products</h2>
               
            </div>
            @foreach($unbrandedProducts as $product)
                <div class="col-lg-3 col-md-4 mb-4">
                    @include('wholesale.customer.partials.product-card', [
                        'product' => $product,
                        'price' => $product->wholesaleInventories->first()->price ?? null
                    ])
                </div>
            @endforeach
        </div>
    @endif

    @if($featuredProducts->isEmpty() && $brands->isEmpty() && $unbrandedProducts->isEmpty())
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
        --text-color: {{ $textColor }};
        --background-color: {{ $backgroundColor }};
        --card-bg-color: {{ $cardBgColor }};
        --button-color: {{ $buttonColor }};
        --button-text-color: {{ $buttonTextColor }};
        --font-family: {{ $fontFamily }};
        --base-font-size: {{ $baseFontSize }};
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
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
}

.logo-container {
    flex: 0 0 150px;
    height: 150px;
    margin-right: 2rem;
}

.custom-logo {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background-color: white;
    border-radius: 50%;
    padding: 10px;
}

.placeholder-logo {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border-radius: 50%;
    font-size: 4rem;
    color: var(--secondary-color);
}

.organization-info {
    flex: 1;
}

.organization-name {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.organization-description {
    font-size: 1.1rem;
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .organization-header {
        flex-direction: column;
        text-align: center;
    }

    .logo-container {
        margin-right: 0;
        margin-bottom: 1rem;
    }
}

    .card-img-container {
        width: 200px;
        height: 200px;
        margin: 1rem auto;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 50%;
        background-color: white;
    }

   
    .custom-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .custom-logo {
        object-fit: contain;
        padding: 10px;
    }
 
    .organization-name {
        font-size: 2.5rem;
        font-weight: bold;
        color: #ffffff;
        margin-bottom: 0.5rem;
    }

    .organization-description {
        font-size: 1.1rem;
        color: #ffffff;
    }

    .section-title {
        font-size: 2rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
        border-bottom: 2px solid var(--secondary-color);
        padding-bottom: 0.5rem;
    }

    .back-button {
        background-color: var(--secondary-color);
        color: var(--button-text-color);
        transition: all 0.3s ease;
        margin-bottom: 1rem;
    }

    .back-button:hover {
        opacity: 0.9;
        transform: scale(1.05);
    }

    .no-products {
        font-size: 1.2rem;
        color: var(--secondary-color);
        text-align: center;
        padding: 2rem;
        background-color: rgba(var(--primary-color), 0.1);
        border-radius: 10px;
    }

    .custom-card {
        background-color: var(--card-bg-color);
        border: 1px solid var(--secondary-color);
        border-radius: 10px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }

    .custom-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .custom-card .card-body {
        display: flex;
        flex-direction: column;
    }

    .custom-card-title {
        font-size: 1.2rem;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .custom-card-text {
        font-size: 1rem;
        color: var(--text-color);
        flex-grow: 1;
    }

    .price-tier {
        font-weight: bold;
        color: var(--secondary-color);
        margin-bottom: 0.5rem;
    }

    .custom-button {
        background-color: var(--button-color);
        color: var(--button-text-color);
        border: none;
        transition: all 0.3s ease;
        width: 100%;
    }

    .custom-button:hover {
        opacity: 0.9;
        transform: scale(1.05);
    }

 
</style>
@if(isset($customStyles['custom_styles']))
<style id="custom-css">
    {!! $customStyles['custom_styles'] !!}
</style>
@endif
@endpush


    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOMContentLoaded event fired in iframe');
        
        function sendMessageToParent(message) {
            console.log('Attempting to send message to parent:', message);
            window.parent.postMessage(message, '*');
        }

        function logClick(e) {
            console.log('Element clicked:', e.target);
            e.preventDefault();
            e.stopPropagation();

            const target = e.target;
            const computedStyle = window.getComputedStyle(target);

            sendMessageToParent({
                type: 'elementClicked',
                tagName: target.tagName,
                id: target.id,
                classes: target.className,
                textContent: target.textContent,
                styles: {
                    color: computedStyle.color,
                    backgroundColor: computedStyle.backgroundColor,
                    fontSize: computedStyle.fontSize,
                    fontFamily: computedStyle.fontFamily,
                }
            });
        }

        document.addEventListener('click', logClick, true);

        window.addEventListener('message', function(event) {
            console.log('Message received in iframe:', event.data);
            
            if (event.data.type === 'applyStyles') {
                const styles = event.data.styles;
                Object.keys(styles).forEach(key => {
                    document.documentElement.style.setProperty(`--${key}`, styles[key]);
                });
            }
        });

        sendMessageToParent({ type: 'iframeLoaded' });
    });
    </script>
    @endpush
