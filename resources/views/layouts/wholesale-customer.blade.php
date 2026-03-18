<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Wholesale Customer Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            overflow-x: hidden;
        }
        .login-bg-container {
            position: fixed;
            top: -15%; left: -15%;
            width: 130%; height: 130%;
            z-index: -1;
            overflow: hidden;
            background-color: #000;
        }
        .login-bg {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.3);
            background-image: url('{{ asset("uploads/THC2.png") }}');
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            opacity: 0.5;
        }
        #wrapper {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
        #page-wrapper {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            background-color: transparent;
            margin: 0 !important;
            padding: 20px !important;
            width: 100% !important;
        }
        .content-card {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .page-content {
            flex: 1 0 auto;
            width: 100%;
        }
        .footer {
            flex-shrink: 0;
            margin-top: auto;
            padding: 10px 0;
            border-top: 1px solid #e7eaec;
            width: 100%;
        }
        body {
            padding-left: 0 !important;
            margin-left: 0 !important;
        }
        .navbar-static-top {
            width: 100% !important;
            background-color: rgba(0, 0, 0, 0.7) !important;
        }
        .navbar-static-side {
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            overflow-y: auto;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
        }
        .center-img {
            display: block;
            margin: 0 auto;
        }
        .nav > li > a {
            color: white;
        }
        .nav > li.active {
            background-color: rgba(255, 255, 255, 0.1);
        }
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #343a40;
        }
        .card {
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
    @stack('styles')
</head>
<body>
    @include('backend.partials.header')
    <div class="login-bg-container">
        <div class="login-bg"></div>
    </div>

    <div id="wrapper">
        @include('backend.partials.wholesaleCustomerTopbar')
    
        <div id="page-wrapper" class="gray-bg">
            <div class="content-card">
                @include('backend.partials.notification')

                @php
                    $content = $__env->yieldContent('content');
                    $parsedContent = parseEmojis($content);
                @endphp

                <div class="page-content flex-grow-1">
                    {!! $parsedContent !!}
                </div>
                
                @yield('scripts')
                <div class="footer mt-auto">
                    <div class="pull-right">
                        {{-- Optionally parse emojis here if needed --}}
                    </div>
                    <div>
                        <strong>Copyright</strong>  &copy; {{ date("Y") }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
   <!-- Add this line before other scripts in the wholesale-customer layout -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css">

    <script>
        $(document).mousemove(function(e) {
            var mouseX = e.pageX;
            var mouseY = e.pageY;
            var windowWidth = $(window).width();
            var windowHeight = $(window).height();

            var moveX = (mouseX - windowWidth / 2) / windowWidth * 60;
            var moveY = (mouseY - windowHeight / 2) / windowHeight * 60;

            $('.login-bg-container').css('transform', 'translate(' + moveX + 'px, ' + moveY + 'px)');
            $('.login-bg').css('transform', 'translate(' + -moveX * 1.2 + 'px, ' + -moveY * 1.2 + 'px)');
        });
    </script>
</body>
</html>