@extends('layouts.app')

@section('content')

<div class="row wrapper border-bottom white-bg page-heading">
  <div class="col-lg-10">
    <h2>Add Customer</h2>
    <ol class="breadcrumb">
      <li><a href="{{ url('') }}">@lang('common.home')</a></li>
      <li><a href="{{ route('customers.index') }}">Customers</a></li>
      <li class="active"><strong>Add</strong></li>
    </ol>
  </div>
  <div class="col-lg-2"></div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
  <div class="row">
    <div class="col-lg-12">
      <div class="ibox float-e-margins">
        <div class="ibox-title">
          <h5>Add Customer <small class="text-muted">Only email & password are required</small></h5>
          <div class="ibox-tools">
            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
          </div>
        </div>

        <div class="ibox-content">

          {{-- Validation errors --}}
          @if ($errors->any())
            <div class="alert alert-danger">
              <strong>There were some problems with your input.</strong>
              <ul class="m-t-xs">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          {{-- Organization context (read-only, bound in controller) --}}
          @php
            $org = Auth::user()->organization_id ?? null;
            $orgName = optional(Auth::user()->organization ?? null)->name;
          @endphp
          <div class="form-group">
            <label class="col-sm-2 control-label">Organization</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" value="{{ $orgName ? $orgName . ' (ID: ' . $org . ')' : ($org ? 'ID: ' . $org : '—') }}" disabled>
              <span class="help-block m-b-none">New customer will be assigned to this organization automatically.</span>
            </div>
          </div>
          <div class="hr-line-dashed"></div>

          <form action="{{ route('customers.store') }}" class="form-horizontal" method="POST" novalidate>
            @csrf

            {{-- Name (optional) --}}
            <div class="form-group">
              <label class="col-sm-2 control-label" for="name">Name</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="(Optional)">
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            {{-- Email (required) --}}
            <div class="form-group">
              <label for="email" class="col-sm-2 control-label">Email <span class="text-danger">*</span></label>
              <div class="col-sm-10">
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="customer@example.com">
                @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            {{-- Password (required) --}}
            <div class="form-group">
              <label for="password" class="col-sm-2 control-label">Password <span class="text-danger">*</span></label>
              <div class="col-sm-10">
                <div class="input-group">
                  <input type="password" class="form-control" id="password" name="password" required minlength="6" autocomplete="new-password" placeholder="Min 6 characters">
                  <span class="input-group-btn">
                    <button class="btn btn-default" type="button" id="togglePwd">Show</button>
                  </span>
                </div>
                @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            {{-- Confirm Password --}}
            <div class="form-group">
              <label for="password_confirmation" class="col-sm-2 control-label">Confirm Password <span class="text-danger">*</span></label>
              <div class="col-sm-10">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            {{-- Phone (optional) --}}
            <div class="form-group">
              <label for="phone" class="col-sm-2 control-label">Phone</label>
              <div class="col-sm-10">
                <input type="tel" class="form-control" id="phone" name="phone" value="{{ old('phone') }}" inputmode="tel" placeholder="(Optional)">
                @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            {{-- Address (optional) --}}
            <div class="form-group">
              <label for="address" class="col-sm-2 control-label">Address</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="address" name="address" value="{{ old('address') }}" placeholder="(Optional)">
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            {{-- City/State/Zip (optional) --}}
            <div class="form-group">
              <label for="city" class="col-sm-2 control-label">City</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="city" name="city" value="{{ old('city') }}" placeholder="(Optional)">
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            <div class="form-group">
              <label for="state" class="col-sm-2 control-label">State</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="state" name="state" value="{{ old('state') }}" placeholder="(Optional)">
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            <div class="form-group">
              <label for="zip" class="col-sm-2 control-label">Zip</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="zip" name="zip" value="{{ old('zip') }}" placeholder="(Optional)">
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            {{-- Actions --}}
            <div class="form-group">
              <div class="col-sm-4 col-sm-offset-2">
                <a class="btn btn-white" href="{{ route('customers.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Save</button>
              </div>
            </div>

          </form>
        </div> {{-- .ibox-content --}}
      </div>
    </div>
  </div>
</div>

{{-- Tiny helper to toggle password visibility --}}
<script>
  (function(){
    var btn = document.getElementById('togglePwd');
    if (!btn) return;
    btn.addEventListener('click', function(){
      var input = document.getElementById('password');
      if (!input) return;
      var isPw = input.type === 'password';
      input.type = isPw ? 'text' : 'password';
      this.textContent = isPw ? 'Hide' : 'Show';
    });
  })();
</script>

@endsection
