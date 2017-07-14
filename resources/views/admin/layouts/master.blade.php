<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>keepdays - @yield('title')</title>
    @section('css')
        <link rel="stylesheet" href="{{asset('plugins/bootstrap/dist/css/bootstrap.min.css')}}">
        {{--<link rel="stylesheet" href="{{asset('plugins/font-awesome/css/font-awesome.min.css')}}">--}}
        <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">

        <link rel="stylesheet" href="{{asset('css/app.css')}}">
        @show
    <!--[if lt IE 9]>
        <script src="//oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="//oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
</head>
<body>
{{-- Navigation Bar --}}
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-menu">
                <span class="sr-only">Toggle Navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">{{ config('blog.title') }}</a>
        </div>
        <div class="collapse navbar-collapse" id="navbar-menu">
            @include('admin.partials.navbar')
        </div>
    </div>
</nav>

@yield('content')

<script type="text/javascript" src="{{asset('plugins/jquery/dist/jquery.min.js')}}"></script>
<script type="text/javascript" src="{{asset('plugins/bootstrap/dist/js/bootstrap.min.js')}}"></script>
<script type="text/javascript" src="{{asset('plugins/toastr/toastr.min.js')}}"></script>

@yield('scripts')


</body>
</html>