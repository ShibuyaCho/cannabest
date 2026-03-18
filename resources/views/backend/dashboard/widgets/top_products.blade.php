<div class="card mb-4">
    <div class="card-header">{{ $widget['title'] }}</div>
    <div class="card-body">
        @if (isset($data) && count($data) > 0)
            <ul class="list-group">
                @foreach ($data as $product)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $product['name'] }}
                        <span class="badge bg-primary rounded-pill">{{ $product['sales'] }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p>No data available</p>
        @endif
    </div>
</div>