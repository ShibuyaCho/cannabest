<div class="row border-bottom">
    <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0; background-color: black; min-height: 90px; padding: 0;">
        <div class="navbar-header" style="padding-left: 15px; height: 90px; display: flex; align-items: center;">
            <a class="navbar-brand" href="{{ route('admin.wholesale.dashboard') }}" style="height: auto; padding: 0;">
                <img alt="Logo" src="{{ asset('uploads/THC.png') }}" style="max-height: 80px; width: auto;" />
            </a>
        </div>
        
        <ul class="nav navbar-top-links navbar-right" style="margin-right: 15px;">
            <li class="dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#" style="color: white;">
                    <i class="fa fa-user fa-fw"></i> {{ Auth::user()->name }} <b class="caret"></b>
                </a>
                <ul class="dropdown-menu">
                    <li>
    <a href="{{ url('/admin/admin/wholesale/dashboard') }}" class="btn btn-primary" style="margin-right: 15px; color: white;">
        <i class="fas fa-tachometer-alt fa-fw"></i> Wholesale Dashboard
    </a>
</li>
                    <li><a href="{{ route('wholesale.brands.index') }}"><i class="fas fa-tags fa-fw"></i> Brands</a></li>
                    <li><a href="{{ route('wholesale.products.index') }}"><i class="fas fa-box-open fa-fw"></i> Products</a></li>
                    <li><a href="{{ route('wholesale.orders.index') }}"><i class="fas fa-shopping-cart fa-fw"></i> Orders</a></li>
                    <li><a href="{{ route('wholesale.settings.edit') }}"><i class="fas fa-cog fa-fw"></i> Wholesale Settings</a></li>
                   <!-- <li><a href="{{ route('admin.wholesale.customize', 'brand-products') }}"><i class="fas fa-edit fa-fw"></i> Customize Brand Products</a></li> -->
                    <li><a href="{{ route('wholesale.profile') }}"><i class="fa fa-user fa-fw"></i> Profile</a></li>
                    <li class="divider"></li>
                    <li>
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt fa-fw"></i> @lang('site.logout')
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>
            </li>
            <li>
                <button type="button" class="btn btn-link" data-toggle="modal" data-target="#inventoryModal" style="color: white;">
                    <i class="fa fa-bell fa-fw"></i>
                    @if(isset($lowInventoryItems) && $lowInventoryItems->count() > 0)
                        <span class="badge badge-danger">{{ $lowInventoryItems->count() }}</span>
                    @endif
                </button>
            </li>
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
        align-items: center;
    }
    .navbar-brand {
        padding: 0;
        height: auto;
    }
    .navbar-brand img {
        max-height: 80px;
        width: auto;
    }
    .navbar-top-links {
        margin-right: 15px;
    }
    .navbar-top-links li {
        display: inline-block;
    }
    .navbar-top-links .dropdown-menu li {
        display: block;
    }
    .navbar-top-links .dropdown-menu li:last-child {
        margin-right: 0;
    }
    .navbar-top-links .dropdown-menu li a {
        padding: 3px 20px;
        min-height: 0;
    }
    .navbar-top-links .dropdown-menu li a div {
        white-space: normal;
    }
    .navbar-top-links .dropdown-menu li a:hover {
        background-color: #f8f9fa;
    }
    .btn-primary {
        background-color: rgb(10, 143, 54);
        border-color: rgb(10, 172, 51);
    }
    .btn-primary:hover {
        background-color: rgb(5, 107, 22);
        border-color: rgb(5, 56, 7);
        font-color: black;
    }
 
</style>
<script>
$(document).ready(function(){
    $('.dropdown-toggle').dropdown();
});
</script>