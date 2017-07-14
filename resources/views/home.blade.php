@extends('layouts/master')
 
@section('title', '坚持每一天')

@section('css')
	@parent
	 <link rel="stylesheet" href="{{asset('css/cloud.css')}}">
@stop

@section('content')

		<div class="intro-header bg-dark bg-cover text-center" style="background-color: #000000;padding-top:200px;padding-bottom: 150px;">
	        <div class="container">
	            <div class="row">
	                <div class="col-lg-12">
	                    <div class="intro-message">
	                        <h1 class="p-xl m-xl text-white text-xs">坚持每一天<br><br>帮助你成就目标和梦想</h1>
	                        <ul class="list-inline intro-social-buttons p-xl">
	                            {{--<li>--}}
	                                {{--<a href="" class="btn btn-danger btn-lg"><span class="network-name">下载App</span></a>--}}
	                            {{--</li>--}}
	                            {{--<li>--}}
	                                {{--<a href="" class="btn btn-green btn-lg"> <span class="network-name">进入控制台</span></a>--}}
	                            {{--</li>--}}
	                        </ul>
	                    </div>
	                </div>
	            </div>
	        </div>
	        <!-- /.container -->
	    </div>
		<!-- COUNTER 4 COL BLOCK -->
		<section class="bg-gray-dark text-center p-xl">
			<div class="container">
				<div class="row">
					<div class="col-md-3">
						<i class="icon icon-download"></i>
						<h3 class="timer text-xxl">353</h3>
						<h4>目标</h4>
					</div>
					<div class="col-md-3">
						<i class="icon icon-wallet2"></i>
						<h3 class="timer text-xxl">834</h3>
						<h4>计划</h4>
					</div>
					<div class="col-md-3">
						<i class="icon icon-target2"></i>
						<h3 class="timer text-xxl">45</h3>
						<h4>小组</h4>
					</div>
					<div class="col-md-3">
						<i class="icon icon-heart2"></i>
						<h3 class="timer text-xxl">20,340</h3>
						<h4>梦想家</h4>
					</div>
				</div>
			</div>
		</section>
@stop

@section('js')
	@parent
	<script type="text/javascript" src="{{asset('js/home.js')}}"></script>
@stop
