@extends('layouts.Wholesale')

@section('content')
<div class="container-fluid">
    <h1 class="mt-4">Wholesale Admin Dashboard</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">User Information</div>
                <div class="card-body">
                    <p><strong>Name:</strong> {{ $user->name }}</p>
                    <p><strong>Email:</strong> {{ $user->email }}</p>
                    <p><strong>Phone:</strong> {{ $user->phone ?? 'N/A' }}</p>
                    <p><strong>Role:</strong> {{ $user->role->name }}</p>
                    <p><strong>Created At:</strong> {{ $user->created_at->format('Y-m-d H:i:s') }}</p>
                    <!-- Add any other user fields you want to display -->
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Organization Information</div>
                <div class="card-body">
                    <p><strong>Name:</strong> {{ $organization->name }}</p>
                    <p><strong>Address:</strong> {{ $organization->address ?? 'N/A' }}</p>
                    <p><strong>Phone:</strong> {{ $organization->phone ?? 'N/A' }}</p>
                    <p><strong>Email:</strong> {{ $organization->email ?? 'N/A' }}</p>
                    <p><strong>Type:</strong> {{ $organization->type }}</p>
                    <p><strong>Created At:</strong> {{ $organization->created_at->format('Y-m-d H:i:s') }}</p>
                    <!-- Add any other organization fields you want to display -->
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Wholesale Settings</div>
                <div class="card-body">
                    <p><strong>Minimum Order Amount:</strong> ${{ number_format($settings->min_order_amount, 2) }}</p>
                    <p><strong>Discount Percentage:</strong> {{ $settings->discount_percentage }}%</p>
                    <!-- Add any other settings you want to display -->
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Top Performing Products</div>
                <div class="card-body">
                    <ul class="list-group">
                        @foreach($topProducts as $product)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $product->name }}
                                <span class="badge badge-primary badge-pill">{{ $product->sales_count }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Sales by Category</div>
                <div class="card-body">
                    <canvas id="salesByCategory"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Total Sales (Completed Orders)</div>
            <div class="card-body">
                <h2>${{ number_format($totalCompletedSales, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Total Orders</div>
            <div class="card-body">
                <h2>{{ $totalOrders }}</h2>
            </div>
        </div>
    </div>
</div>
    <div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">Recent Completed Orders</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($completedOrders as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->user->name }}</td>
                                <td>${{ number_format($order->total_amount, 2) }}</td>
                                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    var ctx = document.getElementById('salesByCategory').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: {!! json_encode($salesByCategory->pluck('name')) !!},
            datasets: [{
                data: {!! json_encode($salesByCategory->pluck('sales_count')) !!},
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                ]
            }]
        },
        options: {
            responsive: true,
            title: {
                display: true,
                text: 'Sales by Category'
            }
        }
    });
</script>
@endsection