@extends('layouts/auth')


@section('title', "登录")

@section('content')
	
	<div class="container">
		<div class="block-center mt-xxl wd-xl">
			<div class="panel panel-dark">
				<div class="panel-heading text-center text-md p-lg">
					<img src="{{asset('img/logo.png')}}" alt="Image" class="block-center img-rounded">
				</div>
				<div class="panel-body">
					<form>
						<div class="form-group">
							<input type="text" class="form-control" placeholder="邮箱" />
						</div>
						<div class="form-group">
							<input type="password" class="form-control" placeholder="密码" />
						</div>
						<div class="form-group">
							<label class="checkbox ml-lg">
				                <input type="checkbox" value="remember"> 记住我的状态
				                <span class="pull-right">
				                    <a href="#" class="text-green-light"> 忘记密码?</a>
				                </span>
	           				 </label>
           				 </div>
						<button type="submit" class="btn btn-block btn-green mt-lg text-md">登录</button>
						<div class="text-center mt-lg">
			                还没有账号？
			                <a class="text-green-light " href="registration.html">
			                    立即注册
			                </a>
			            </div>
					</form>
				</div>
			</div>
			<p class="text-center">©2016 - keepdays</p>
		</div>
	</div>
@stop

