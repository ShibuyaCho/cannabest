@include('backend.partials.header')
 
<body>
    <div class="login-bg-container">
        <div class="login-bg"></div>
    </div>

    <div id="wrapper">
        @include('backend.partials.wholesaleTopbar')
    
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
                @stack('scripts')
            <break>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="{{ url('assets/js/plugins/metisMenu/jquery.metisMenu.js') }}"></script>
    <script src="{{ url('assets/js/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
    <script src="{{ url('assets/js/inspinia.js') }}"></script>
    <script src="{{ url('assets/js/plugins/pace/pace.min.js') }}"></script>
    <script src="{{ url('assets/js/plugins/peity/jquery.peity.min.js') }}"></script>
    <script src="{{ url('assets/js/demo/peity-demo.js') }}"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css">

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
    </style>

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

