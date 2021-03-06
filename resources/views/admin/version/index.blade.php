@extends('admin.layouts.master')

@section('css')
    @parent
    <link rel="stylesheet" href="{{asset('plugins/datatables/media/css/dataTables.bootstrap.min.css')}}">
@stop

@section('content')
    <div class="container-fluid">
        <div class="row page-title-row">
            <div class="col-md-6">
                <h3>版本管理 <small>» 版本列表</small></h3>
            </div>
            <div class="col-md-6 text-right">
                <a href="#version-add-modal" data-toggle="modal" class="btn btn-success btn-md">
                    <i class="fa fa-plus-circle"></i> 新增版本
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">

                {{--@include('admin.partials.errors')--}}

                <table id="versions-table" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>#ID</th>
                        <th>app版本号</th>
                        <th>web版本号</th>
                        <th>更新类型</th>
                        <th>更新内容</th>
                        <th>是否推送</th>
                        <th>发布时间</th>
                        <th data-sortable="false">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="version-add-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span
                                aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title">新增版本</h4>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" role="form">
                        <input type="hidden" name="id" value="" id="input-id">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">更新类型:</label>
                            <div class="col-sm-9">
                                <label class="radio-inline">
                                    <input type="radio" name="status" value="1" class="form_type" checked> 资源更新
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="status" value="2" class="form_type"> 整包更新
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">APP版本号:</label>
                            <div class="col-sm-2">
                                <input type="text" class="form-control" value="" name="app_version" id="form_app_version"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">WEB版本号:</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" value="" name="web_version" id="form_web_version"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">更新内容:</label>
                            <div class="col-sm-6">
                                <textarea class="form-control" rows="3" name="content"  id="form_content"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">是否推送</label>
                            <div class="col-sm-9">
                                <input type="checkbox" class="form-control" value="" name="is_push" id="form_is_push"/>
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" id="btn_version_submit">确认</button>
                </div>
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
            var table = $('#versions-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{!! route('admin.app.get_versions') !!}',
                type:'POST',
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'app_version', name: 'app_version' },
                    { data: 'web_version', name: 'web_version' },
                    { data: 'type', name: 'type' },
                    { data: 'content', name: 'content' },
                    { data: 'is_push', name: 'is_push' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action' }
                ]
            });

            // 版本提交
            $(document).on('click','#btn_version_submit',function(){
                $.ajax({
                    type: "POST",
                    url: '{!! route('admin.app.create_version') !!}',
                    data: {
                        type:$(".form_type:checked").val(),
                        content:$("#form_content").val(),
                        app_version:$("#form_app_version").val(),
                        web_version:$("#form_web_version").val(),
                        is_push:$("#form_is_push").val()
                    },
                    dataType: "json",
                    success: function(response){
                        if(response.status) {
                            toastr.success("添加成功");
                            $('#version-add-modal').modal('hide');
                            if(table) {
                                table.clear();
                                table.draw();
                            }
                        } else {
                            toastr.error(response.mesasge);
                        }
                    },
                    error:function(error) {
                        toastr.error("请求接口错误");
                    }
                });
            });

            $(document).on('click','.btn-version-del',function(){
                var id = $(this).attr('data-id');
                if(!id) {
                    // TODO 提示
                    toastr.error("ID获取失败");
                    return;
                }

                $.ajax({
                    type: "POST",
                    url: '{!! route('admin.version.delete') !!}',
                    data: {
                        id:id,
                    },
                    dataType: "json",
                    success: function(response){
                        if(response.status) {
                            toastr.success("处理成功");
                            if(table) {
                                table.clear();
                                table.draw();
                            }
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error:function(error) {
                        toastr.error("请求接口错误");
                    }
                });


            });

        });
    </script>
@stop