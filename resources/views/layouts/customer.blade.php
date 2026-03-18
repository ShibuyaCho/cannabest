


    <div id="wrapper">
        <div id="page-wrapper" class="gray-bg">
       
           
            @include('frontend.cheader')
            @php
                // Capture the content of the 'content' section
                $content = $__env->yieldContent('content');
                // Parse any emoji shortcodes (like :weed:) in the content
                $parsedContent = parseEmojis($content);
            @endphp

            <div class="page-content">
                {!! $parsedContent !!}
            </div>
            <div class="footer">
                <div class="pull-right"></div>
                <div>
                    <strong>Copyright</strong> &copy; {{ date('Y') }}
                </div>
            </div>

        </div> <!-- closes page-wrapper -->
    </div> <!-- closes wrapper -->

    <!-- JavaScript includes -->
    <script src="{{ url('assets/js/jquery.min.js') }}"></script> <!-- Don't forget jQuery -->
    <script src="{{ url('assets/js/bootstrap.min.js') }}"></script>
    <script src="{{ url('assets/js/plugins/metisMenu/jquery.metisMenu.js') }}"></script>
    <script src="{{ url('assets/js/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
    <script src="{{ url('assets/js/inspinia.js') }}"></script>
    <script src="{{ url('assets/js/plugins/pace/pace.min.js') }}"></script>
    <script src="{{ url('assets/js/plugins/peity/jquery.peity.min.js') }}"></script>
    <script src="{{ url('assets/js/demo/peity-demo.js') }}"></script>
</body>
</html>
