@extends('layouts.Wholesale')

@section('content')
<div class="wrapper wrapper-content animated fadeInRight">
  <div class="row">
    <div class="col-lg-12">
      <div class="ibox float-e-margins">
        <div class="ibox-title">
          <h5>Wholesale Settings</h5>
        </div>
        <div class="ibox-content">
          <form action="{{ route('wholesale.settings.update') }}" class="form-horizontal" method="POST">
            @csrf
            
       

            <h3>User Profile</h3>
            @foreach($userSettings as $key => $value)
                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ ucfirst($key) }}</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" name="user_settings[{{ $key }}]" value="{{ old('user_settings.'.$key, $value) }}">
                    </div>
                </div>
            @endforeach

          <h3>Wholesale Settings</h3>
@foreach($wholesaleSettings as $key => $setting)
    <div class="form-group">
        <label class="col-sm-2 control-label">{{ $setting->label ?? ucfirst($key) }}</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" name="wholesale_settings[{{ $key }}]" value="{{ old('wholesale_settings.'.$key, $setting->value ?? '') }}">
        </div>
    </div>
@endforeach

          

            <div class="form-group">
              <div class="col-sm-4 col-sm-offset-2">
                <button class="btn btn-primary" type="submit">Save Changes</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>


</script>
@endsection