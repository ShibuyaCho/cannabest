@extends('layouts.app')

@section('content')
<link href="{{ asset('assets/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet">

<!-- Page Heading -->
<div class="row wrapper border-bottom white-bg page-heading">
   
    <div class="col-lg-12 text-right">
        <a href="{{ url('products/create') }}" class="btn btn-primary btn-lg">
            @lang('common.add_new')
        </a>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        @forelse ($products as $product)
            <!-- Full-width card for each product -->
            <div class="col-xs-12 product-card" id="product-card-{{ $product->id }}">
                <div class="custom-card card-row">
                    <!-- Delete button positioned in the top right corner -->
                    <button type="button" class="btn btn-danger btn-xs delete-product" 
                        data-id="{{ $product->id }}" 
                        data-url="{{ route('products.destroy', $product->id) }}">
                        x
                    </button>
                    
                    <!-- Clickable area for editing -->
                    <a href="{{ url('products/'.$product->id.'/edit') }}" class="card-link">
                        <div class="card-content">
                            <div class="custom-img-container">
                                @if(file_exists(public_path('public/uploads/products/' . $product->id . '.jpg')))
                                    <img src="{{ asset('uploads/products/' . $product->id . '.jpg') }}" alt="{{ strip_tags($product->name) }}">
                                @else
                                    <img src="{{ asset('herbs/noimage.jpg') }}" alt="{{ strip_tags($product->name) }}">
                                @endif
                            </div>
                            <div class="custom-text">
                                <!-- Product Name in Larger Font -->
                                <div class="custom-name">{!! parseEmojis($product->name) !!}</div>
                                
                                <!-- Details: you can add extra columns or fields if needed -->
                                <div class="custom-details row">
                                    <div class="col-xs-6">
                                        <div class="custom-field">
                                            Price: ${{ $product->original_price ? $product->original_price : (isset($product->discount_tiers['name']) ? $product->discount_tiers['name'] : 'N/A') }}
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <!-- Optionally add more product details here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Hidden delete form for fallback (if not using AJAX) -->
                    <form id="delete-form-{{ $product->id }}" action="{{ url('products/' . $product->id) }}" method="POST" style="display: none;">
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
</div>

<style>
    /* Full-width card container */
    .product-card {
        width: 100%;
        margin-bottom: 16px;
    }
    /* Card styled as a horizontal row with two sections: content and delete button */
    .custom-card.card-row {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #ccc;
        background-color: #fdfdfd;
        border-radius: 4px;
        padding: 12px;
        transition: box-shadow 0.3s;
    }
    .custom-card.card-row:hover {
        box-shadow: 0 0 8px rgba(0, 0, 0, 0.3);
    }
    /* Remove default anchor styling */
    .card-link {
        text-decoration: none;
        color: inherit;
        flex: 1;
    }
    /* Container for clickable content */
    .card-content {
        display: flex;
        align-items: center;
    }
    /* Image container: fixed size for thumbnail */
    .custom-img-container {
        flex: 0 0 80px;
        height: 80px;
        overflow: hidden;
        border-radius: 50%;
        margin-right: 16px;
    }
    .custom-img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    /* Text container for product details */
    .custom-text {
        flex: 1;
        overflow-wrap: break-word;
    }
    /* Increase the font size for readability */
    .custom-name {
        font-weight: 700;
        font-size: 1.8em;
        margin-bottom: 8px;
    }
    .custom-details {
        display: flex;
        flex-wrap: wrap;
    }
    .custom-details > div {
        flex: 1;
        min-width: 140px;
    }
    .custom-field {
        font-size: 1.2em;
        color: #555;
        margin-bottom: 4px;
        overflow-wrap: break-word;
    }
    /* Delete button styling: positioned as a small red x in the top right corner */
    .delete-product {
        position: absolute;
        top: 8px;
        right: 8px;
        border: none;
        background: transparent;
        font-size: 1.2em;
        font-weight: bold;
        color: #d9534f;
        cursor: pointer;
    }
    .delete-product:hover {
        color: #a94442;
    }
</style>

<script>
$(document).ready(function() {
    $('.delete-product').on('click', function(e) {
        // Prevent the click from triggering the edit action via the surrounding anchor
        e.stopPropagation();
        e.preventDefault();

        var btn = $(this);
        var productId = btn.data('id');
        var deleteUrl = btn.data('url');
        if(confirm('Are you sure you want to delete this product?')) {
            $.ajax({
                url: deleteUrl,
                type: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // Remove the card from the DOM on success
                    $('#product-card-' + productId).fadeOut(function() {
                        $(this).remove();
                    });
                },
                error: function(xhr) {
                    alert('Deletion failed. Please try again.');
                }
            });
        }
    });
});
</script>
@endsection
