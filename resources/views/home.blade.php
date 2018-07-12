@extends('layouts/master')
 
@section('title', '一款习惯养成和目标管理工具')

@section('css')
	@parent
	 <link rel="stylesheet" href="{{asset('css/cloud.css')}}">
@stop

@section('content')
		<!-- INTRO RIGHT IMG BLOCK -->
		<header id="intro-right-img" class="intro-block bg-color1 dark-bg cover-bg" style="background-image:url({{asset('img/bg19.jpg')}})" data-selector="header">
			<div class="container">
				<div class="row">
					<div class="col-md-6 col-md-push-6">
						{{--<div class="logo">--}}
							{{--<img src="{{asset('img/icon.png')}}" alt="水滴打卡" height="120" data-selector="img">--}}
						{{--</div>--}}
						<div class="slogan">
							<h2 data-selector="h2">水滴打卡</h2>
							<p data-selector="p">一款<strong>习惯养成</strong>和<strong>目标管理</strong>工具</p>
							</div>

						{{--<div class="">--}}
							{{--<img src="{{asset('img/qrcode.png')}}" alt="水滴打卡" height="120" data-selector="img">--}}
						{{--</div>--}}
						<a class="download-btn" href="https://itunes.apple.com/cn/app/id1255579223" target="_blank" data-selector="a.btn, a.download-btn, button.btn, a.goto" alt="水滴打卡苹果下载地址"> <i class="icon icon-apple" data-selector=".icon"></i><b>AppStore</b>下载</a>
						<a class="download-btn" href="http://a.app.qq.com/o/simple.jsp?pkgname=me.growu.drip" target="_blank" data-selector="a.btn, a.download-btn, button.btn, a.goto"  alt="水滴打卡安卓下载地址"><i class="icon icon-msnui-logo-android" data-selector=".icon"></i><b>Android</b>下载</a>
					</div>
					<div class="col-md-4 col-md-pull-6">
						<img src="{{asset('img/screen-3.png')}}" class="screen" alt="" data-selector="img">
					</div>
				</div>
			</div>
		</header>

		<!-- COUNTER 4 COL BLOCK -->
		<section class="facts-block bg-color2 dark-bg text-center">
			<div class="container">
				<div class="row">
					<div class="col-md-4">
						<i class="icon icon-download"></i>
						<h3 class="timer text-xxl">{{number_format($count->all_reg_num)}}</h3>
						<h4>梦想家</h4>
					</div>
					<div class="col-md-4">
						<i class="icon icon-wallet2"></i>
						<h3 class="timer text-xxl">{{number_format($count->all_goal_num)}}</h3>
						<h4>目标</h4>
					</div>
					<div class="col-md-4">
						<i class="icon icon-heart2"></i>
						<h3 class="timer text-xxl">{{number_format($count->all_checkin_count)}}</h3>
						<h4>打卡</h4>
					</div>
				</div>
			</div>
		</section>
@stop

@section('js')
	@parent
@stop
