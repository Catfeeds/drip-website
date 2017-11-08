
@extends('admin.layouts.master')

@section('css')
    @parent
    <link rel="stylesheet" href="{{asset('plugins/datatables/media/css/dataTables.bootstrap.min.css')}}">
@stop

@section('content')

    <section class="content-header">
        <h1>
            博客管理
            <small>添加文章</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li><a href="#">博客管理</a></li>
            <li class="active">添加文章</li>
        </ol>
    </section>

    <section class="content">
        <!-- 显示验证错误 -->
        @include('admin.common.errors')
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">添加文章</h3>
                    </div>
                    <!-- /.box-header -->
                    <!-- form start -->
                    <form action="{!! route('admin.blog.article.store') !!}" method="post">
                     <div class="box-body">
                            <div class="form-group">
                                <label for="article-title">文章标题</label>
                                <input type="text" class="form-control" id="article-title" name="title">
                            </div>
                            <div class="form-group">
                                <label for="article-">文章分类</label>
                                <select class="form-control" id="article-category" name="category">
                                    <option value="1">更新日志</option>
                                    <option value="2">产品活动</option>
                                    <option value="3">功能介绍</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="article-content">内容</label>
                                <textarea id="article-content" name="content" rows="10" cols="80">
                                </textarea>
                            </div>


                        </div>
                        <!-- /.box-body -->

                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary">发布</button>
                        </div>
                    </form>
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
    <script language="javascript">
        var uptoken='{{ $token }}';  //七牛云服务端生成的uptoken
    </script>

    <!-- CKEDITOR -->
    <script type="text/javascript" src="{{asset('plugins/plupload/plupload.full.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('plugins/ckeditor/ckeditor.js')}}"></script>

    <script>
        $(function() {
            CKEDITOR.replace('article-content',{"extraPlugins":"filebrowser,image,imagepaste,filetools"});
        });
    </script>
@stop