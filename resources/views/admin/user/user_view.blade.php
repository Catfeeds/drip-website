<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title">{{$user->nickname}}</h4>
</div>
<div class="modal-body">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab_basic" data-toggle="tab">基本信息</a></li>
            <li><a href="#tab_goals" data-toggle="tab">目标列表</a></li>
            <li><a href="#tab_events" data-toggle="tab">动态列表</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab_basic">
                <table class="table table-bordered">
                    <tbody>
                    <tr>
                        <td>用户ID</td>
                        <td>{{$user->user_id}}</td>
                    </tr>
                    <tr>
                        <td>昵称</td>
                        <td>{{$user->nickname}}</td>
                    </tr>
                    <tr>
                        <td>头像</td>
                        <td><img src="{{$user->user_avatar}}" class="img-circle" width="24" height="24"></td>
                    </tr>
                    <tr>
                        <td>电子邮箱</td>
                        <td>{{$user->email}}</td>
                    </tr>
                    <tr>
                        <td>手机号码</td>
                        <td>{{$user->phone}}</td>
                    </tr>
                    <tr>
                        <td>注册时间</td>
                        <td>{{date('Y-m-d H:i:s',$user->reg_time)}}</td>
                    </tr>
                    <tr>
                        <td>注册IP</td>
                        <td>{{$user->reg_ip}}</td>
                    </tr>
                    <tr>
                        <td>最后登录IP</td>
                        <td>{{$user->last_login_ip}}</td>
                    </tr>
                    <tr>
                        <td>最后登录时间</td>
                        <td>{{date('Y-m-d H:i:s',$user->last_login_time)}}</td>
                    </tr>
                    <tr>
                        <td>粉丝数</td>
                        <td>{{$user->fans_num}}</td>
                    </tr>
                    <tr>
                        <td>关注数</td>
                        <td>{{$user->follow_num}}</td>
                    </tr>
                    <tr>
                        <td>目标数</td>
                        <td>{{$user->goal_count}}</td>
                    </tr>
                    <tr>
                        <td>打卡次数</td>
                        <td>{{$user->checkin_count}}</td>
                    </tr>
                    <tr>
                        <td>签名</td>
                        <td>{{$user->signature}}</td>
                    </tr>

                    </tbody>
                </table>
            </div>
            <div class="tab-pane" id="tab_goals">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered nowrap" id="user-goals-table">
                    <thead>
                    <tr>
                        <th>目标ID</th>
                        <th>目标名称</th>
                        <th>开始日期</th>
                        <th>结束日期</th>
                        <th>预期天数</th>
                        <th>已完成天数</th>
                        <th>连续天数</th>
                        <th>最后打卡时间</th>
                        <th>提醒时间</th>
                        <th>是否公开</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                </div>
            </div>
            <div class="tab-pane" id="tab_basic">
                <table class="table table-hover table-bordered nowrap" id="buy_table">
                    <thead>
                    <tr>
                        <th>来源ID</th>
                        <th>来源帐号</th>
                        <th>数量</th>
                        <th>类型</th>
                        <th>时间</th>
                        <th>备注</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">关闭</button>
</div>

<script>
    $(function() {
        $('#user-goals-table').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 100,
            ajax: {
                url:'{!! route('admin.user.ajax_user_goals') !!}',
                data: function (d) {
                    d.user_id = '{{$user->user_id}}';
                }
            },
            order: [[0, "desc"]],
            type:'POST',
            columns: [
                { data: 'goal_id', name: 'goal_id' },
                { data: 'goal_name', name: 'goal_name' },
                { data: 'start_date', name: 'start_date' },
                { data: 'end_date', name: 'end_date' },
                { data: 'expect_days', name: 'expect_days' },
                { data: 'total_days', name: 'total_days' },
                { data: 'series_days', name: 'series_days' },
                { data: 'last_checkin_time', name: 'last_checkin_time' },
                { data: 'remind_time', name: 'remind_time' },
                { data: 'is_public', name: 'is_public' },
                { data: 'status', name: 'status'},
                { data: 'action', name: 'action' }
            ]
        });
    });
    </script>