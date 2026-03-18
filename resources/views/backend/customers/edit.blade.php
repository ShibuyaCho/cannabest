@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="panel panel-default">
                <div class="panel-heading">Customers - Edit</div>

                <div class="panel-body">
                    <form action="{{ url('customers/' . $customer->id) }}" method="POST">
                        <input type="hidden" name="_method" value="put">
                        {{ csrf_field() }}

                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $customer->name) }}">
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="text" class="form-control" id="email" name="email" value="{{ old('email', $customer->email) }}">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}">
                        </div>

                        

                        <div class="form-group">
                            <label for="address">Address</label>
                            <input class="form-control" id="address" name="address" value="{{old('address', $customer->address) }}" >
                        </div>

                        <div class="form-group">
                            <label for="phone">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $customer->city) }}">
                        </div>

                        <div class="form-group">
                            <label for="phone">State</label>
                            <input type="text" class="form-control" id="state" name="state" value="{{ old('state', $customer->state) }}">
                        </div>

                        <div class="form-group">
                            <label for="phone">Zip</label>
                            <input type="text" class="form-control" id="zip" name="zip" value="{{ old('zip', $customer->zip) }}">
                        </div>
                         

                         <div class="form-group">
                            <label for="password">Password <small>Leave empty if not want to change</small></label>
                            <input type="password" class="form-control" id="password" name="password" value="">
                        </div>


                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a class="btn btn-link" href="{{ url('customers') }}">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection