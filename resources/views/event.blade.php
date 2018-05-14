@extends('layouts/master')

@section('title', $event['user']['nickname']."的打卡动态")

@section('css')
    @parent
    <link rel="stylesheet" href="{{asset('css/cloud.css')}}">
@stop

@section('content')
    <div class="" style="max-width: 768px;margin:0 auto;">
    <section class="zero-padding" style="background-color:#fff;padding-left: 10px;
    padding-right: 10px;">
            <div class="row">
                <div class="media" style="padding: 20px;">
                    <div class="media-left media-middle">
                        <a href="#">
                            <img class="media-object" src="{{$event['user']['avatar_url']}}" style="width: 64px;height: 64px;border-radius: 50%;" alt="...">
                        </a>
                    </div>
                    <div class="media-body" style="text-align: left;">
                        <h4 class="media-heading">{{$event['user']['nickname']}}</h4>
                        <p>{{$event['created_at']}}</p>
                    </div>
                </div>
                <div class="post">
                    <div class="post-media">
                        <a href="#"><img src="{{$event['attachs'][0]['url']}}}" class="img-responsive"></a>
                    </div>
                    <div class="post-content">
                        {!! $event['content'] !!}
                    </div>
            </div>
            </div>
    </section>

    <section id="items-3col-2" class="bg-color3 cover-bg zero-top" style="margin-top:20px;background-color:#fff;padding-left: 10px;padding-right: 10px;">
            <h3>精彩动态</h3>
            <div class="row sep-bottom">
                @foreach ($events as $event)
                <div class="col-md-6 col-xs-6 col-lg-6" style="">
                    <div class="post" style="border:0;">
                        <div class="post-media" style="max-height: 357px;overflow: hidden;">
                            <a href="#"><img src="{{$event['attachs'][0]['url']}}}" class="img-responsive"></a>
                        </div>
                        <div class="post-content">
                            <h4 style="margin:0;"><img class="media-object" src="{{$event['user']['avatar_url']}}" style="width: 32px;height: 32px;display: inline-block; border-radius: 50%;" alt="...">
                                {{$event['user']['nickname']}}</h4>
                            <p class="editContent" style= "height: 50px;overflow: hidden;
">{{$event['content']}}</p>
                        </div>
                    </div>
                </div>
                @endforeach
        </div>

        <div class="row" text-center style="padding-left: 20%;padding-right: 20%;">
            <button class="btn btn-lg btn-primary btn-block" type="submit" id="show_more_btn" data-loading-text="•••">查看更多</button>
        </div>
    </section>
    </div>
@stop

@section('js')
    @parent
    <script type="application/javascript">
        $(function() {
            $('#show_more_btn').on('click',function(){
                window.location.href="http://a.app.qq.com/o/simple.jsp?pkgname=me.growu.drip";
            });
        });
    </script>
@stop
