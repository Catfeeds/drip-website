@extends('admin.layouts.master')

@section('css')
    @parent
    <link rel="stylesheet" href="{{asset('plugins/datatables/media/css/dataTables.bootstrap.min.css')}}">
@stop

@section('content')
    <div class="container-fluid">
        <div class="row page-title-row">
            <div class="col-md-6">
                <h3>应用管理 <small>» 渠道列表</small></h3>
            </div>
            <div class="col-md-6 text-right">
                <a href="#channel-add-modal" data-toggle="modal" class="btn btn-success btn-md">
                    <i class="fa fa-plus-circle"></i> 新增渠道
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">

                {{--@include('admin.partials.errors')--}}

                <table id="channles-table" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>#ID</th>
                        <th>名称</th>
                        <th>主页地址</th>
                        <th>平台</th>
                        <th>审核版本号</th>
                        <th data-sortable="false">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="channel-add-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span
                                aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title">新增渠道</h4>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" role="form">

                        <div class="form-group">
                            <label class="col-sm-3 control-label">名称:</label>
                            <div class="col-sm-2">
                                <input type="text" class="form-control" value="" name="name" id="form_name"/>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="col-sm-3 control-label">主页:</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" value="" name="homepage" id="form_homepage"/>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="col-sm-3 control-label">平台类型:</label>
                            <div class="col-sm-9">
                                <label class="radio-inline">
                                    <input type="radio" name="status" value="android" class="form_platform" checked> Android
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="status" value="ios" class="form_platform"> IOS
                                </label>
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
            var table = $('#channles-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{!! route('admin.app.get_channels') !!}',
                type:'POST',
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'name' },
                    { data: 'homepage', name: 'homepage' },
                    { data: 'platform', name: 'platform' },
                    { data: 'audit_version', name: 'audit_version' },
                    { data: 'action', name: 'action' }
                ]
            });

            // 版本提交
            $(document).on('click','#btn_channel_submit',function(){
                $.ajax({
                    type: "POST",
                    url: '{!! route('admin.app.create_channel') !!}',
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