@extends('layouts.customer')

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
            <a href="{{ route('retail.customer.dashboard') }}" class="btn btn-secondary back-button">
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
                            <i class="fas fa-store"></i>
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
                    @include('retail.customer.partials.product-card', ['product' => $product])
                </div>
            @endforeach
        </div>
    @endif

    <!-- Products by Category Section -->
    @foreach($categories as $category)
        @if($category->retailProducts->isNotEmpty())
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="section-title">{{ $category->name }}</h2>
                    <p>{{ $category->description ?? 'No description available.' }}</p>
                </div>
                @foreach($category->retailProducts as $product)
                    <div class="col-lg-3 col-md-4 mb-4">
                        @include('retail.customer.partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach

    <!-- Uncategorized Products Section -->
    @if($uncategorizedProducts->isNotEmpty())
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="section-title">Other Products</h2>
            </div>
            @foreach($uncategorizedProducts as $product)
                <div class="col-lg-3 col-md-4 mb-4">
                    @include('retail.customer.partials.product-card', ['product' => $product])
                </div>
            @endforeach
        </div>
    @endif

    @if($featuredProducts->isEmpty() && $categories->isEmpty() && $uncategorizedProducts->isEmpty())
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

    /* ... (rest of the styles remain the same) ... */
</style>
@if(isset($customStyles['custom_styles']))
<style id="custom-css">
    {!! $customStyles['custom_styles'] !!}
</style>
@endif
@endpush

@push('scripts')
<script>
    // ... (the script remains the same) ...
</script>
@endpush