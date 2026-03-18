<!-- Start: Header Area -->
<header class="header-area">
    <div class="container clearfix">
        <div class="row">
            <!-- Start: Logo Area -->
            <div class="logo-sec">
                <a class="logo" href="{{ url('/thcfg') }}">
                    <img src="{{ asset('assets/frontend/img/logo.png') }}" alt="Logo" style="height: px; max-height: 100px;">
                </a>
                <a class="togg-navi">
                    <span class="togg-text-menu"><i class="fa fa-bars"></i></span>
                </a>
            </div>
            <!-- End: Logo Area -->
            <div class="header-right-sec">
                <nav class="nav-area">
                    <ul class="menu-main">
                        <li><a href="{{ url('about') }}">About</a></li>
                        <li><a href="{{ url('our-menu') }}">Menu</a></li>
                        @auth
                            <li>
                                <a href="{{ url('/thcfg') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    Logout
                                </a>
                            </li>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        @else
                            <li><a href="#" id="openLoginModal">Login</a></li>
                        @endauth
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</header>
<!-- End: Header Area -->

<!-- Start: Login Modal -->
<div id="loginModal" role="dialog" aria-modal="true" aria-labelledby="loginModalLabel" style="display:none; position:fixed; top:60px; right:20px; background:#fff; border:1px solid #ccc; z-index:1000; width:90%; max-width:400px; padding:20px; box-shadow:0 0 10px rgba(0,0,0,0.3);">
    <button id="closeLoginModal" aria-label="Close login modal" style="position:absolute; top:5px; right:10px; cursor:pointer; font-size:20px; background:none; border:none;">&times;</button>
    <h3 id="loginModalLabel" style="margin-top:0;">Login</h3>
    <form action="{{ route('login') }}" method="POST">
        @csrf
        <div style="margin-bottom:10px;">
            <label style="display:block;">Email:</label>
            <input type="email" name="email" required style="width:100%; padding:5px;">
        </div>
        <div style="margin-bottom:10px;">
            <label style="display:block;">Password:</label>
            <input type="password" name="password" required style="width:100%; padding:5px;">
        </div>
        <button type="submit" style="width:100%; padding:8px; background:#2ecc71; color:#fff; border:none; cursor:pointer;">Login</button>
    </form>
    <hr style="margin:15px 0;">
    <!-- Button to open the registration modal -->
    <button id="openNewCustomerModal" style="width:100%; padding:8px; background:#000; color:#fff; border:none; cursor:pointer;">Create New Customer Account</button>
</div>
<!-- End: Login Modal -->

<!-- Start: New Customer Registration Modal -->
<div id="newCustomerModal" role="dialog" aria-modal="true" aria-labelledby="newCustomerModalLabel" style="display:none; position:fixed; top:60px; right:20px; background:#fff; border:1px solid #ccc; z-index:1100; width:90%; max-width:400px; padding:20px; box-shadow:0 0 10px rgba(0,0,0,0.3);">
    <button id="closeNewCustomerModal" aria-label="Close registration modal" style="position:absolute; top:5px; right:10px; cursor:pointer; font-size:20px; background:none; border:none;">&times;</button>
    <h3 id="newCustomerModalLabel" style="margin-top:0;">Create New Account</h3>
    <!-- This form posts to the "customers" route (CustomerController@store) -->
    <form action="{{ url('customers') }}" method="POST">
        @csrf
        <div style="margin-bottom:10px;">
            <label style="display:block;">Name:</label>
            <input type="text" name="name" required style="width:100%; padding:5px;">
        </div>
        <div style="margin-bottom:10px;">
            <label style="display:block;">Email:</label>
            <input type="email" name="email" required style="width:100%; padding:5px;">
        </div>
        <div style="margin-bottom:10px;">
            <label style="display:block;">Password:</label>
            <input type="password" name="password" required style="width:100%; padding:5px;">
        </div>
        <div style="margin-bottom:10px;">
            <label style="display:block;">Confirm Password:</label>
            <input type="password" name="password_confirmation" required style="width:100%; padding:5px;">
        </div>
        <button type="submit" style="width:100%; padding:8px; background:#2ecc71; color:#fff; border:none; cursor:pointer;">Create Account</button>
    </form>
</div>

<!-- End: New Customer Registration Modal -->
<!--<link href="{{ asset('assets/frontend/css/style.css') }}" rel="stylesheet">-->
<!-- Inline JavaScript to Toggle the Modals -->
<script>
    // Open login modal when clicking "Login" in header.
    document.getElementById('openLoginModal').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('loginModal').style.display = 'block';
    });

    // Close login modal.
    document.getElementById('closeLoginModal').addEventListener('click', function() {
        document.getElementById('loginModal').style.display = 'none';
    });

    // Open new customer modal when clicking the "Create New Customer Account" button.
    document.getElementById('openNewCustomerModal').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('loginModal').style.display = 'none';
        document.getElementById('newCustomerModal').style.display = 'block';
    });

    // Close new customer modal.
    document.getElementById('closeNewCustomerModal').addEventListener('click', function() {
        document.getElementById('newCustomerModal').style.display = 'none';
    });

    // Optional: Close modals if clicking outside them.
    window.addEventListener('click', function(e) {
        var loginModal = document.getElementById('loginModal');
        var newCustomerModal = document.getElementById('newCustomerModal');
        if (e.target == loginModal) {
            loginModal.style.display = 'none';
        }
        if (e.target == newCustomerModal) {
            newCustomerModal.style.display = 'none';
        }
    });
</script>
