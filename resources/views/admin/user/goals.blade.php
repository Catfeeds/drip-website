@extends('admin.layouts.master')

@section('css')
    @parent
    <link rel="stylesheet" href="{{asset('plugins/datatables/media/css/dataTables.bootstrap.min.css')}}">
@stop

@section('content')

    <section class="content-header">
        <h1>
            用户管理
            <small>用户列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li><a href="#">用户管理</a></li>
            <li class="active">列表</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">全部用户</h3>
                    </div>
                    <div class="box-body">
                        <table id="users-table" class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th>#ID</th>
                                <th>昵称</th>
                                <th>邮箱</th>
                                <th>注册时间</th>
                                <th>最后登录时间</th>
                                <th data-sortable="false">操作</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="user-view-modal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

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
                pageLength:100,
                ajax: '{!! route('admin.user.ajax_users') !!}',
                order: [[0, "desc"]],
                type:'POST',
                columns: [
                    { data: 'user_id', name: 'user_id' },
                    { data: 'nickname', name: 'nickname' },
                    { data: 'email', name: 'email' },
                    { data: 'reg_time', name: 'reg_time' },
                    { data: 'last_login_time', name: 'last_login_time' },
                    { data: 'action', name: 'action' }
                ]
            });
        });
    </script>
@stop