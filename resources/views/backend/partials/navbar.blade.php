<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css">

<nav class="navbar-default navbar-static-side" role="navigation">
    <div class="sidebar-collapse">
        <ul class="nav metismenu" id="side-menu">
            <li class="nav-header">
                <div class="dropdown profile-element">
                    <span>
                    <img alt="image" width="120" src="{{ asset('uploads/THC.png') }}" class="center-img" /><br>
                    </span>
                    <div class="logo-element">
                        <!-- Optional mini-logo -->
                    </div>
                </div>
            </li>
            
            @if(role_permission(35))
                <li @if(Request::segment(1) == "admin" or Request::segment(1) == "dashboard") class="active" @endif>
                    <a href="{{ url('dashboard') }}"><i class="fa fa-th-large"></i> <span class="nav-label">@lang('site.dashboard')</span></a>
                </li>
            @endif
            
            @if(role_permission(1))
                <li @if(Request::segment(1) == "sales" and Request::segment(2) == "create") class="active" @endif>
                    <a href="{{ url('sales/create') }}"><i class="fas fa-cash-register"></i>  <span class="nav-label">@lang('Cashier')</span></a>
                </li>
            @endif

            @if(role_permission(1))
                <li @if(Request::segment(1) == "sales" and Request::segment(2) == "") class="active" @endif>
                    <a href="{{ url('sales') }}"><i class="fa fa-th-large"></i> @lang('site.sales')</a>
                </li>
            @endif

            @if(role_permission(8))
                <li @if((Request::segment(1) == "categories" or Request::segment(1) == "products") and Request::segment(2) == "") class="active" @endif>
                    <a href="#"><i class="fas fa-poll"></i> <span class="nav-label">@lang('site.products')</span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level collapse">
                        <li @if(Request::segment(1) == "categories" and Request::segment(2) == "") class="active" @endif>
                            <a href="{{ url('categories') }}">@lang('site.categories')</a>
                        </li>
                        <li @if(Request::segment(1) == "products" and Request::segment(2) == "") class="active" @endif>
                            <a href="{{ url('products') }}">@lang('site.products')</a>
                        </li>
                    </ul>
                </li>
            @endif

            @if(role_permission(17))
                <li <?php if(Request::segment(1) == "reports") { ?>  class="active"; <?php } ?>>
                    <a href="#"><i class="fa fa-th-large"></i> <span class="nav-label">@lang('Analytics')</span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level collapse">
                        <li @if((Request::segment(1) == "reports" and Request::segment(2) == "sales")) class="active" @endif>
                            <a href="{{ url('reports/sales') }}">@lang('site.sales_report')</a>
                        </li>
                        <li @if((Request::segment(1) == "reports" and Request::segment(2) == "sales_by_products")) class="active" @endif>
                            <a href="{{ url('reports/sales_by_products') }}">@lang('site.product_by_sales')</a>
                        </li>
                        <li @if((Request::segment(1) == "reports" and Request::segment(2) == "graphs")) class="active" @endif>
                            <a href="{{ url('reports/graphs') }}">@lang('site.graphs')</a>
                        </li>
                        <li @if((Request::segment(1) == "reports" and Request::segment(2) == "expenses")) class="active" @endif>
                            <a href="{{ url('reports/expenses') }}">@lang('site.expense_report')</a>
                        </li>
                    </ul>
                </li>
            @endif

            @if(role_permission(20))
                <li @if((Request::segment(1) == "users" or Request::segment(1) == "customers") and Request::segment(2) == "") class="active" @endif>
                    <a href="#"><i class="fa fa-users"></i> <span class="nav-label">User & Customers</span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level collapse">
                        <li @if(Request::segment(1) == "customers" and Request::segment(2) == "") class="active" @endif>
                            <a href="{{ url('customers') }}">Customers</a>
                        </li>
                        <li @if(Request::segment(1) == "users") class="active" @endif>
                            <a href="{{ url('users') }}">Users</a>
                        </li>  
                    </ul>
                </li>
            @endif

            @if(role_permission(34))
                <li @if((Request::segment(1) == "update_inventory" or Request::segment(1) == "quantity_alerts" or Request::segment(1) == "inventory" or Request::segment(1) == "min_quantity_alert") and Request::segment(2) == "") class="active" @endif>
                    <a href="#"><i class="fa fa-database"></i> <span class="nav-label">Inventory</span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level collapse">
                        <li @if(Request::segment(1) == "update_inventory" and Request::segment(2) == "") class="active" @endif>
                            <a href="{{ url('update_inventory') }}">Inventory</a>
                        </li>
                        <li @if(Request::segment(1) == "quantity_alerts") class="active" @endif>
                            <a href="{{ url('quantity_alerts') }}">Quantity Alerts</a>
                        </li>
                    </ul>
                </li>
            @endif

            @if(role_permission(16))
                <li @if((Request::segment(1) == "settings" or Request::segment(1) == "sliders" or Request::segment(1) == "pages") and ((Request::segment(2) == "homepage" or Request::segment(2) == "menu_management" or Request::segment(2) == ""))) class="active" @endif>
                    <a href="#"><i class="fa fa-th-large"></i> <span class="nav-label">@lang('site.frontend_website')</span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level collapse">
                        <li @if(Request::segment(2) == "homepage") class="active" @endif>
                            <a href="{{ url('settings/homepage') }}">@lang('site.homepage_setting')</a>
                        </li>
                        <li @if(Request::segment(1) == "sliders") class="active" @endif>
                            <a href="{{ url('sliders') }}">@lang('site.sliders')</a>
                        </li>
                        <li @if(Request::segment(1) == "pages") class="active" @endif>
                            <a href="{{ url('pages') }}">@lang('site.pages')</a>
                        </li>
                    </ul>
                </li>
            @endif

            @if(role_permission(18))
                <li @if(Request::segment(1) == "roles") class="active" @endif>
                    <a href="{{ url('roles') }}"><i class="fas fa-people-arrows"></i><span class="nav-label"> @lang('site.roles')</span></a>
                </li>
            @endif

            @if(role_permission(21))
                <li @if(Request::segment(2) == "profile") class="active" @endif>
                    <a href="{{ url('settings/profile') }}"><i class="fa fa-user"></i> <span class="nav-label"> @lang('site.profile') </span></a>
                </li>
            @endif

            @if(role_permission(15))
                <li @if(Request::segment(2) == "general") class="active" @endif>
                    <a href="{{ url('settings/general') }}"><i class="fa fa-gear"></i> <span class="nav-label"> @lang('site.settings')</span></a>
                </li>
            @endif

           
            <li>
                <a href="{{ url('logout') }}"><i class="fas fa-sign-out-alt"></i>  <span class="nav-label"> @lang('site.logout') </span></a>
            </li>
        </ul>

        @php
    // Determine if the user is clocked in. 
    // (Assumes $shiftdata is set when a shift exists and has a non-null shift_start_time.)
    $isClockedIn = isset($shiftdata) && !is_null($shiftdata->shift_start_time);
    $shiftId = $isClockedIn ? $shiftdata->id : '';
@endphp
<div id="startTimeSidebar" style="color: white; font-size: 12px; margin-top: 5px;"></div>
<!-- Sidebar Footer: Welcome Message & Clock In/Out Button -->
<div class="sidebar-footer" style="padding: 10px; border-top: 1px solid rgba(255,255,255,0.2);">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <span style="color: white; font-size: 12px;">Welcome, {{ Auth::user()->name }}</span>
        <button type="button" id="sidebarClock" class="btn btn-xs" 
                style="border: 1px solid white; background: transparent; color: white; padding: 4px 8px;"
                data-action="{{ $isClockedIn ? 'clockout' : 'clockin' }}"
                data-shiftid="{{ $shiftId }}"
                onclick="sidebarClock()">
            {{ $isClockedIn ? 'Clock Out' : 'Clock In' }}
        </button>
    </div>
    
</div>
<style>
    .navbar-static-side {
    position: fixed;    /* Fixes the sidebar to the viewport */
    top: 0;             /* Aligns it to the top */
    left: 0;            /* Positions it at the left edge */
    height: 100vh;      /* Sets its height to 100% of the viewport */
       /* Enables vertical scrolling if the content is taller than the viewport */
}
.center-img {
  display: center;
  margin: 0 auto;
}
</style>

    </div>
    <script>
    function sidebarClock() {
    // Get the sidebar clock button jQuery element.
    var $btn = $('#sidebarClock');
    // Retrieve the current action from the button's data attribute.
    var action = $btn.data('action');

    // Function to update the displayed clock in time.
    function updateStartTime(timeString) {
        // Convert the returned time to a Date object.
        var dateObj = new Date(timeString);
        // Check if the date is valid.
        if (isNaN(dateObj.getTime())) {
            // If invalid, display the raw value.
            $('#startTimeSidebar').css('color', 'white').text("Clock In Time: " + timeString);
        } else {
            // Format the date as a 12-hour time with 2-digit hour and minute.
            var formattedTime = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            $('#startTimeSidebar').css('color', 'white').text("Clock In Time: " + formattedTime);
        }
    }

    // Determine whether to perform clock in or clock out.
    if (action === 'clockin') {
        console.log("Sidebar: Clock In activated");

        $.ajax({
            url: '/shift/clockin',  // GET route for clock in.
            type: 'GET',
            success: function(response) {
                console.log("Sidebar Clock In response:", response);
                // Update the button to reflect the "clock out" state.
                $btn.data('action', 'clockout');
                $btn.data('shiftid', response.shiftId);
                $btn.text('Clock Out');

                // Update the sidebar clock in time if provided.
                if (response.shift_start_time) {
                    updateStartTime(response.shift_start_time);
                }
            },
            error: function(xhr) {
                console.error("Sidebar Clock In error:", xhr.responseText);
            }
        });
    } else if (action === 'clockout') {
        // Retrieve the stored shift ID from the button's data attribute.
        var shiftId = $btn.data('shiftid');
        console.log("Sidebar: Clock Out activated for shift ID:", shiftId);

        $.ajax({
            url: '/shift/clockout/' + shiftId,  // GET route for clock out.
            type: 'GET',
            success: function(response) {
                console.log("Sidebar Clock Out response:", response);
                // Reset the button to the "clock in" state.
                $btn.data('action', 'clockin');
                $btn.data('shiftid', '');
                $btn.text('Clock In');
                // Clear the clock in time display.
                $('#startTimeSidebar').text("");
            },
            error: function(xhr) {
                console.error("Sidebar Clock Out error:", xhr.responseText);
            }
        });
    }
}
</script>

</nav>
