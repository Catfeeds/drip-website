@extends('admin.layouts.master')

@section('css')
    @parent
    <link rel="stylesheet" href="{{asset('plugins/datatables/media/css/dataTables.bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('plugins/AdminLTE/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css')}}">

@stop

@section('content')
    <section class="content-header">
        <h1>
            用户管理
            <small>用户列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i>首页</a></li>
            <li><a href="#">用户管理</a></li>
            <li class="active">反馈列表</li>
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
                        <table id="feedbacks-table" class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th>#ID</th>
                                <th>用户</th>
                                <th>版本号</th>
                                <th>反馈内容</th>
                                <th>反馈时间</th>
                                <th>状态</th>
                                <th data-sortable="false">操作</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal -->
    <div class="modal fade" id="feedback-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg">
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
                            <label class="col-sm-2 control-label">处理意见:</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" rows="3" name="content" id="feedback-content">

                                </textarea>
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

    <div id="feedback-content-template" style="display: none;">
        <p>
        <p><br>亲爱的小伙伴：<br><br>&nbsp;您好，我是「水滴打卡」的负责人格吾君。非常感谢你对「水滴打卡」作出反馈。</p>
        <p><br></p>
        <p>&nbsp;关于你提出的：</p>
        <p></p>
        <blockquote id="feedback-blockquote"></blockquote>
        <p></p>
        <p>已经在新版本中进行修复，欢迎下载体验。</p>
        <p><br></p>
        <p>同时，我们也诚挚邀请您加入我们的「水滴打卡」产品交流群内对我们的产品进行更加深入的反馈和帮助。<br></p>
        <p>加入方式：添加格吾君（微信号：<strong>growu001</strong>）,发送暗号<strong>“水滴打卡</strong>”即可。</p>
        <p><br></p>
        <p><img alt="" src="http://drip.growu.me/img/qrcode2.png" style="height:300px; width:300px" /></p>
        <p>期待你的加入，再次感谢你使用「水滴打卡」。</p>
        <div><p><strong>微信公众号：格吾社区</strong></p>
            <p><strong>微博：<a target="_blank" rel="nofollow" href="http://weibo.com/growu"
                             title="Link: http://weibo.com/growu">http://weibo.com/growu</a></strong></p>
            <p><strong>qq群：7852084</strong></p>
            <p><b>官网：</b><a target="_blank" rel="nofollow" href="http://drip.growu.me/"
                            title="Link: http://drip.growu.me/">http://drip.growu.me</a></p>
            <p><strong>电子邮件：drip@growu.me</strong></p></div>
        ﻿<br></p>
    </div>

@stop
@section('scripts')
    <!-- DataTables -->
    <script type="text/javascript" src="{{asset('plugins/datatables/media/js/jquery.dataTables.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('plugins/datatables/media/js/dataTables.bootstrap.min.js')}}"></script>

    <!-- Bootstrap WYSIHTML5 -->
    <script type="text/javascript" src="{{asset('plugins/AdminLTE/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js')}}"></script>
    <!-- CKEditor -->

    <script type="text/javascript" src="{{asset('plugins/AdminLTE/plugins/ckeditor/ckeditor.js')}}"></script>

    <script>
        $(function () {
            // datatable 初始化
            var table = $('#feedbacks-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{!! route('admin.user.ajax_feedbacks') !!}',
                type: 'POST',
                iDisplayLength:100,
                scrollX:true,
                responsive:true,
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'email', name: 'email'},
                    {data: 'version', name: 'version'},
                    {data: 'content', name: 'content'},
                    {data: 'create_time', name: 'create_time'},
                    {data: 'status', name: 'status'},
                    {data: 'action', name: 'action'}
                ]
            });

            // 反馈处理提交
            $(document).on('click','.btn-feedback-deal',function(){
                var id = $(this).attr('data-id');
                if(!id) {
                    // TODO 提示
                    toastr.error("ID获取失败");
                    return;
                }
                $('#input-id').val(id);
                $('#feedback-blockquote').html($(this).data('content'))
                $('#feedback-modal').modal('show');
            });


            // 清空值
            $('#feedback-modal').on('hidden.bs.modal', function (e) {
                $('#input-id').val('');
            });

//            $('#feedback-content').wysihtml5();
            CKEDITOR.replace('feedback-content');

            $('#feedback-modal').on('shown.bs.modal', function (e) {


                var content = $('#feedback-content-template').html();
//                $('iframe').contents().find('.wysihtml5-editor').html(content);
                CKEDITOR.instances['feedback-content'].setData(content);

            });

            // 反馈处理提交
            $(document).on('click','#btn_feedback_submit',function(){
                $.ajax({
                    type: "POST",
                    url: '{!! route('admin.user.deal_feedback') !!}',
                    data: {
                        id:$("#input-id").val(),
                        content:CKEDITOR.instances['feedback-content'].getData(),
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