<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
   <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' 'unsafe-eval' https: data: http://localhost:8000">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Customize</title>

    <!-- Styles -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        #app {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .customize-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div id="app">
        @include('backend.partials.header')
        @include('backend.partials.wholesaleTopbar')

        <div class="customize-content">
            @yield('content')
        </div>
    </div>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>