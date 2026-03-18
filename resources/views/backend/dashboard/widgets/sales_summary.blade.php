<div class="card mb-4">
    <div class="card-header">{{ $widget['title'] }}</div>
    <div class="card-body">
        @if (isset($data))
            <h4>Today's Sales: {{ $data['today'] }}</h4>
            <h4>This Week's Sales: {{ $data['week'] }}</h4>
            <h4>This Month's Sales: {{ $data['month'] }}</h4>
        @else
            <p>No data available</p>
        @endif
    </div>
</div>