@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-center mb-4">Wholesale Settings</h1>

    <form action="{{ route('wholesale.settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="license_number">License Number</label>
            <input type="text" class="form-control" id="license_number" name="license_number" value="{{ $wholesale->license_number }}" required>
        </div>

        <!-- Add other settings fields as needed -->

        <button type="submit" class="btn btn-primary">Update Settings</button>
    </form>
</div>
@endsection