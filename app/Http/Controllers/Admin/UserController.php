<?php

/**
 * 用户管理控制器
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use DB;

use App\User;
use App\Models\Feedback as Feedback;
use App\Models\Message as Message;
use App\Models\Energy as Energy;
use App\Libs\MyJpush as MyJpush;
use App\Libs\MyEmail as MyEmail;

use App\Models\Event;

use Yajra\Datatables\Datatables;


use App\Http\Requests;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    //
    public function users()
    {

        return view('admin.user.users', []);
    }

    public function ajax_users()
    {
        return Datatables::of(User::query())
            ->addColumn('action', function ($user) {
                return ' <a class="btn btn-xs btn-primary add-coin-btn"  data-toggle="modal" data-target="#add-coin-modal" data-userid="'.$user->id.'"><i class="glyphicon glyphicon-add"></i>赠送水滴币</a>';
            })
            ->editColumn('reg_time', function ($user) {
                return $user->reg_time>0?date("Y-m-d H:i:s",$user->reg_time):'';
            })
            ->editColumn('last_login_time', function ($user) {
                return $user->last_login_time>0?date("Y-m-d H:i:s",$user->last_login_time):'';
            })
            ->editColumn('nickname',  function ($user) {
                return '<a href="user_view/'.$user->user_id.'" data-toggle="modal" data-target="#user-view-modal"><img src="'.$user->user_avatar.'" class="img-circle" width="24" height="24"> '.$user->nickname."</a>";
            })
            ->make(true);
    }

    public function user_view(Request $request)
    {
        $user = User::find($request->id);;

        return view('admin.user.user_view', ['user'=> $user]);
    }

    public function ajax_user_goals(Request $request)
    {

        $goals = DB::table('user_goal')
            ->select(['user_goal.*',
                'goal.goal_name'])
            ->join('goal','goal.goal_id','=','user_goal.goal_id')
            ->orderBy('user_goal.create_time','desc');


        $datatables =  Datatables::of($goals)
            ->addColumn('action', function ($goal) {
                return '<a href="#edit-'.$goal->goal_id.'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> 编辑</a>';
            })
            ->editColumn('create_time', function ($goal) {
                return $goal->create_time>0?date("Y-m-d H:i:s",$goal->create_time):'';
            })
            ->editColumn('last_checkin_time', function ($goal) {
                return $goal->last_checkin_time>0?date("Y-m-d H:i:s",$goal->last_checkin_time):'';
            })
            ->editColumn('is_public', function ($goal) {
                return $goal->is_public == 0?'私密':'公开';
            })
            ->editColumn('status', function ($goal) {
                return $goal->status == 0?'进行中':'已结束';
            });

            // additional users.name search
            if ($user_id = $datatables->request->get('user_id')) {
                $datatables->where('user_goal.user_id', '=',$user_id);
            }

            return $datatables->make(true);
    }

    /**
     * 取出用户反馈
     */
    public function feedbacks()
    {
        $feedbacks = Feedback::paginate(100);;
        return view('admin.user.feedbacks', ['feedbacks' => $feedbacks]);
    }

    public function ajax_feedbacks()
    {
        $feedbacks = DB::table('feedback')
            ->select(['feedback.*',
                'users.user_avatar',
                'users.nickname',
                'attaches.id',
                'attaches.name',
                'attaches.path'])
            ->where('feedback.is_del',0)
            ->join('users','users.user_id','=','feedback.user_id')
            ->leftJoin('attaches', function ($join) {
                $join->on('attaches.attachable_id', '=', 'feedback.id')
                    ->where('attaches.attachable_type', '=', 'feedback');
            })
            ->orderBy('feedback.create_time','desc');

        return Datatables::of($feedbacks)
            ->addColumn('action', function ($feedback) {
               if($feedback->status == 0) {
                   return '<button data-id="'.$feedback->id.'" data-content="'.$feedback->content.'" class="btn btn-xs btn-primary btn-feedback-deal"><i class="glyphicon glyphicon-edit"></i>处理</button>
<button data-id="'.$feedback->id.'" class="btn btn-xs btn-danger btn-feedback-del"><i class="glyphicon glyphicon-delete"></i>删除</button>';
               } else {
                   return '';
               }
            })
            ->addColumn('attach', function ($feedback) {
                if($feedback->id) {
                    return '<img src="http://drip.growu.me/uploads/images/'.$feedback->path.'/'.$feedback->name.'" class="" width="200" height="200">';
                }
                return '';
            })
            ->editColumn('status', function ($feedback) {
               switch ($feedback->status) {
                   case 0:
                       return '<span class="label label-default">未处理</span>';
                       break;
                   case 1:
                       return '<span class="label label-success">已处理</span>';
                       break;
                   case 2:
                       return '<span class="label label-success">已处理</span>';
                       break;
                   default;
                       return '';
                       break;
               }
            })
            ->editColumn('create_time', function ($feedback) {
                return date("Y-m-d H:i:s",$feedback->create_time);
            })
            ->editColumn('device', function ($feedback) {
//                return $feedback->device;
                  $device = json_decode($feedback->device);
                  return $device?$device->manufacturer.' '.$device->model:'';
            })
            ->editColumn('user_avatar', '<img src="{{$user_avatar}}" width="48" height="48">')
            ->make(true);
    }

    public function add_vip(Request $request) {
        $user_id = $request->input("user_id");
        $days = $request->input("days");
        $remark = $request->input("remark");

        $user = User::find($user_id);

        if(!$user) {
            return ['status'=>false,'message'=>'用户不存在'];
        }

        if($user->is_vip) {
            // 判断vip是否过期
            if($user->vip_end_date<date('Y-m-d')) {
                $user->is_vip = 1;
                $user->vip_begin_date = date('Y-m-d');
                $user->vip_end_date = date('Y-m-d',strtotime("+".$days." days"));
            }else {
                $user->vip_end_date  = date('Y-m-d',strtotime($user->vip_end_date)+86400*$days);
            }
        } else {
            $user->is_vip = 1;
            $user->vip_begin_date = date('Y-m-d');
            $user->vip_end_date = date('Y-m-d',strtotime("+".$days." days"));
        }

        $user->save();


        $id = DB::table('user_vip_log')->insertGetId(
            [
                'user_id' => $user_id,
                'days' => $days,
                'type' => 'reward',
                'remark' => $remark,
                'remark' => '',
                'created_at' => date('Y-m-d H:i:s')
            ]
        );

        $content = "恭喜你，获得一份会员奖励。<br><br>
                    赠送人：水滴君<br>
                    赠送时间：".$days."<br>
                    赠送说明：".$remark;

        $message = new Message();
        $message->from_user = 0;
        $message->to_user = $user_id;
        $message->type = 6 ;
        $message->title = '会员奖励' ;
        $message->content = $content;
        $message->msgable_id = $id;
        $message->msgable_type = 'user_vip_log';
        $message->create_time  = time();
        $message->save();



        return ['status'=>true,'message'=>'操作成功'];


    }

    public function add_coin(Request $request) {
        $user_id = $request->input("user_id");
        $num = $request->input("num");
        $remark = $request->input("remark");

        $user = User::find($user_id);

        if(!$user) {
            return ['status'=>false,'message'=>'用户不存在'];
        }

        $user->energy_count += $num;
        $user->save();

        $energy = new Energy();
        $energy->user_id = $user->user_id;
        $energy->change = $num;
        $energy->obj_type = 'feedback';
        $energy->obj_id = 0;
        $energy->create_time = time();
        $energy->save();

        $message = new Message();
        $message->from_user = 0;
        $message->to_user = $user_id;
        $message->type = 6 ;
        $message->title = '奖励通知' ;
        $message->content = trim(sprintf($remark,$num));
        $message->msgable_id = $energy->id;
        $message->msgable_type = 'App\Energy';
        $message->create_time  = time();
        $message->save();



        return ['status'=>true,'message'=>'操作成功'];


    }


    /**
     * 处理用户反馈
     */
    public function deal_feedback(Request $request) {
        $id = $request->id;
        $content = $request->input("content");
        $status = $request->input("status");
        $reward = $request->input("reward");


        // 查询反馈信息
        $feedback = Feedback::find($id);

        if(!$feedback) {
            return ['status'=>false,'message'=>'反馈记录不存在'];
        }

        $email = $feedback->user->email;

        if(filter_var($email,FILTER_VALIDATE_EMAIL))
        {
            $myEmail = new MyEmail('aes');
            $myEmail->sendWithContent([$email], '意见反馈回复', $content);
        }

        //发送站内信

        $message = new Message();
        $message->from_user = 0;
        $message->to_user = $feedback->user_id;
        $message->type = 6 ;
        $message->title = '反馈回复' ;
        $message->content = $content ;
        $message->msgable_id = $id;
        $message->msgable_type = 'App\Models\Feedback';
        $message->create_time  = time();
        $message->save();

        $feedback->status = 1;
        $feedback->deal_time = time();
        $feedback->save();

//        // 发送奖励
        if($reward>0) {
            $user = User::find($feedback->user_id);
            $user->energy_count += 10;
            $user->save();

            $energy = new Energy();
            $energy->user_id = $feedback->user_id;
            $energy->change = 10;
            $energy->obj_type = 'feedback';
            $energy->obj_id = $feedback->id;
            $energy->create_time = time();
            $energy->save();
        }

//
        //发送消息
//        $message = new Message();
//        $message->from_user = 0;
//        $message->to_user = $feedback->user_id;
//        $message->type = 6 ;
//        $message->title = '意见反馈回复' ;
//        $message->content = $content ;
//        $message->msgable_id = $id;
//        $message->msgable_type = 'App\Models\Feedback';
//        $message->create_time  = time();
//        $message->save();
//
//        // 推送
//        $content = '你的反馈已收到回复';
//
//        $push = new MyJpush();
//        $push->pushToSingleUser($feedback->user_id,$content);

        return ['status'=>true,'message'=>'操作成功'];
    }

    public function delete_feedback(Request $request) {
        $id = $request->id;

        // 查询反馈信息
        $feedback = Feedback::find($id);

        if(!$feedback) {
            return ['status'=>false,'message'=>'反馈记录不存在'];
        }

        $feedback->is_del = 1;
        $feedback->delete();

        return ['status'=>true,'message'=>'操作成功'];
    }


    /**
     * 处理用户反馈
     */
    public function hot_event(Request $request) {
        $id = $request->id;

        // 查询反馈信息
        $event = Event::find($id);

        if(!$event) {
            return ['status'=>false,'message'=>'动态不存在'];
        }

        if($event->is_hot == 1) {
            return ['status'=>false,'message'=>'已经是精选动态'];
        }

        $event->is_hot = 1;
        $event->save();

        // 发送奖励
        $user = User::find($event->user_id);
        $user->energy_count += 5;
        $user->save();

        $energy = new Energy();
        $energy->user_id = $event->user_id;
        $energy->change = +5;
        $energy->obj_type = 'hot';
        $energy->obj_id = $event->event_id;
        $energy->create_time = time();
        $energy->save();

        //发送消息
        $message = new Message();
        $message->from_user = 0;
        $message->to_user = $event->user_id;
        $message->type = 6 ;
        $message->title = '精选动态入选通知' ;
        $message->content = '恭喜你,你的打卡动态被评选为精选动态,并奖励能量点+5,希望再接再厉~<a href="#/event/'.$id.'">点击查看</a>' ;
        $message->msgable_id = $id;
        $message->msgable_type = 'App\Event';
        $message->create_time  = time();
        $message->save();

        // 推送
        $content = '精选动态入选通知';

        $push = new MyJpush();
        $push->pushToSingleUser($event->user_id,$content);

        return ['status'=>true,'message'=>'操作成功'];
    }


    /**
     * 取出用户反馈
     */
    public function events()
    {

        return view('admin.user.events', []);
    }

    public function ajax_events()
    {
        $events = DB::table('events')
            ->select(['events.*',
                'users.user_avatar',
                'users.email',
                'users.nickname',
                'checkins.content',
                'attaches.id',
                'attaches.name',
                'attaches.path'
            ])
            ->join('users','users.id','=','events.user_id')
            ->join('checkins','checkins.id','=','events.event_value')
            ->leftJoin('attachs','attaches.attachable_id','=','events.event_value')
            ->where('attaches.attachable_type', '=', 'checkin')
//            ->leftJoin('attachs', function ($join) {
//                $join->on('attaches.attachable_id', '=', 'events.event_value')
//                    ->where('attaches.attachable_type', '=', 'checkin');
//            })
            ->orderBy('events.create_time','desc');

        return Datatables::of($events)
            ->addColumn('action', function ($event) {
                return '<button data-id="'.$event->event_id.'" class="btn btn-xs btn-primary btn-event-hot"><i class="glyphicon glyphicon-edit"></i>设为精选</button>';
            })
            ->addColumn('attach', function ($event) {
                if($event->id) {
                    return '<img src="http://drip.growu.me/uploads/images/'.$event->path.'/'.$event->name.'" class="" width="200" height="200">';
               }
                return '无';
            })
            ->editColumn('is_public', function ($event) {
                return $event->is_public==1?'是':'否';
            })
            ->editColumn('is_hot', function ($event) {
                return $event->is_hot==1?'是':'否';
            })
            ->editColumn('create_time', function ($event) {
                return date("Y-m-d H:i:s",$event->create_time);
            })
            ->editColumn('user_avatar', '<img src="{{$user_avatar}}" class="img-circle" width="48" height="48">')
            ->make(true);
    }

}
