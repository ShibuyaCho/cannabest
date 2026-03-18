@extends('layouts.Wholesale')

@section('content')
<div class="container">
    <h1>Edit Wholesale Order</h1>
    <form action="{{ route('admin.wholesale.orders.update', $order->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Order Information</h5>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status" class="form-control">
                        <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment_method">Payment Method:</label>
                    <input type="text" name="payment_method" id="payment_method" class="form-control" value="{{ $order->payment_method }}">
                </div>
                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea name="notes" id="notes" class="form-control">{{ $order->notes }}</textarea>
                </div>
                
                <!-- Add more fields as needed -->

                <h5 class="card-title mt-4">Order Items</h5>
                @foreach($order->items as $index => $item)
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Product</label>
                            <input type="text" class="form-control" value="{{ $item->product->name }}" readonly>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="items[{{ $index }}][quantity]">Quantity</label>
                            <input type="number" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $item->quantity }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="items[{{ $index }}][price]">Price</label>
                            <input type="number" step="0.01" name="items[{{ $index }}][price]" class="form-control" value="{{ $item->price }}">
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update Order</button>
                <a href="{{ route('admin.wholesale.orders.show', $order->id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection