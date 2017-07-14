@extends('layouts/master')
 
@section('title', "目标详情")

@section('content')
    <div class="container">
        <div class="page-header">
            <h2>{{ $goal->goal_name }} 
<!--                 <small><label class="label label-sm label-success">进行中</label> </small>
 -->                <div class="pull-right"><button type="button" class="btn btn-green btn-outline">制定</button>
                </div>
            </h2>
        </div>
       <!--  <div class="well">
        实践者：Jason.z
        开始于：2016-01-01 <br>
        </div> -->

        <div class="row mb-lg">
            <div class="col-md-12">
                <div class="tabbable-line">
                    <ul class="nav nav-tabs">
                      <li role="presentation" class="active"><a href="#">动态</a></li>
                      <li role="presentation"><a href="#">评论</a></li>
                    </ul>
                </div>
            </div>
        </div>

    <div class="row">
        <div class="col-md-12">
            <div class="col-md-8">
                @foreach ($events as $event)
                    <div class="event">
                        <div class="panel panel-default bg-white">
                            <div class="panel-heading bb0">
                            <p><a href=""><img src="{{asset('img/avatar.png')}}" class="img-circle" width="40" height="40" /> 这是一个昵称</a><span class="pull-right">完成  <a>我要牛逼的马甲线</a> 第 15 天</span></p>
                            </div>
                            <div class="panel-body">
                                1、俯卧撑200个
                                2、仰卧起坐300个
                                3、深蹲1000个
<!--                                 <img src="{{asset('img/c5.jpg')}}" class="img-responsive">
 -->                            </div>
                             <div class="panel-footer">
                                <div class="row">
                                    <div class="col-md-6">
                                        <a href="＃">7分钟前 来自 ios客户端</a>
                                    </div>
                                     
                                    <div class="col-md-2 pull-right">
                                        <a href="javascript:;" class="checkin-comment-btn" data-checkin="<?php echo $event['checkin']['checkin_id'];?>"> 
                                        <i class="fa fa-ellipsis-h"></i>
                                            <label class="count"></label>
                                        </a> 
                                    </div>
                                    <div class="col-md-2 pull-right">
                                        <a href="javascript:;" class="checkin-comment-btn" data-checkin="<?php echo $event['checkin']['checkin_id'];?>"> 
                                        <i class="fa fa-comment-o"> </i>
                                            <label class="count"> 20</label>
                                        </a> 
                                    </div>
                                    <div class="col-md-2 pull-right">
                                        <a href="javascript:;" data-checkin="<?php echo $event['checkin']['checkin_id'];?>" class="like-btn"> 
                                            <i class="fa fa-heart-o"></i> <label class="count"> 10</label>
                                        </a> 
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
           <!--  <div class="col-md-5">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        最新加入
                    </div>
                    <div class="panel-body">
                        <div class="col-xs-6 col-md-3">
                            <a href="">
                                <img src="{{asset('img/avatar.png')}}"  width="40" height="40" />
                            </a>
                        </div>
                        <div class="col-xs-6 col-md-3">
                            <a href="">
                                <img src="{{asset('img/avatar.png')}}"  width="40" height="40" />
                            </a>
                        </div>
                        <div class="col-xs-6 col-md-3">
                            <a href="">
                                <img src="{{asset('img/avatar.png')}}"  width="40" height="40" />
                            </a>
                        </div>
                        <div class="col-xs-6 col-md-3">
                            <a href="">
                                <img src="{{asset('img/avatar.png')}}"  width="40" height="40" />
                            </a>
                        </div>
                        <div class="col-xs-6 col-md-3">
                            <a href="">
                                <img src="{{asset('img/avatar.png')}}"  width="40" height="40" />
                            </a>
                        </div>
                    </div>
                </div>

                  <div class="panel panel-default">
                    <div class="panel-heading">
                        最新完成
                    </div>
                    <div class="panel-body">
                        <div class="col-xs-6 col-md-3">
                            <a href="">
                                <img src="{{asset('img/avatar.png')}}"  width="40" height="40" />
                            </a>
                        </div>
                        <div class="col-xs-6 col-md-3">
                            <a href="">
                                <img src="{{asset('img/avatar.png')}}"  width="40" height="40" />
                            </a>
                        </div>
                        <div class="col-xs-6 col-md-3">
                            <a href="">
                                <img src="{{asset('img/avatar.png')}}"  width="40" height="40" />
                            </a>
                        </div>
                        <div class="col-xs-6 col-md-3">
                            <a href="">
                                <img src="{{asset('img/avatar.png')}}"  width="40" height="40" />
                            </a>
                        </div>
                        <div class="col-xs-6 col-md-3">
                            <a href="">
                                <img src="{{asset('img/avatar.png')}}"  width="40" height="40" />
                            </a>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        可能感兴趣的目标
                    </div>
                    <div class="panel-body">
                        <p><a href="">早起22</a></p>
                        <p><a href="">早起22</a></p>
                        <p><a href="">早起22</a></p>
                        <p><a href="">早起22</a></p>
                        <p><a href="">早起22</a></p>

                    </div>
                </div>
                
            </div> -->
        </div>
    </div>
    </div>
@stop