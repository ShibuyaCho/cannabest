@extends('layouts.app')

@section('content')

<div class="row wrapper border-bottom white-bg page-heading">
  <div class="col-lg-10">
    <h2>@lang('common.add') @lang('common.user')</h2>
  </div>
  <div class="col-lg-2"></div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
  <div class="row">
    <div class="col-lg-12">
      <div class="ibox float-e-margins">
        <div class="ibox-title">
          <h5>@lang('common.add_new')</h5>
          <div class="ibox-tools">
            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
          </div>
        </div>

        <div class="ibox-content">
          {{-- Errors --}}
          @if ($errors->any())
            <div class="alert alert-danger">
              <strong>@lang('common.there_were_some_problems')</strong>
              <ul class="m-t-xs">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          @php
            use Illuminate\Support\Str;

            // Filter roles so Super Admin is NOT an option
            $filteredRoles = collect($roles ?? [])->filter(function($role){
              $slug = Str::slug($role->name ?? $role->display_name ?? '');
              return !in_array($slug, ['super-admin','superadmin','super-administrator']);
            });
          @endphp

          <form action="{{ url('users') }}" class="form-horizontal" method="POST" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="form-group">
              <label class="col-sm-2 control-label" for="name">@lang('common.name')</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}">
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            <div class="form-group">
              <label class="col-sm-2 control-label" for="email">@lang('common.email')</label>
              <div class="col-sm-10">
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="hr-line-dashed"></div>

            <div class="form-group">
              <label class="col-sm-2 control-label" for="role_id">@lang('common.role')</label>
              <div class="col-sm-10">
                <select class="form-control" id="role_id" name="role_id" required>
                  @forelse($filteredRoles as $role)
                    <option value="{{ $role->id }}" {{ (string)$role->id === (string)old('role_id') ? 'selected' : '' }}>
                      {{ $role->display_name ?? $role->name }}
                    </option>
                  @empty
                    <option value="" disabled>@lang('common.no_roles_available')</option>
                  @endforelse
                </select>
                @error('role_id')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>
            </div>

            <div class="hr-line-dashed"></div>

            <div class="form-group">
              <label class="col-sm-2 control-label" for="password">@lang('common.password')</label>
              <div class="col-sm-10">
                <input type="password" class="form-control" id="password" name="password" required minlength="6" autocomplete="new-password">
                @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
              </div>
            </div>

            <div class="hr-line-dashed"></div>

            <div class="form-group">
              <label class="col-sm-2 control-label" for="password_confirmation">@lang('common.confirm_password')</label>
              <div class="col-sm-10">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
              </div>
            </div>

            <div class="hr-line-dashed"></div>

            <div class="form-group">
              <div class="col-sm-4 col-sm-offset-2">
                <a class="btn btn-white" href="{{ route('users.index') }}">@lang('common.cancel')</a>
                <button class="btn btn-primary" type="submit">Save Changes</button>
              </div>
            </div>

          </form>
        </div> {{-- .ibox-content --}}
      </div>
    </div>
  </div>
</div>

@endsection
