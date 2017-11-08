@extends('layouts/master')

@section('title', '水滴打卡')

@section('css')
    @parent
    <link rel="stylesheet" href="{{asset('css/cloud.css')}}">
@stop

@section('content')
    <div class="container third-padding">
        <div class="row">

        <div class="col-md-7">
            @if (count($articles) > 0)

                @foreach ($articles as $article)
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <a href="{!! url('article',[$article->id]) !!}"><h3 class="panel-title">{!! $article->title !!}</h3></a>
                        </div>
                        <div class="panel-body">
                            {!! $article->content !!}
                        </div>
                    </div>
                @endforeach


            @endif
        </div>

        <div class="col-md-5">
        </div>

    </div>
    </div>
    <!-- INTRO RIGHT IMG BLOCK -->
    {{--<header id="intro-right-img" class="intro-block bg-color1 dark-bg cover-bg" style="background-image:url({{asset('img/bg19.jpg')}})" data-selector="header">--}}
        {{--<div class="container">--}}
            {{--<div class="row">--}}
                {{--<div class="col-md-6 col-md-push-6">--}}
                    {{--<div class="logo">--}}
                    {{--<img src="{{asset('img/icon.png')}}" alt="水滴打卡" height="120" data-selector="img">--}}
                    {{--</div>--}}
                    {{--<div class="slogan">--}}
                        {{--<h2 data-selector="h2">水滴打卡</h2>--}}
                        {{--<p data-selector="p">一款<strong>习惯养成</strong>和<strong>目标管理</strong>工具</p>--}}
                    {{--</div>--}}

                    {{--<div class="">--}}
                        {{--<img src="{{asset('img/qrcode.png')}}" alt="水滴打卡" height="120" data-selector="img">--}}
                    {{--</div>--}}
                    {{--<a class="download-btn" href="https://itunes.apple.com/cn/app/id1255579223" data-selector="a.btn, a.download-btn, button.btn, a.goto"> <i class="icon icon-apple" data-selector=".icon"></i><b>App Store</b>下载</a>--}}
                    {{--<a class="download-btn" href="#" data-selector="a.btn, a.download-btn, button.btn, a.goto"><i class="icon icon-android" data-selector=".icon"></i><b>Google play</b>下载</a>--}}
                {{--</div>--}}
                {{--<div class="col-md-4 col-md-pull-6">--}}
                    {{--<img src="{{asset('img/screen-3.png')}}" class="screen" alt="" data-selector="img">--}}
                {{--</div>--}}
            {{--</div>--}}
        {{--</div>--}}
    {{--</header>--}}



@stop

@section('js')
    @parent
@stop
