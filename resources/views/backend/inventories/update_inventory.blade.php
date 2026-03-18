@extends('layouts.app')

@section('content')
<!-- Include DataTables CSS if needed (or any other CSS assets) -->
<link href="{{ asset('assets/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet">

<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Inventories</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ url('') }}">@lang('common.home')</a>
            </li>
            <li class="active">
                <strong>Update Inventory</strong>
            </li>
        </ol>
    </div>
    <div class="col-lg-2 text-right">
        <!-- Link to your import page where products get imported -->
        <a href="{{ url('products/create') }}" class="btn btn-primary btn-lg">
            @lang('common.import_products')
        </a>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    <!-- The form will send the adjusted quantities to your adjust_warehouse_quantity route -->
    <form action="{{ url('adjust_warehouse_quantity') }}" method="POST">
        {{ csrf_field() }}
        <div class="row">
            @if(!empty($products))
                @forelse($products as $product)
                    <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                        <div class="custom-card">
                            <div class="custom-img-container">
                                <!-- Link to product edit if desired -->
                                <a href="{{ url('products/'.$product->id.'/edit') }}">
                                    <div class="custom-img">
                                        @if(file_exists(public_path('uploads/products/' . $product->id . '.jpg')))
                                            <img src="{{ asset('uploads/products/' . $product->id . '.jpg') }}" alt="{{ strip_tags($product->name) }}">
                                        @else
                                            <img src="{{ asset('herbs/noimage.jpg') }}" alt="{{ strip_tags($product->name) }}">
                                        @endif
                                    </div>
                                </a>
                            </div>
                            <div class="custom-text">
                                <div class="custom-name">{{ $product->name }}</div>
                                <div class="custom-price">
                                    Price: {{ $product->original_price ? $product->original_price : 'N/A' }}
                                </div>
                                <div class="custom-price">
                                    Package ID: {{ $product->package_id ? $product->package_id : 'N/A' }}
                                </div>
                                <div class="custom-price">
                                    ASPD: 
                                    <!-- Placeholder for future functionality; you can replace this with a proper input or display -->
                                    <input type="text" name="aspd[]" class="form-control" placeholder="ASPD" value="">
                                </div>
                                <div class="custom-price">
                                    Store Qty: {{ $product->quantity >= 0 ? $product->quantity : 0 }}
                                </div>
                                <div class="custom-price">
                                    Warehouse Qty: {{ $product->warehouse >= 0 ? $product->warehouse : 0 }}
                                </div>
                            </div>
                            <!-- Hidden input to pass the product id -->
                            <input type="hidden" name="product_id[]" value="{{ $product->id }}">
                            <div class="form-group">
                                <select name="type[]" class="form-control">
                                    <option value="add">Add</option>
                                    <option value="sub">Subtract</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <!-- The data-max attribute can be used to limit input, for example using the store quantity -->
                                <input type="number" name="quantity[]" data-max="{{ $product->quantity }}" value="0" class="form-control changeqty" placeholder="Adjust Quantity">
                            </div>
                            <div class="form-group">
                                <input type="text" name="comments[]" value="" class="form-control" placeholder="Comments">
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-xs-12">
                        <p>@lang('common.no_record_found')</p>
                    </div>
                @endforelse
            @endif
        </div>
        <div class="row">
            <div class="col-xs-12 text-center">
                <input type="submit" value="Save" class="btn btn-primary">
            </div>
        </div>
    </form>
</div>

<!-- Include SweetAlert JS -->
<script src="{{ asset('assets/js/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script>
// Validate the input quantity to ensure it does not exceed available quantity in the store
document.querySelectorAll('.changeqty').forEach(function(input) {
    input.addEventListener('keyup', function() {
        if (Number(this.value) > Number(this.dataset.max)) {
            swal("Oops", "In Storeroom there is only " + this.dataset.max + " items", "error");
            this.value = "";
        }
    });
    input.addEventListener('change', function() {
        if (Number(this.value) > Number(this.dataset.max)) {
            swal("Oops", "In Storeroom there is only " + this.dataset.max + " items", "error");
            this.value = "";
        }
    });
});
</script>

<!-- Custom CSS styling to match the digital warehouse/product cards look -->
<style>
    /* Card Container */
    .custom-card {
        border: 1px solid #ccc;
        padding: 8px;
        background-color: #fdfdfd;
        border-radius: 4px;
        margin-bottom: 16px;
        transition: box-shadow 0.3s;
        cursor: pointer;
    }
    .custom-card:hover {
        box-shadow: 0 0 8px rgba(0, 0, 0, 0.3);
    }
    /* Image Container with forced square aspect ratio */
    .custom-img-container {
        position: relative;
        margin-bottom: 8px;
    }
    .custom-img {
        width: 100%;
        padding-top: 100%;
        position: relative;
        overflow: hidden;
        border-radius: 50%;
    }
    .custom-img img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    /* Text Section */
    .custom-text {
        margin-bottom: 8px;
        text-align: center;
    }
    .custom-name {
        font-weight: 600;
        font-size: 1.1em;
        margin-bottom: 4px;
    }
    .custom-price {
        font-size: 14px;
        color: #555;
        margin-bottom: 2px;
    }
    /* Form Inputs inside Card */
    .custom-card .form-group {
        margin-top: 8px;
    }
</style>
@endsection
