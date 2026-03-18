@extends('layouts.Wholesale')

@section('content')
<div class="container">
    <h1>Wholesale Order Details</h1>
    <div class="card">
        <div class="card-header">
            Order #{{ $order->order_number }}
             @if(auth()->user()->hasRole('org_admin'))
<a href="{{ route('admin.wholesale.orders.edit', $order->id) }}" class="btn btn-primary">Edit Order</a>
            @endif
        </div>
        <div class="card-body">
            <h5 class="card-title">Order Information</h5>
            <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
            <p><strong>Total Amount:</strong> ${{ number_format($order->total_amount, 2) }}</p>
            <p><strong>Payment Method:</strong> {{ ucfirst($order->payment_method) }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at ? $order->created_at->format('M d, Y g:i A') : 'N/A' }}</p>
            <p><strong>Notes:</strong> {{ $order->notes ?? 'N/A' }}</p>

            <h5 class="card-title mt-4">Customer Information</h5>
            <p><strong>Name:</strong> {{  $order->createdByUser->name ?? 'N/A'  }}</p>
            <p><strong>Email:</strong> {{ $order->createdByUser?->email ?? 'N/A' }}</p>>

            <h5 class="card-title mt-4">Order Items</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->wholesaleProduct->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format($item->price, 2) }}</td>
                        <td>${{ number_format($item->quantity * $item->price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Total:</th>
                        <th>${{ number_format($order->total_amount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <a href="{{ route('wholesale.orders.index') }}" class="btn btn-secondary mt-3">Back to Orders</a>
</div>
@endsection