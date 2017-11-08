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
                <h2>{!! $article->title !!}</h2>
                <div>
                    {!! $article->content !!}
                </div>
            </div>

            <div class="col-md-5">
            </div>

        </div>
    </div>

@stop

@section('js')
    @parent
@stop
