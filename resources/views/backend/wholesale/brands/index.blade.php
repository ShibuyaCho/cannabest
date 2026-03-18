@extends('layouts.wholesale-customer')

@section('content')
    <h1 class="mb-4">Wholesale Brands</h1>
    <div class="row">
        @foreach($organizations as $organization)
            @foreach($organization->brands as $brand)
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">{{ $brand->name }}</h5>
                            <p class="card-text">{{ $organization->name }}</p>
                            <a href="{{ route('wholesale.customer.brand-products', $brand) }}" class="btn btn-primary">View Products</a>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>

    <!-- Cart Section -->
    <div class="cart-section mt-5">
        <h2>Your Cart</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="cartItems">
                <!-- Cart items will be dynamically added here -->
            </tbody>
        </table>
        <h3>Total: <span id="cartTotal">0.00</span></h3>
        <button class="btn btn-success" id="generateInvoice">Generate Invoice</button>
    </div>
@endsection

@section('scripts')
<script>
    let cart = [];

    function addToCart(productId, productName, productPrice) {
        const existingItem = cart.find(item => item.id === productId);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({ id: productId, name: productName, price: productPrice, quantity: 1 });
        }
        updateCartDisplay();
    }

    function updateCartDisplay() {
        const cartItemsContainer = document.getElementById('cartItems');
        cartItemsContainer.innerHTML = '';
        let total = 0;

        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.name}</td>
                <td>${item.quantity}</td>
                <td>${item.price.toFixed(2)}</td>
                <td>${itemTotal.toFixed(2)}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="removeFromCart(${item.id})">Remove</button>
                </td>
            `;
            cartItemsContainer.appendChild(row);
        });

        document.getElementById('cartTotal').textContent = total.toFixed(2);
    }

    function removeFromCart(productId) {
        cart = cart.filter(item => item.id !== productId);
        updateCartDisplay();
    }

    document.getElementById('generateInvoice').addEventListener('click', function() {
        // Logic to generate and print invoice
        alert('Invoice generated!');
    });
</script>
@endsection