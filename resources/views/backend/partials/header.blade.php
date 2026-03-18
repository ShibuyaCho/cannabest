<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{setting_by_key('title')}} | @lang('menu.dashboard') </title>

    <link href="{{url('assets/css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{url('assets/font-awesome/css/font-awesome.css')}}" rel="stylesheet">
    <link href="{{url('assets/css/animate.css')}}" rel="stylesheet">
    <link href="{{url('assets/css/style.css')}}" rel="stylesheet">
    <link href="{{url('assets/css/custom.css')}}" rel="stylesheet">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<script>
        window.Laravel = <?php echo json_encode(
            [
            'csrfToken'  => csrf_token(),
            'siteUrlApi' => url('api'),
            'tokenApi'
            ]
        ); ?>
    </script>
    <script src="{{url('assets/jquery-1.11.1.min.js')}}"></script>
    <style>
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #f8f8f8;
        }
        .logo {
            cursor: pointer;
        }
        .nav-menu {
            display: none;
            position: absolute;
            top: 60px;
            right: 20px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .nav-menu.show {
            display: block;
        }
        .nav-menu ul {
            list-style-type: none;
            padding: 0;
        }
        .nav-menu ul li {
            padding: 10px 20px;
        }
        .nav-menu ul li:hover {
            background-color: #f1f1f1;
        }
    </style>

</head>
