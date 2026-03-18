<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css">


<nav class="navbar-default navbar-static-side" role="navigation">
        <div class="sidebar-collapse">
            <ul class="nav metismenu" id="side-menu">
                <li class="nav-header">
                    <div class="dropdown profile-element"> <span>
                            <img alt="image" width="110" class="" src="{{asset('uploads/logo_1.png')}}" /> <br>
                             </span>
                        <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                            <span class="clear"> <span class="block m-t-xs"> <strong class="font-bold">{{Auth::user()->name}}</strong>
                             </span> <span class="text-muted text-xs block"><b class="caret"></b></span> </span> </a>
                        <ul class="dropdown-menu animated fadeInRight m-t-xs">
                            <li><a href="{{url('settings/profile')}}">@lang('site.profile')</a></li>
                            
                            <li><a href="{{ url('/logout') }}">@lang('site.logout')</a></li>
                        </ul>
                    </div>
                    <div class="logo-element">
                        
                    </div>
                </li>
				

                @if(role_permission(35))
				 <li @if(Request::segment(1) == "admin" or Request::segment(1) == "dashboard") class="active" @endif><a href="{{ url('dashboard') }}"><i class="fa fa-th-large"></i>  <span class="nav-label">@lang('site.dashboard')</span></a></li>
				
                 @endif
                
                @if(role_permission(1))
                

				 <li @if(Request::segment(1) == "sales" and Request::segment(2) == "create") class="active" @endif><a href="{{ url('sales/create') }}"><i class="fas fa-cash-register"></i>  <span class="nav-label">@lang('site.cashier')</span></a></li>
                 @endif

                
                
                @if(role_permission(1))
                

                <li @if(Request::segment(1) == "sales" and Request::segment(2) == "") class="active" @endif><a href="{{ url('sales') }}"><i class="fa fa-th-large"></i> @lang('site.sales')</a></li>

				  <!-- <li  @if((Request::segment(1) == "orders" or Request::segment(1) == "sales") and Request::segment(2) == "") class="active" @endif>
                    <a href="#"><i class="fa fa-th-large"></i> <span class="nav-label">@lang('site.sales')</span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level collapse">
                        
                         <li @if(Request::segment(1) == "orders" ) class="active" @endif><a href="{{ url('orders') }}">@lang('site.order_sales')</a></li>
                    </ul>
                </li> -->
				
				

                @endif
                
                @if(role_permission(8))
                

                    <?php /* <li><a href="{{ url('customers') }}"> <i class="fa fa-users"></i> <span class="nav-label">Customers <span></a></li>
                    <li><a href="{{ url('suppliers') }}"> <i class="fa fa-users"></i> <span class="nav-label">Suppliers <span></a></li> */ ?>
					
                <li @if((Request::segment(1) == "categories" or Request::segment(1) == "products") and Request::segment(2) == "") class="active" @endif>
                    <a href="#"><i class="fas fa-poll"></i> <span class="nav-label">@lang('site.products')</span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level collapse">
                        <li @if(Request::segment(1) == "categories" and Request::segment(2) == "") class="active" @endif><a href="{{ url('categories') }}">@lang('site.categories')</a></li>
                        <li @if(Request::segment(1) == "products" and Request::segment(2) == "") class="active" @endif><a href="{{ url('products') }}">@lang('site.products')</a></li>
                       
                    </ul>
                </li>
				

@endif
                
                @if(role_permission(17))
                
                 
                <li <?php if(Request::segment(1) == "reports") { ?>  class="active"; <?php  } ?>>
                    <a href="#"><i class="fa fa-th-large"></i> <span class="nav-label">@lang('site.reporting')</span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level collapse">
                        <li @if((Request::segment(1) == "reports" and Request::segment(2) == "sales")) class="active" @endif><a href="{{ url('reports/sales') }}">@lang('site.sales_report')</a></li>
                        <li @if((Request::segment(1) == "reports" and Request::segment(2) == "sales_by_products")) class="active" @endif><a href="{{ url('reports/sales_by_products') }}">@lang('site.product_by_sales')</a></li>
                        <li @if((Request::segment(1) == "reports" and Request::segment(2) == "graphs")) class="active" @endif><a href="{{ url('reports/graphs') }}">@lang('site.graphs')</a></li>
                        <li @if((Request::segment(1) == "reports" and Request::segment(2) == "expenses")) class="active" @endif><a href="{{ url('reports/expenses') }}">@lang('site.expense_report')</a></li>
                        <li @if((Request::segment(1) == "reports" and Request::segment(2) == "staff_log")) class="active" @endif><a href="{{ url('reports/staff_log') }}">@lang('site.staff_logs')</a></li>
						
						<li @if((Request::segment(1) == "reports" and Request::segment(2) == "staff_sold")) class="active" @endif><a href="{{ url('reports/staff_sold') }}">@lang('site.sales_manager_sold')</a></li>
						
                        <li @if((Request::segment(1) == "reports" and Request::segment(2) == "profit_by_purchase")) class="active" @endif><a href="{{ url('reports/profit_by_purchase') }}">Profit By Purchase</a></li>
						<li @if((Request::segment(1) == "reports" and Request::segment(2) == "profit_by_product")) class="active" @endif><a href="{{ url('reports/profit_by_product') }}">Profit By Product</a></li>
						
                    </ul>
                </li>

                @endif
                
                @if(role_permission(15))
                
				
				 <!-- <li @if(Request::segment(2) == "general") class="active" @endif>
                    <a href="{{ url('settings/general') }}"><i class="fa fa-gear"></i> <span class="nav-label"> @lang('site.settings')</span></a>
                </li> -->
				
                 <li @if(Request::segment(1) == "settings" and ((Request::segment(2) == "general" or Request::segment(2) == "html" ))) class="active" @endif>
                    <a href="#"><i class="fa fa-gear"></i> <span class="nav-label"> Settings </span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level collapse">
                        <li @if(Request::segment(1) == "settings" and (Request::segment(2) == "general" )) class="active" @endif><a href="{{ url('settings/general') }}">@lang('site.settings')</a></li>
                        
                    </ul>
                </li>

                

                

                @endif

                @if(role_permission(20))

                <li  @if((Request::segment(1) == "users" or Request::segment(1) == "customers") and Request::segment(2) == "") class="active" @endif>
                    <a href="#"><i class="fa fa-users"></i> <span class="nav-label">User & Customers</span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level collapse">
                        <li @if(Request::segment(1) == "customers" and Request::segment(2) == "") class="active" @endif><a href="{{ url('customers') }}">Customers</a></li>
                        <li @if(Request::segment(1) == "users" ) class="active" @endif><a href="{{ url('users') }}">Users</a></li>  
                
                       
                    </ul>
                </li>
                @endif
                


               
                
               
                @if(role_permission(34))
                
                <li  @if((Request::segment(1) == "update_inventory" or Request::segment(1) == "update_werehouse_inventory" or Request::segment(1) == "quantity_alerts" or Request::segment(1) == "inventory" or Request::segment(1) == "min_quantity_alert") and Request::segment(2) == "") class="active" @endif>
                    <a href="#"><i class="fa fa-database"></i> <span class="nav-label">Inventory</span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level collapse">
                         
                        <li @if(Request::segment(1) == "update_inventory" and Request::segment(2) == "") class="active" @endif><a href="{{ url('update_inventory') }}">Update Inventory</a></li>
                        <li @if(Request::segment(1) == "inventory" ) class="active" @endif><a href="{{ url('inventory') }}">Inventory History</a></li>
                        <li @if(Request::segment(1) == "min_quantity_alert" ) class="active" @endif><a href="{{ url('min_quantity_alert') }}">Update Quantity Alert</a></li>
                        <li @if(Request::segment(1) == "quantity_alerts" ) class="active" @endif><a href="{{ url('quantity_alerts') }}"> Quantity Alerts</a></li>
                       
                    </ul>
                </li>
                @endif


                @if(role_permission(16))
				
                <li @if((Request::segment(1) == "settings" or Request::segment(1) == "sliders" or Request::segment(1) == "pages") and ((Request::segment(2) == "homepage" or Request::segment(2) == "menu_management"  or Request::segment(2) == ""))) class="active" @endif>
                    <a href="#"><i class="fa fa-th-large"></i> <span class="nav-label">@lang('site.frontend_website')</span> <span class="fa arrow"></span></a>
                    <ul class="nav nav-second-level collapse">
                        <li @if((Request::segment(2) == "homepage" )) class="active" @endif><a href="{{ url('settings/homepage') }}">@lang('site.homepage_setting')</a></li>
                        <li @if((Request::segment(1) == "sliders" )) class="active" @endif><a href="{{ url('sliders') }}">@lang('site.sliders')</a></li>
                        <li @if((Request::segment(1) == "pages" )) class="active" @endif><a href="{{ url('pages') }}">@lang('site.pages')</a></li>
                        <?php /* 
                        <li @if((Request::segment(2) == "menu_management" )) class="active" @endif><a href="{{ url('settings/menu_management') }}">@lang('site.menu_management')</a></li>
                        */ ?>
                       
                    </ul>
                </li>
                @endif

           


                
                @if(role_permission(18))
				
				<li @if(Request::segment(1) == "roles") class="active" @endif>
                    <a href="{{ url('roles') }}"><i class="fas fa-people-arrows"></i><span class="nav-label"> @lang('site.roles')</span></a>
                </li>

                @endif
                
             
                
                @if(role_permission(21))
				<li @if((Request::segment(2) == "profile" )) class="active" @endif>
                    <a href="{{url('settings/profile')}}"><i class="fa fa-user"></i> <span class="nav-label"> @lang('site.profile') </span></a>
                </li>
				@endif
            
                <li>
                    <a href="{{ url('clear_cache') }}" target="_blank"><i class="fa fa-eye"></i> <span class="nav-label"> Clear Cache</span></a>
                </li>
                <li>
                    <a href="{{ url('logout') }}"><i class="fas fa-sign-out-alt"></i>  <span class="nav-label"> @lang('site.logout') </span></a>
                </li>
                
            </ul>

        </div>
    </nav>

