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
                        <th>版本号</th>
                        <th>更新类型</th>
                        <th>更新内容</th>
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
                            <label class="col-sm-2 control-label">更新类型:</label>
                            <div class="col-sm-10">
                                <label class="radio-inline">
                                    <input type="radio" name="status" value="1" class="radio-type" checked> 资源更新
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="status" value="2" class="radio-type"> APK更新
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">版本号:</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" value="" name="no" id="input-no"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">更新内容:</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" rows="3" name="content"  id="textarea-content"></textarea>
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
                ajax: '{!! route('admin.version.ajax_versions') !!}',
                type:'POST',
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'no', name: 'no' },
                    { data: 'type', name: 'type' },
                    { data: 'content', name: 'content' },
                    { data: 'create_time', name: 'create_time' },
                    { data: 'action', name: 'action' }
                ]
            });

            // 反馈处理提交
            $(document).on('click','#btn_version_submit',function(){
                $.ajax({
                    type: "POST",
                    url: '{!! route('admin.version.create') !!}',
                    data: {
                        type:$(".radio-type:checked").val(),
                        content:$("#textarea-content").val(),
                        no:$("#input-no").val()
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
        });
    </script>
@stop