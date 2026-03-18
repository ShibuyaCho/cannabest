@extends('layouts.customer')
@section('content')
<head>
    <meta charset="UTF-8">
    <title>Menu</title>
    <!-- Global CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/customer.css') }}" rel="stylesheet">
    <style>
        /* ---------- Layout ---------- */
        /* The cart area is narrow and centered */
        .cart-container {
            width: 400px;
            margin: 20px auto;
        }
        /* The menu area takes up a large part of the page */
        .menu-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        /* ---------- Cart Section ---------- */
        .cart-section {
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .cart-section h5 {
            margin-bottom: 15px;
        }
        #car_items, .cart-table-wrap {
            background-color: #fcfcfc;
            min-height: 300px;
            padding: 10px;
            overflow-y: auto;
        }
        .cart-buttons button {
            margin-top: 10px;
        }
        
        /* ---------- Menu / Products Section ---------- */
        .menu-container .row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .menu-item {
            background: #fcfcfc;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: box-shadow 0.3s ease;
            margin-bottom: 20px;
        }
        .menu-item:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .menu-item img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .menu-item h2 {
            font-size: 20px;
            margin: 10px 0;
        }
        .menu-item p {
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        /* ---------- Footer (if needed) ---------- */
        .footer {
            background-color: #f1f1f1;
            font-size: 12px;
            color: #666;
            text-align: center;
            padding: 10px 0;
            margin-top: auto;
        }
    </style>
</head>

<!-- The header remains untouched (loaded in layouts.customer) -->

<!-- Cart Container: Appears immediately under the header -->
<div class="cart-container">
    <div class="cart-section">
        <h5>Cart</h5>
        <div id="car_items">
            <!-- Cart table -->
            <table width="100%" border="0" style="border-spacing: 5px; border-collapse: separate;">
                <tbody id="CartHTML"></tbody>
            </table>
            <hr>
            <table width="100%" border="0" style="border-spacing: 5px; border-collapse: separate;">
                <tbody>
                    <tr>
                        <td><h4>Sub Total</h4></td>
                        <td class="text-right"><h4 id="p_subtotal">0.00</h4></td>
                    </tr>
                    <tr>
                        <td><h4>Discount</h4></td>
                        <td class="text-right"><h4 id="p_discount">0.00</h4></td>
                    </tr>
                    <tr>
                        <td><h4>Tax ({{ setting_by_key("vat") }}%)</h4></td>
                        <td class="text-right"><h4 id="p_hst">0.00</h4></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="cart-buttons">
            <button type="button" id="Checkout" class="btn btn-primary btn-block">Save Order</button>
            <button type="button" id="ClearCart" class="btn btn-danger btn-block">Clear Cart</button>
        </div>
    </div>
</div>

<!-- Menu Container: Full-width product selection -->
<div class="menu-container">
    <div class="row" id="portfolio">
        @foreach($products as $product)
            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3 menu-item">
                @if(file_exists(public_path('public/uploads/products/' . $product->id . '.jpg')))
                    <img src="{{ url('uploads/products/' . $product->id . '.jpg') }}" alt="Product">
                @else
                    <img src="{{ url('herbs/noimage.jpg') }}" alt="Product">
                @endif
                <h2>{{ $product->name }}</h2>
                <p>{{ $product->description }}</p>
                <!-- Add-to-cart button; if you want the whole card to be clickable, you could also wrap the entire card in a link -->
                <button class="btn btn-primary AddToCart"
                        data-id="{{ $product->id }}"
                        data-name="{{ $product->name }}">
                    Add to Cart
                </button>
            </div>
        @endforeach
    </div>
</div>

<!-- Hidden input for order type if needed -->
<input type="hidden" id="OrderType" value="order">

<!-- (Optional) Order Placed Modal -->
<div class="modal fade" id="orderPlacedModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-body text-center">
        <h4>Order Placed</h4>
        <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript -->
<script src="{{ url('assets/js/lodash.min.js') }}"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/2.8.9/js/bootstrap.min.js"></script>
<script src="{{ url('assets/js/inspinia.js') }}"></script>
<script>
    // Category filter functionality can be added here if needed.

    // When a product's Add to Cart button is clicked, add the product to the cart.
    $(document).on('click', '.AddToCart', function() {
        var productId = $(this).data('id');
        var productName = $(this).data('name');
        // Call your addToCart function here. For example:
        addToCart(productId, productName);
    });

    // (Include your cart functions and AJAX checkout logic as needed.)
</script>
@endsection
