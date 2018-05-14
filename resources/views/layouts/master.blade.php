<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <title> @yield('title') - 水滴打卡</title>
        @section('css')
            <link rel="stylesheet" href="{{asset('plugins/bootstrap/dist/css/bootstrap.min.css')}}">
            {{--<link rel="stylesheet" href="{{asset('plugins/font-awesome/css/font-awesome.min.css')}}">--}}
            <link rel="stylesheet" href="{{asset('css/style.css')}}">
            <link rel="stylesheet" href="{{asset('css/iconfont.css')}}">
        @show
    </head>
    <body>
        <!-- WEBSITE PRELOADER-->
         <div class="preloader" style="display: none;">
            <div class="loading">Loading ...</div>
         </div>
        <div class="wrapper">
          @section('header')
                <nav class="navbar bg-color3">
                    <div class="container">
                        <a class="navbar-brand goto" href="{{url('/')}}"><img src="{{asset('img/logo.png')}}" height="" alt="水滴打卡"></a>
                        <button class="round-toggle navbar-toggle menu-collapse-btn collapsed" data-toggle="collapse" data-target=".navMenuCollapse"> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>
                        <div class="collapse navbar-collapse navMenuCollapse">
                            <ul class="nav">
                                <li><a href="#">关于</a></li>
                                <li><a href="#">下载</a></li>
                                <li><a href="#">帮助</a></li>
                            </ul>
                        </div>
                    </div>
                </nav>
              @show

          <div class="bg-color4" style="padding-top:20px;padding-bottom: 20px;">
            @yield('content')
          </div>
          @section('footer')
              <!-- FOOTER -->
                  <footer id="footer" class="bg-color3">
                      <div class="container">
                          <div class="row">
                              <div class="col-md-6 col-md-push-6 text-right">
                                  <ul class="soc-list">
                                      <li><a href="https://www.weibo.com/growu" target="_blank"><i class="icon icon-weibo"></i></a></li>
                                      <li><a  target="_blank" id="wechat" style="background-image:url({{asset('img/wechat.jpg')}})" class="btn" data-toggle="popover" data-placement="top" ><i class="icon icon-weixin"></i></a></li>
                                      {{--<li><a href="#" target="_blank"><i class="icon icon-qq"></i></a></li>--}}
                                  </ul>
                              </div>
                              <div class="col-md-6 col-md-pull-6">
                                  <img class="logo" src="img/icon.png" alt="">
                                  <span class="editContent">© 2015 格吾社区<br>浙ICP备 1234567</span>
                              </div>
                          </div>
                      </div>
                  </footer>
              @show
          @section('js')

            <script type="text/javascript" src="{{asset('plugins/jquery/dist/jquery.min.js')}}"></script>
            <script type="text/javascript" src="{{asset('plugins/bootstrap/dist/js/bootstrap.min.js')}}"></script>
                  <script>
                      var _hmt = _hmt || [];
                      (function() {
                          var hm = document.createElement("script");
                          hm.src = "https://hm.baidu.com/hm.js?04e0f7526f0276c084db5a813a710408";
                          var s = document.getElementsByTagName("script")[0];
                          s.parentNode.insertBefore(hm, s);
                      })();


                  $(document).ready(function(){
                          $('#wechat').popover({
                              trigger : 'hover',//鼠标以上时触发弹出提示框
                              html:true,//开启html 为true的话，data-content里就能放html代码了
                              content:"<img src='{{asset('img/wechat.jpg')}}' height='100'>"
                          });
                      });

                  </script>
              @show
        </div>
    </body>
</html>