@extends('admin.layouts.master')

@section('css')
    @parent
    <link rel="stylesheet" href="{{asset('plugins/datatables/media/css/dataTables.bootstrap.min.css')}}">
@stop

@section('content')
    <div class="container-fluid">
        <div class="row page-title-row">
            <div class="col-md-6">
                <h3>商城管理 <small>» 商品列表</small></h3>
            </div>
            <div class="col-md-6 text-right">
                <a href="#mall-add-modal" data-toggle="modal" class="btn btn-success btn-md">
                    <i class="fa fa-plus-circle"></i> 新增商品
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
                        <th>名称</th>
                        <th>描述</th>
                        <th>主图</th>
                        <th>原价</th>
                        <th>现价</th>
                        <th>能量币</th>
                        <th>运费</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th data-sortable="false">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="mall-add-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span
                                aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title">新增商品</h4>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" role="form">

                        <div class="form-group">
                            <label class="col-sm-2 control-label">商品标题:</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" value="" name="title" id="input-no"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">商品描述:</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" rows="3" name="content"  id="textarea-content"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">原价:</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" value="" name="title" id="input-no"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">现价:</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" value="" name="title" id="input-no"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">能量:</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" value="" name="title" id="input-no"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">邮费:</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" value="" name="title" id="input-no"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">主图链接:</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" value="" name="title" id="input-no"/>
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" id="btn_good_submit">确认</button>
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
                ajax: '{!! route('admin.mall.ajax_goods') !!}',
                type:'POST',
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'title', name: 'title' },
                    { data: 'content', name: 'content' },
                    { data: 'main_image', name: 'main_image' },
                    { data: 'original_price', name: 'original_price' },
                    { data: 'sell_price', name: 'sell_price' },
                    { data: 'energy_price', name: 'energy_price' },
                    { data: 'postage', name: 'postage' },
                    { data: 'create_time', name: 'create_time' },
                    { data: 'status', name: 'status' },
                    { data: 'action', name: 'action' }
                ]
            });

            // 反馈处理提交
            $(document).on('click','#btn_good_submit',function(){
                $.ajax({
                    type: "POST",
                    url: '{!! route('admin.mall.creategood') !!}',
                    data: {

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