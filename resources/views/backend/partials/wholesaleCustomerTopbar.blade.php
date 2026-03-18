<div class="row border-bottom">
    <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0; background-color: black; height: auto; min-height: 90px; padding-top: 0;">
        <div class="navbar-header" style="padding-left: 15px; position: relative; height: 90px; display: flex; align-items: flex-start;">
            @if(Auth::check())
                <div class="dropdown" style="padding-top: 5px;">
                    <a class="navbar-brand dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="height: auto; padding-top: 0;">
                        <img alt="Logo" src="{{ asset('uploads/THC.png') }}" style="max-height: 90px; width: auto; object-fit: cover; object-position: top; clip-path: inset(0px 0 25px 0);" />
                    </a>

                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                        @if(Auth::check())
                            @if(in_array(Auth::user()->role_id, [5, 6]))
                                <li><a class="dropdown-item" href="{{ Auth::user()->role_id == 5 ? route('retail.public-marketplace') : route('wholesale.public-marketplace') }}"><i class="fa fa-th-large"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="{{ url('logout') }}"><i class="fas fa-sign-out-alt"></i> @lang('site.logout')</a></li>
                            @else
                                @if(role_permission(35))
                                    <li><a class="dropdown-item" href="{{ url('dashboard') }}"><i class="fa fa-th-large"></i> @lang('site.dashboard')</a></li>
                                @endif
                                @if(role_permission(1))
                                    <li><a class="dropdown-item" href="{{ url('sales/create') }}"><i class="fas fa-cash-register"></i> @lang('Cashier')</a></li>
                                    <li><a class="dropdown-item" href="{{ url('sales') }}"><i class="fa fa-th-large"></i> @lang('site.sales')</a></li>
                                @endif
                                @if(role_permission(8))
                                    <li class="dropdown-submenu">
                                        <a class="dropdown-item dropdown-toggle" href="#"><i class="fas fa-poll"></i> @lang('site.products')</a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="{{ url('categories') }}">@lang('site.categories')</a></li>
                                            <li><a class="dropdown-item" href="{{ url('products') }}">@lang('site.products')</a></li>
                                        </ul>
                                    </li>
                                @endif
                                @if(role_permission(17))
                                    <li class="dropdown-submenu">
                                        <a class="dropdown-item dropdown-toggle" href="#"><i class="fa fa-th-large"></i> @lang('Analytics')</a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="{{ url('reports/sales') }}">@lang('site.sales_report')</a></li>
                                            <li><a class="dropdown-item" href="{{ url('reports/sales_by_products') }}">@lang('site.product_by_sales')</a></li>
                                            <li><a class="dropdown-item" href="{{ url('reports/graphs') }}">@lang('site.graphs')</a></li>
                                            <li><a class="dropdown-item" href="{{ url('reports/expenses') }}">@lang('site.expense_report')</a></li>
                                        </ul>
                                    </li>
                                @endif
                                @if(role_permission(20))
                                    <li class="dropdown-submenu">
                                        <a class="dropdown-item dropdown-toggle" href="#"><i class="fa fa-users"></i> User & Customers</a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="{{ url('customers') }}">Customers</a></li>
                                            <li><a class="dropdown-item" href="{{ url('users') }}">Users</a></li>
                                        </ul>
                                    </li>
                                @endif
                                @if(role_permission(34))
                                    <li class="dropdown-submenu">
                                        <a class="dropdown-item dropdown-toggle" href="#"><i class="fa fa-database"></i> Inventory</a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="{{ url('update_inventory') }}">Inventory</a></li>
                                            <li><a class="dropdown-item" href="{{ url('quantity_alerts') }}">Quantity Alerts</a></li>
                                        </ul>
                                    </li>
                                @endif
                                @if(role_permission(16))
                                    <li class="dropdown-submenu">
                                        <a class="dropdown-item dropdown-toggle" href="#"><i class="fa fa-th-large"></i> @lang('site.frontend_website')</a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="{{ url('settings/homepage') }}">@lang('site.homepage_setting')</a></li>
                                            <li><a class="dropdown-item" href="{{ url('sliders') }}">@lang('site.sliders')</a></li>
                                            <li><a class="dropdown-item" href="{{ url('pages') }}">@lang('site.pages')</a></li>
                                        </ul>
                                    </li>
                                @endif
                                @if(role_permission(18))
                                    <li><a class="dropdown-item" href="{{ url('roles') }}"><i class="fas fa-people-arrows"></i> @lang('site.roles')</a></li>
                                @endif
                                @if(role_permission(21))
                                    <li><a class="dropdown-item" href="{{ url('settings/profile') }}"><i class="fa fa-user"></i> @lang('site.profile')</a></li>
                                @endif
                                @if(role_permission(15))
                                    <li><a class="dropdown-item" href="{{ url('settings/general') }}"><i class="fa fa-gear"></i> @lang('site.settings')</a></li>
                                @endif
                                <li><a class="dropdown-item" href="{{ url('logout') }}"><i class="fas fa-sign-out-alt"></i> @lang('site.logout')</a></li>
                            @endif
                        @endif
                    </ul>
                </div>
            @else
                <a class="navbar-brand" href="{{ url('/') }}" style="height: auto; padding-top: 0;">
                    <img alt="Logo" src="{{ asset('uploads/THC.png') }}" style="max-height: 90px; width: auto; object-fit: cover; object-position: top; clip-path: inset(0px 0 25px 0);" />
                </a>
            @endif
        </div>
        <ul class="nav navbar-top-links navbar-right" style="height: 90px; display: flex; flex-direction: column; align-items: flex-end; justify-content: flex-start; padding-right: 15px; padding-top: 5px;">
            <li style="margin-bottom: 5px; display: flex; align-items: center;">
                <span style="color: white; margin-right: 10px;">
                    Welcome, 
                    @if(Auth::check())
                        {{ Auth::user()->name }}
                    @else
                        Guest
                    @endif
                </span>
                @if(Auth::check() && !in_array(Auth::user()->role_id, [5, 6]))
                    <button type="button" class="btn" data-toggle="modal" data-target="#inventoryModal" style="padding: 0;">
                        <i class="fa fa-bell" style="color: white; font-size: 5px;"></i>
                        @if(isset($lowInventoryItems) && $lowInventoryItems->count() > 0)
                            <span class="badge badge-danger">{{ $lowInventoryItems->count() }}</span>
                        @endif
                    </button>
                @endif
            </li>
            @if(Auth::check() && !in_array(Auth::user()->role_id, [5, 6]))
                <li style="display: flex; align-items: center;">
                    <span id="clockDisplay" style="color: white; margin-right: 10px;"></span>
                    <button id="clockButton" class="btn btn-primary" onclick="toggleClock()">
                        Clock In
                    </button>
                </li>
            @endif
        </ul>
    </nav>
</div>

<!-- Inventory Modal -->
<div class="modal fade" id="inventoryModal" tabindex="-1" role="dialog" aria-labelledby="inventoryModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="inventoryModalLabel">Low Inventory Alerts</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        @if(isset($lowInventoryItems) && $lowInventoryItems->isNotEmpty())
            @foreach($lowInventoryItems as $item)
                <div class="alert alert-warning">
                    <strong>{{ str_replace(':gls:', 'Green Leaf Special', $item->name) }}</strong> is low.
                    <span class="text-muted">
                        Current: {{ $item->storeQty }} / Min: {{ $item->min_qty }}
                    </span>
                </div>
            @endforeach
        @else
            <p>No low inventory alerts!</p>
        @endif
      </div>
      <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<style>
    .navbar-static-top {
        background-color: black;
        padding: 0;
    }
    .navbar-header {
        height: 90px;
        display: flex;
        align-items: flex-start;
    }
    .navbar-brand {
        padding: 0;
        height: auto;
        display: flex;
        align-items: center;
    }
    .navbar-brand img {
        max-height: 120px;
        width: auto;
        object-fit: cover;
        object-position: top;
        clip-path: inset(10px 0 10px 0);
    }
    .dropdown-menu {
        left: 0;
        top: 100%;
        margin-top: 0;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }
    .dropdown {
        position: static;
    }
    .navbar-header .dropdown-menu {
        left: 15px;
        right: auto;
        width: auto;
    }
    .dropdown-item {
        display: block;
        width: 100%;
        padding: .25rem 1.5rem;
        clear: both;
        font-weight: 400;
        color: #212529;
        text-align: inherit;
        white-space: nowrap;
        background-color: transparent;
        border: 0;
    }
    .dropdown-item:hover, .dropdown-item:focus {
        color: #16181b;
        text-decoration: none;
        background-color: #f8f9fa;
    }
    .dropdown-submenu {
        position: relative;
    }

    .dropdown-submenu > .dropdown-menu {
        top: 0;
        left: 100%;
        margin-top: -6px;
        margin-left: -1px;
    }

    .dropdown-submenu:hover > .dropdown-menu {
        display: block;
    }

    .dropdown-submenu > a:after {
        display: block;
        content: " ";
        float: right;
        width: 0;
        height: 0;
        border-color: transparent;
        border-style: solid;
        border-width: 5px 0 5px 5px;
        border-left-color: #cccccc;
        margin-top: 5px;
        margin-right: -10px;
    }
    .dropdown-toggle::after {
        color: white;
    }
    .btn-primary {
        background-color:rgb(10, 143, 54);
        border-color:rgb(10, 172, 51);
    }
    .btn-primary:hover {
        background-color:rgb(5, 107, 22);
        border-color:rgb(5, 56, 7);
    }
    .navbar-top-links {
        position: absolute;
        top: 0;
        right: 0;
        margin: 0;
        padding: 10px;
    }
    .navbar-top-links li {
        display: inline-block;
        vertical-align: middle;
    }
    .badge-danger {
        position: absolute;
        top: -5px;
        right: -5px;
    }
</style>
<script>
$(document).ready(function(){
    $('.dropdown-submenu a.dropdown-toggle').on("click", function(e){
        $(this).next('ul').toggle();
        e.stopPropagation();
        e.preventDefault();
    });

    // Adjust dropdown position
    $('.navbar-brand.dropdown-toggle').on('click', function() {
        var dropdownMenu = $(this).next('.dropdown-menu');
        var navbarHeight = $('.navbar-static-top').outerHeight();
        dropdownMenu.css('top', navbarHeight + 'px');
    });

    // Initialize clock display
    updateClockDisplay();
});

function updateClockDisplay() {
    var now = new Date();
    var hours = now.getHours();
    var minutes = now.getMinutes();
    var seconds = now.getSeconds();
    var meridiem = hours >= 12 ? "PM" : "AM";
    hours = hours % 12;
    hours = hours ? hours : 12;
    minutes = minutes < 10 ? "0" + minutes : minutes;
    seconds = seconds < 10 ? "0" + seconds : seconds;
    var timeString = hours + ":" + minutes + ":" + seconds + " " + meridiem;
    document.getElementById("clockDisplay").textContent = timeString;
    setTimeout(updateClockDisplay, 1000);
}

var clockedIn = false;
var clockInTime;

function toggleClock() {
    var button = document.getElementById("clockButton");
    if (!clockedIn) {
        clockedIn = true;
        clockInTime = new Date();
        button.textContent = "Clock Out";
        button.classList.remove("btn-primary");
        button.classList.add("btn-danger");
        // You can add an AJAX call here to log the clock-in time on the server
    } else {
        clockedIn = false;
        var clockOutTime = new Date();
        var duration = (clockOutTime - clockInTime) / 1000 / 60 / 60; // in hours
        alert("You worked for " + duration.toFixed(2) + " hours");
        button.textContent = "Clock In";
        button.classList.remove("btn-danger");
        button.classList.add("btn-primary");
        // You can add an AJAX call here to log the clock-out time on the server
    }
}
</script>