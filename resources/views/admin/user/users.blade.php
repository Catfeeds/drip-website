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
            <li class="active">用户列表</li>
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

    <div class="modal fade" id="add-vip-modal">
        <div class="modal-dialog    ">
            <div class="modal-content">
                <form action="{!! route('admin.user.add_vip') !!}" method="post" id="add-vip-form">
                    <input type="hidden" name="user_id" id="add_vip_userid">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">赠送会员</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="article-title">天数<span class="required">*</span></label>
                        <input type="text" class="form-control" name="days">
                    </div>
                    <div class="form-group">
                        <label for="article-title">备注</label>
                        <textarea class="form-control" name="remark">
                        </textarea>
                    </div>
                </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">确认</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@stop
@section('scripts')
<!-- DataTables -->
<script type="text/javascript" src="{{asset('plugins/datatables/media/js/jquery.dataTables.min.js')}}"></script>
<script type="text/javascript" src="{{asset('plugins/datatables/media/js/dataTables.bootstrap.min.js')}}"></script>

<!-- Jquery-validation -->
<script type="text/javascript" src="{{asset('plugins/jquery-validation/dist/jquery.validate.js')}}"></script>
<script type="text/javascript" src="{{asset('plugins/jquery-validation/dist/additional-methods.js')}}"></script>

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

            $("#add-vip-form").validate({
                rules: {
                    days: {
                        required: true,
                        digits:true
                    },
                    remark:{

                    }
                },
                messages:{
                    days:{
                        required:"天数不能为空",
                        digits:"天数必须为正整数"
                    }
                },
                submitHandler:function(form){

//                    $(form).ajaxSubmit();
//                    return false;


                    $.ajax({
                        url : "{!! route('admin.user.add_vip') !!}",
                        type : "post",
                        dataType : "json",
                        data:$(form).serialize(),
                        success : function(res) {
                            if(res.stauts){
                                toastr.success("赠送成功");
                            } else {
                                toastr.error("赠送失败");
                            }
                        }
                    });

                    return false;

                }
            });

            $(document).on('click','.add-vip-btn',function () {
               var userId = $(this).attr('data-userid');
                $('#add_vip_userid').val(userId);
            });
        });
    </script>
@stop