@extends('admin.layouts.master')

@section('css')
    @parent
    <link rel="stylesheet" href="{{asset('plugins/datatables/media/css/dataTables.bootstrap.min.css')}}">
@stop

@section('content')
    <div class="container-fluid">
        <div class="row page-title-row">
            <div class="col-md-6">
                <h3>用户管理
                    <small>» 动态列表</small>
                </h3>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">

                {{--@include('admin.partials.errors')--}}

                <table id="events-table" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>#ID</th>
                        <th>用户</th>
                        <th>内容</th>
                        <th>附件</th>
                        <th>是否公开</th>
                        <th>是否精选</th>
                        <th>时间</th>
                        <th data-sortable="false">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="feedback-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span
                                aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title">反馈处理</h4>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" role="form">
                        <input type="hidden" name="id" value="" id="input-id">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">有效性:</label>
                            <div class="col-sm-10">
                                <label class="radio-inline">
                                    <input type="radio" name="status" value="1" class="radio-status" checked> 有效
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="status" value="2" class="radio-status"> 无效
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">处理意见:</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" rows="3" name="content"  id="textarea-content">你的反馈意见已经确认,我们将会根据你的建议在未来的版本内做调整,敬请期待,为表示对你的感谢,我们特别奖励你 5 点能量作为奖励,期待你的继续反馈。</textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">奖励:</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control" value="5" name="reward" id="input-reward"/>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" id="btn_feedback_submit">确认</button>
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
        $(function () {
            // datatable 初始化
            var table = $('#events-table').DataTable({
                processing: true,
                serverSide: true,
                iDisplayLength:100,
                ajax: '{!! route('admin.user.ajax_events') !!}',
                type: 'POST',
                columns: [
                    {data: 'event_id', name: 'event_id'},
                    {data: 'nickname', name: 'nickname'},
                    {data: 'checkin_content', name: 'checkin_content'},
                    {data: 'attach', name: 'attach'},
                    {data: 'is_public', name: 'is_public'},
                    {data: 'is_hot', name: 'is_hot'},
                    {data: 'create_time', name: 'create_time'},
                    {data: 'action', name: 'action'}
                ]
            });

            // 反馈处理提交
            $(document).on('click','.btn-event-hot',function(){
                var id = $(this).attr('data-id');
                if(!id) {
                    // TODO 提示
                    toastr.error("ID获取失败");
                    return;
                }

                $.ajax({
                    type: "POST",
                    url: '{!! route('admin.user.hot_event') !!}',
                    data: {
                        id:id
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

            // radio 切换事件
            $(".radio-status").change(function() {
                var $selectedvalue = $(".radio-status:checked").val();
                if ($selectedvalue == 1) {
                    $('#textarea-content').val('你的反馈意见已经确认,我们将会根据你的建议在未来的版本内做调整,敬请期待,为表示对你的感谢,我们特别奖励你 5 点能量作为奖励,期待你的继续反馈。');
                    $('#input-reward').val(5);
                } else {
                    $('#textarea-content').val('你的反馈意见已经确认,由于版本设计和其他原因,我们暂时将不会对此做调整,还请谅解,期待你的继续反馈!');
                    $('#input-reward').val(0);
                }
            });

            // 清空值
            $('#feedback-modal').on('hidden.bs.modal', function (e) {
                $('#input-id').val('');
            })

            // 反馈处理提交
            $(document).on('click','#btn_feedback_submit',function(){
                $.ajax({
                    type: "POST",
                    url: '{!! route('admin.user.deal_feedback') !!}',
                    data: {
                        id:$("#input-id").val(),
                        status:$(".radio-status:checked").val(),
                        content:$("#textarea-content").val(),
                        reward:$("#input-reward").val()
                    },
                    dataType: "json",
                    success: function(response){
                        if(response.status) {
                            toastr.success("处理成功");
                            $('#feedback-modal').modal('hide');
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