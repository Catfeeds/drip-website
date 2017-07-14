@extends('layouts/auth')


@section('title', "忘记密码")

@section('content')

    <div class="container">
        <div class="block-center mt-xxl wd-xl">
            <div class="panel panel-dark">
                <div class="panel-heading text-center text-md p-lg">
                    <img src="{{asset('img/logo.png')}}" alt="Image" class="block-center img-rounded">
                </div>
                <div class="panel-body" style="display:none;">
                    <p class="text-center pv">忘记密码</p>
                    <form>
                        <div class="form-group">
                            <input type="text" class="form-control" placeholder="请输入你注册的邮箱" />
                        </div>

                        <button type="submit" class="btn btn-block btn-green mt-lg text-md">重置密码</button>
                        <div class="text-center mt-lg">
                            如无法通过手机或邮箱找回请发送邮件至 <a href="mailto:help@keepdays.com">help@keepdays.com</a> 寻求帮助
                        </div>
                    </form>
                </div>

                <div class="panel-body">
                    <p class="text-center pv">重置密码邮件已发送至邮箱 ccnuzxg@163.com，有效期为24小时</p>
                    <form>
                        <button type="submit" class="btn btn-block btn-green mt-lg text-md" id="resend-btn">重新发送激活邮件</button>
                    </form>
                </div>
            </div>
            <p class="text-center">©2016 - keepdays</p>
        </div>
    </div>
@stop

