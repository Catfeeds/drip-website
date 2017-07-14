@extends('layouts/master')
 
@section('title', '发现目标')

@section('content')
    <div class="container pl-xl pr-xl">
        <div class="row">
            <div class="page-header">
        	   <h2>最新创建</h2>
            </div>
        	<div class="row">
        		@foreach ($goals as $goal)
    			    <div class="col-md-3 p-lg">
    			    	<a href="goal/view/{{ $goal->goal_id }}"><label class="label label-green">{{ $goal->goal_name }}</label></a>
        			</div>
    			@endforeach
        		
        	</div>
        </div>
    </div>
@stop