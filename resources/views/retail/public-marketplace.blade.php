@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-center mb-4">Dispensary Marketplace</h1>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <input type="text" id="searchInput" class="form-control" placeholder="Search dispensaries...">
        </div>
        <div class="col-md-4">
            <button id="locationSearchBtn" class="btn btn-secondary btn-block">Search Near Me</button>
        </div>
    </div>

    <div id="organizationList" class="row">
        @foreach($organizations as $organization)
            <div class="col-md-4 mb-4 organization-card" data-name="{{ strtolower($organization->name) }}" data-address="{{ strtolower($organization->physical_address) }}">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">{{ $organization->name }}</h5>
                        <p class="card-text">Products available: {{ $organization->products_count }}</p>
                        <p class="card-text">Address: {{ $organization->physical_address }}</p>
                        <button class="btn btn-primary mt-auto" data-toggle="modal" data-target="#loginModal">View Products</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="text-center mt-4">
        <p>Don't have an account? <a href="{{ route('register') }}">Sign up now</a> to start shopping!</p>
    </div>
</div>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">Login</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .card {
        transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-5px);
    }
</style>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/js-marker-clusterer/1.0.0/markerclusterer_compiled.js"></script>
<script>
$(document).ready(function() {
    $('#loginModal').on('shown.bs.modal', function () {
        $('#email').focus();
    });

    // Search functionality
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.organization-card').filter(function() {
            $(this).toggle($(this).data('name').indexOf(value) > -1 || $(this).data('address').indexOf(value) > -1)
        });
    });

    // Location-based search
    $('#locationSearchBtn').on('click', function() {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lon = position.coords.longitude;
                
                // Here you would typically send these coordinates to your server
                // and retrieve nearby organizations. For this example, we'll just
                // simulate it by showing all organizations and sorting them randomly.
                
                var $organizations = $('.organization-card').get();
                $organizations.sort(function(){
                    return Math.round(Math.random())-0.5;
                });
                $('#organizationList').html($organizations);
                
                alert("Showing dispensaries near your location!");
            });
        } else {
            alert("Geolocation is not supported by your browser.");
        }
    });
});
</script>
@endsection