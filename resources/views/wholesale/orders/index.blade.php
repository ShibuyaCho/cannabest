@extends('layouts.Wholesale')

@section('content')
<div class="container">
    <h1 class="mb-4">Wholesale Orders</h1>

    <div class="btn-group mb-4" role="group" aria-label="Order status filter">
        <button type="button" class="btn btn-outline-primary active" data-status="all">All</button>
        <button type="button" class="btn btn-outline-warning" data-status="pending">Pending</button>
        <button type="button" class="btn btn-outline-info" data-status="processing">Processing</button>
        <button type="button" class="btn btn-outline-success" data-status="completed">Completed</button>
        <button type="button" class="btn btn-outline-danger" data-status="cancelled">Cancelled</button>
    </div>

    <div id="orders-container" class="row">
        @foreach($orders as $order)
            <div class="col-md-6 mb-4 order-card" data-status="{{ $order->status }}">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order #{{ $order->order_number }}</h5>
                        <span class="badge badge-{{ $order->status_color }}">{{ ucfirst($order->status) }}</span>
                    </div>
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Customer: {{ $order->createdByUser->name ?? 'N/A' }}</h6>    
                        <h6 class="card-subtitle mb-2 text-muted">Organization: {{ $order->organization->name }}</h6>
                        <p class="card-text">Order Date: {{ $order->created_at->format('M d, Y H:i') }}</p>
                        <h6 class="mt-4 mb-2">Products:</h6>
                        <ul class="list-group list-group-flush mb-3">
                            @foreach($order->items as $item)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $item->wholesaleProduct->name }}
                                    <span>
                                        {{ $item->quantity }} x ${{ number_format($item->price, 2) }}
                                        = ${{ number_format($item->quantity * $item->price, 2) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Total:</strong>
                            <h5>${{ number_format($order->total_amount, 2) }}</h5>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('wholesale.orders.show', ['order' => $order->id]) }}" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $orders->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.btn-group .btn').on('click', function() {
            $('.btn-group .btn').removeClass('active');
            $(this).addClass('active');
            
            var status = $(this).data('status');
            if (status === 'all') {
                $('.order-card').show();
            } else {
                $('.order-card').hide();
                $('.order-card[data-status="' + status + '"]').show();
            }
        });
    });
</script>
@endpush