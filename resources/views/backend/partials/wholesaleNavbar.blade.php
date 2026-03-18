<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css">

<nav class="navbar-default navbar-static-side" role="navigation">
  <div class="sidebar-collapse">
    <ul class="nav metismenu" id="side-menu">

      {{-- Profile / Logo --}}
      <li class="nav-header text-center">
        <img alt="logo" width="120" src="{{ asset('uploads/THC.png') }}" class="center-img mb-2" />
      </li>

      
        <li @if(Request::segment(1)=='brands') class="active" @endif>
          <a href="{{ url('brands') }}">
            <i class="fas fa-tags"></i>
            <span class="nav-label">Brands</span>
          </a>
        </li>
   

     
        <li @if(Request::segment(1)=='products') class="active" @endif>
          <a href="{{ url('products') }}">
            <i class="fas fa-box-open"></i>
            <span class="nav-label">Products</span>
          </a>
        </li>
    

        <li @if(Request::segment(1)=='invoices') class="active" @endif>
          <a href="{{ url('invoices') }}">
            <i class="fas fa-file-invoice"></i>
            <span class="nav-label">Invoices</span>
          </a>
        </li>
   

    
        <li @if(Request::segment(1)=='orders') class="active" @endif>
          <a href="{{ url('orders') }}">
            <i class="fas fa-shopping-cart"></i>
            <span class="nav-label">Orders</span>
          </a>
        </li>
     

        <li @if(Request::is('wholesale/settings')) class="active" @endif>
          <a href="{{ route('wholesale.settings.edit') }}">
            <i class="fas fa-cog"></i>
            <span class="nav-label">Wholesale Settings</span>
          </a>
        </li>

                                <li class="dropdown">
                    <a class="dropdown-toggle" href="#" role="button" id="profileDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-user"></i> <span class="nav-label">@lang('site.profile')</span> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="profileDropdown">
                        <li>
                            <a href="{{ url('settings/profile') }}">
                                <i class="fa fa-cog"></i> Profile Settings
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('wholesale.dashboard') }}">
                                <i class="fa fa-tachometer-alt"></i> Wholesale Profile
                            </a>
                        </li>
                    </ul>
                </li>
                
        <li>
                <a href="{{ url('logout') }}"><i class="fas fa-sign-out-alt"></i>  <span class="nav-label"> @lang('site.logout') </span></a>
            </li>
    </ul>

   
    <div class="sidebar-footer p-2 text-white" style="border-top:1px solid rgba(255,255,255,0.2);">
      <div class="d-flex justify-content-between align-items-center">
        <span style="font-size:12px;">Welcome, {{ Auth::user()->name }}</span>
     
      </div>
      <div id="startTimeSidebar" class="mt-1" style="font-size:12px; color:#fff;"></div>
    </div>

  </div>
</nav>

<style>
  .navbar-static-side {
    position: fixed;
    top: 0; left: 0;
    height: 100vh;
    overflow-y: auto;
  }
  .center-img {
    display: block;
    margin: 0 auto;
  }
</style>

<script>
  function sidebarClock() {
    const $btn    = $('#sidebarClock');
    const action  = $btn.data('action');
    const shiftId = $btn.data('shiftid');

    function setTimeDisplay(timeStr) {
      const d = new Date(timeStr);
      const txt = isNaN(d) 
        ? timeStr 
        : d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
      $('#startTimeSidebar').text('Clock In Time: ' + txt);
    }

    if (action === 'clockin') {
      $.get('/shift/clockin').done(resp => {
        $btn.data('action','clockout').data('shiftid',resp.shiftId).text('Clock Out');
        if (resp.shift_start_time) setTimeDisplay(resp.shift_start_time);
      }).fail(err => console.error(err));
    } else {
      $.get(`/shift/clockout/${shiftId}`).done(() => {
        $btn.data('action','clockin').data('shiftid','').text('Clock In');
        $('#startTimeSidebar').text('');
      }).fail(err => console.error(err));
    }
  }
</script>