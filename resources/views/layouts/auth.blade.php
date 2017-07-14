<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>keepdays - @yield('title')</title>
        @section('css')
            <link rel="stylesheet" href="{{asset('plugins/bootstrap/dist/css/bootstrap.min.css')}}">
            <link rel="stylesheet" href="{{asset('plugins/font-awesome/css/font-awesome.min.css')}}">
            <link rel="stylesheet" href="{{asset('css/app.css')}}">
        @show
    </head>
    <body>
        <section id="content">
          @yield('content')
        </section>
    </body>
</html>