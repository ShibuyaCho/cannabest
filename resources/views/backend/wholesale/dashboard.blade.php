@extends('layouts.Wholesale')

@section('content')
<div class="container">
    <h1 class="text-center mb-4">Welcome, {{ $user->name }}</h1>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3>Account Overview</h3>
                </div>
                <div class="card-body">
                    <p><strong>Email:</strong> {{ $user->email }}</p>
                    <p><strong>Organization:</strong> {{ $user->organization->name ?? 'Not set' }}</p>
                    <p><strong>Branch:</strong> {{ $user->branch->name ?? 'Not set' }}</p>
                    <p><strong>License Number:</strong> {{ $wholesaleSettings->license_number ?? 'Not set' }}</p>
                    <p><strong>Total Brands:</strong> {{ $brands->count() }}</p>
                    <p><strong>Total Products:</strong> {{ $brands->flatMap->products->count() }}</p>
                    <a href="{{ route('wholesale.profile') }}" class="btn btn-primary">View Full Profile</a>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3>Recent Orders</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group" id="recentOrdersList">
                        <li class="list-group-item">Loading recent orders...</li>
                    </ul>
                    <a href="{{ route('wholesale.orders.index') }}" class="btn btn-primary mt-3">View All Orders</a>
                </div>
            </div>
        </div>
    </div>

    <h2 class="mb-4">Your Brands</h2>
    <div class="row" id="brandCards">
        @forelse($brands as $brand)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $brand->name }}</h5>
                        <p class="card-text">Products: {{ $brand->products->count() }}</p>
                        <p class="card-text orders-count" data-brand-id="{{ $brand->id }}">Orders: Loading...</p>
                        <button class="btn btn-primary btn-sm view-brand-details" data-brand-id="{{ $brand->id }}">View Details</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <p>No brands found. <a href="{{ route('wholesale.brands.create') }}">Create your first brand</a>.</p>
            </div>
        @endforelse
    </div>

    <!-- Modal for Brand Details -->
    <div class="modal fade" id="brandDetailsModal" tabindex="-1" role="dialog" aria-labelledby="brandDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="brandDetailsModalLabel">Brand Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Content will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Load recent orders
    $.ajax({
        url: "{{ route('wholesale.recent-orders') }}",
        method: 'GET',
        success: function(response) {
            var ordersList = $('#recentOrdersList');
            ordersList.empty();
            
            if (response.length === 0) {
                ordersList.append('<li class="list-group-item">No recent orders found.</li>');
            } else {
                response.forEach(function(order) {
                    ordersList.append(
                        '<li class="list-group-item">' +
'Order #' + order.id + ' - ' +
                        ' <span class="float-right">' + new Date(order.created_at).toLocaleDateString() + '</span>' +
                        '</li>'
                    );
                });
            }
        },
        error: function() {
            $('#recentOrdersList').html('<li class="list-group-item">Error loading recent orders.</li>');
        }
    });

    // Load order counts for each brand
    $('.orders-count').each(function() {
        var $this = $(this);
        var brandId = $this.data('brand-id');
        $.ajax({
            url: '/wholesale/brands/' + brandId + '/order-count',
            method: 'GET',
            success: function(response) {
                $this.text('Orders: ' + response.count);
            },
            error: function(xhr) {
                $this.text('Orders: Error');
            }
        });
    });

    // View brand details
    $('.view-brand-details').on('click', function() {
        var brandId = $(this).data('brand-id');
        $.ajax({
            url: '/wholesale/brands/' + brandId + '/details',
            method: 'GET',
            success: function(response) {
                $('#brandDetailsModal .modal-body').html(response);
                $('#brandDetailsModal').modal('show');
            },
            error: function(xhr) {
                console.log('Error loading brand details');
            }
        });
    });
});
</script>
@endpush