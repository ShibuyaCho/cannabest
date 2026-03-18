@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-center mb-5">Welcome to Our Dispensary Network</h1>

    <div class="row">
        @foreach($organizations as $organization)
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h2>{{ $organization->name }}</h2>
                    </div>
                    <div class="card-body">
                        <h3>Branches:</h3>
                        <ul class="list-group">
                            @foreach($organization->branches as $branch)
                                <li class="list-group-item">
                                    <a href="{{ route('branch.products', ['organization' => $organization->id, 'branch' => $branch->id]) }}">
                                        {{ $branch->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection