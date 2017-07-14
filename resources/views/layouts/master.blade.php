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
        <!-- WEBSITE PRELOADER-->
         <div class="preloader" style="display: none;">
            <div class="loading">Loading ...</div>
         </div>
        <div class="wrapper">
          @section('header')
              @if(Request::url() === '/')
                <header class="navbar navbar-fixed-top transparent" id="header">
              @else
                <header class="navbar" id="header">
              @endif
                <div class="container">
                  <!-- Brand and toggle get grouped for better mobile display -->
                  <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                      <span class="sr-only">展开</span>
                      <span class="icon-bar"></span>
                      <span class="icon-bar"></span>
                      <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#"></a>
                  </div>

                  <!-- Collect the nav links, forms, and other content for toggling -->
                  <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav">
                      <li class=""><a href="goal">目标 <span class="sr-only">(current)</span></a></li>
                      <!-- <li><a href="#">计划</a></li>
                       <li><a href="#">小组</a></li> -->
                    </ul>
                    <!-- <form class="navbar-form navbar-left" role="search">
                      <div class="form-group">
                        <input type="text" class="form-control" placeholder="Search">
                      </div>
                      <button type="submit" class="btn btn-default">Submit</button>
                    </form> -->
                    <ul class="nav navbar-nav navbar-right">
                      <li>
                      {{--<a href="{{ url('account/login') }}" class="btn btn-sm btn-green btn-outline navbar-btn">登录</a></li>--}}
                      {{--<li><a href="{{ url('account/register') }}" class="btn btn-sm btn-dark btn-outline ml-sm navbar-btn">注册</a></li>--}}

                      <!-- <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><img src="assets/img"> <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                          <li><a href="#">控制台</a></li>
                          <li><a href="#">账号设置</a></li>
                          <li role="separator" class="divider"></li>
                          <li><a href="#">注销</a></li>
                        </ul>
                      </li> -->
                    </ul>
                  </div><!-- /.navbar-collapse -->
                </div><!-- /.container-fluid -->
              </header>
          @show

          <section id="content">
            @yield('content')
          </section>
          @section('footer')
              <footer class="text-center p-xl">
                  <div class="container">
                      <div class="row">
                          <div class="copyright">
                            © keepdays Inc, 2016 <br>浙ICP备 13046642
                          </div>
                        </div>
                  </div>
              </footer>
          @show
          @section('js')
          <script>
              var _hmt = _hmt || [];
              (function() {
                  var hm = document.createElement("script");
                  hm.src = "https://hm.baidu.com/hm.js?2eddaff584dff6d6ddd51602807fc4d7";
                  var s = document.getElementsByTagName("script")[0];
                  s.parentNode.insertBefore(hm, s);
              })();
          </script>
            <script type="text/javascript" src="{{asset('plugins/jquery/dist/jquery.min.js')}}"></script>
            <script type="text/javascript" src="{{asset('js/app.js')}}"></script>
          @show
        </div>
    </body>
</html>