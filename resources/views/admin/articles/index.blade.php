
@extends('admin.layouts.master')

@section('css')
    @parent
    <link rel="stylesheet" href="{{asset('plugins/datatables/media/css/dataTables.bootstrap.min.css')}}">
@stop

@section('content')

    <section class="content-header">
        <h1>
            博客管理
            <small>文章列表</small>
            <div class="col-md-6 text-right">
                <a href="#channel-add-modal" data-toggle="modal" class="btn btn-success btn-md">
                    <i class="fa fa-plus-circle"></i> 创建文章
                </a>
            </div>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li><a href="#">博客管理</a></li>
            <li class="active">文章列表</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">全部文章</h3>
                    </div>
                    <div class="box-body">
                        <table id="articles-table" class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>标题</th>
                                <th>分类</th>
                                <th>发布时间</th>
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
            $('#articles-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength:100,
                ajax: '{!! route('admin.blog.article.lists') !!}',
                order: [[0, "desc"]],
                type:'POST',
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'title', name: 'title' },
                    { data: 'category_id', name: 'category_id' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action' }
                ]
            });
        });
    </script>
@stop