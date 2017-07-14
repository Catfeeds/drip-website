@extends('admin.layouts.master')

@section('css')
    @parent
    <link rel="stylesheet" href="{{asset('plugins/datatables/media/css/dataTables.bootstrap.min.css')}}">
@stop

@section('content')

    <div class="container-fluid">
        <div class="row page-title-row">
            <div class="col-md-6">
                <h3>用户管理 <small>» 用户列表</small></h3>
            </div>
            <div class="col-md-6 text-right">
                <a href="/admin/post/create" class="btn btn-success btn-md">
                    <i class="fa fa-plus-circle"></i> New Post
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">

                {{--@include('admin.partials.errors')--}}

                <table id="users-table" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>#ID</th>
                        <th>邮箱</th>
                        <th>昵称</th>
                        <th>头像</th>
                        <th>注册时间</th>
                        <th>最后登录时间</th>
                        <th data-sortable="false">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

@stop
@section('scripts')
    <!-- DataTables -->
    <script type="text/javascript" src="{{asset('plugins/datatables/media/js/jquery.dataTables.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('plugins/datatables/media/js/dataTables.bootstrap.min.js')}}"></script>

    <script>
        $(function() {
            $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{!! route('admin.user.ajax_users') !!}',
                type:'POST',
                columns: [
                    { data: 'user_id', name: 'user_id' },
                    { data: 'nickname', name: 'nickname' },
                    { data: 'email', name: 'email' },
                    { data: 'user_avatar', name: 'user_avatar' },
                    { data: 'reg_time', name: 'reg_time' },
                    { data: 'last_login_time', name: 'last_login_time' },
                    { data: 'action', name: 'action' }
                ]
            });
        });
    </script>
@stop