<?php
/**
 * 用户控制器
 */
namespace App\Http\Controllers\Api\V2;

use Auth;
use Carbon\Carbon;
use Validator;
use API;
use DB;
use Log;

use App\User;
use App\Checkin;
use App\Models\Message as Message;
use App\Models\Attach as Attach;
use App\Models\Report as Report;
use App\Event;


use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class UserController extends BaseController {

    // 取出用户的基本信息
    public function info()
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

        $validation = Validator::make(Input::all(), [
            'user_id'		=> 	'required',		// 用户id
        ],$messages);

        if($validation->fails()){
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $user_id  = Input::get('user_id');

        $user = User::find($user_id);

        $is_follow = false;

        // 查询是否关注

        $user_follow = DB::table('user_follow')
            ->where('user_id',$this->auth->user()->user_id)
            ->where('follow_user_id',$user_id)
            ->first();

        if($user_follow) {
            $is_follow = true;
        }

        $user->is_follow = $is_follow;



        // TODO 判断用户是否存在
        return API::response()->array(['status' => true, 'message' =>'','data'=>$user])->statusCode(200);

    }
 
    // 取出登录用户的目标列表
    public function getGoals (Request $request)
    {
        $messages = [
//            'account.required' => '请输入邮箱地址',
        ];

        $validation = Validator::make(Input::all(), [
            'date'	=> 	'',		// 具体日期
        ],$messages);

        if($validation->fails()){
            return API::response()->error($validation->errors()->all('</br>:message'),500);
        }

    	$user_id  = $this->auth->user()->user_id;

        $date = $request->input("date",date('Y-m-d'));

        $goals = User::find($user_id)
            ->goals()
            ->wherePivot('is_del','=',0)
            ->wherePivot('start_date','<=',$date)
            ->where(function($query) use ($date) {
                $query->where('user_goal.end_date', '>=', $date)
                    ->orWhere('user_goal.end_date', '=', NULL);
            })
            ->orderBy('remind_time','asc')
            ->get();

        $result = array();

        foreach ($goals as $key => $goal) {

            $result[$key]['id'] = $goal->goal_id;
            // TODO
            // $goals[$key]['name'] = $goal->pivot->name;
            $result[$key]['name'] = $goal->goal_name;
            $result[$key]['is_today_checkin'] = $goal->pivot->last_checkin_time >= strtotime(date('Y-m-d')) ? true : false;
            $result[$key]['remind_time'] = $goal->pivot->remind_time?substr($goal->pivot->remind_time,0,5):null;
            $result[$key]['status'] = $goal->pivot->status;

        }

        return API::response()->array($result)->statusCode(200);
    }

    public function getGoalsCalendar (Request $request)
    {
        $messages = [
            'start_date.required' => '请输入开始日期',
            'end_date.required' => '请输入结束日期',
        ];

        $validation = Validator::make(Input::all(), [
            'start_date'	=> 	'required',		// 开始日期
            'end_date'		=> 	'required',		// 结束日期
        ],$messages);

        if($validation->fails()){
            return API::response()->error($validation->errors()->all('</br>:message'),500);
        }

        $user_id  = $this->auth->user()->user_id;

        $start_date = $request->input("start_date");
        $format_start_date = Carbon::parse($start_date);;
        $end_date = $request->input("end_date");
        $format_end_date = Carbon::parse($end_date);;

        $diffDays = $format_start_date->diffInDays($format_end_date);

        $result = array();

        for($i=0;$i<=$diffDays;$i++) {
            $result[] = $this->_get_goals_by_day($format_start_date->addDays($i)->toDateString());
        }

        return API::response()->array($result)->statusCode(200);
    }

    private function _get_goals_by_day($date) {
        $user_id  = $this->auth->user()->user_id;

        return User::find($user_id)
            ->goals()
            ->wherePivot('is_del','=',0)
            ->wherePivot('start_date','<=',$date)
            ->where(function($query) use ($date) {
                $query->where('user_goal.end_date', '>=', $date)
                    ->orWhere('user_goal.end_date', '=', NULL);
            })
            ->count();
    }

    public function getGoal($goal_id,Request $request)
    {
//    	$validation = Validator::make(Input::all(), [
//      		'user_id'		=> 	'required',		// 用户id
//      		'goal_id'		=>	'required',     // 目标id
//    	]);
//
//    	if($validation->fails()){
//	      return API::response()->array(['status'=>false,'code' => '2001', 'message' => $validation->errors()]);
//	    }

    	$user_id  = $this->auth->user()->user_id;

    	$goal = User::find($user_id)->goals()
            ->wherePivot('goal_id','=',$goal_id)
            ->wherePivot('is_del','=',0)
            ->first();

        if(empty($goal)) {
            return $this->response->error("未制定该目标",500);
        }

        // 检查今天是否打卡
        $user_checkin = Checkin::where('user_id', '=', $user_id)
                    ->where('goal_id', '=', $goal_id)
                    ->where('checkin_day', '=', date('Y-m-d'))
                    ->first();

        // 如果存在该条打卡记录
        if($user_checkin) {
           $goal->is_today_checkin = true;
            $goal->pivot->is_today_checkin = true;

        } else {
            $goal->is_today_checkin = false;
            $goal->pivot->is_today_checkin = false;
        }

        // 检查expect_days
        if($goal->pivot->expect_days==0){
            $goal->pivot->expect_days=ceil((time()-$goal->pivot->start_time)/86400);
        }

        $items = DB::table('user_goal_item')
            ->where('goal_id', $goal_id)
            ->where('user_id',$user_id)
            ->where('is_del','0')
            ->get();

        $goal->items = $items;

        $result = array();

        $result['id'] = $goal_id;
        $result['name'] = $goal->goal_name;
        $result['expect_days'] = $goal->pivot->expect_days;
        $result['total_days'] = $goal->pivot->total_days;
        $result['series_days'] = $goal->pivot->series_days;
        $result['start_date'] = $goal->pivot->start_date;
        $result['end_date'] = $goal->pivot->end_date;
        $result['status'] = $goal->pivot->status;
        $result['items'] = $goal->items;


        return $result;
    }

    public function updateGoal($id,Request $request){

        $goal = Goal::findOrFail($id);

        $messages = [
            'goal_id.required' => '缺少目标ID参数',
        ];

        $validation = Validator::make(Input::all(), [
            'goal_id' => 'required',     // 目标id
            'items' => [],             // 统计项目
            'is_public' => '',             // 是否公开
            'is_push' => '',                // 是否推送
            'push_time' => '',             // 推送时间
        ], $messages);

        if ($validation->fails()) {
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $user_id = $this->auth->user()->user_id;

        // 判断是否已经指定了该目标
        $user_goal = Goal::find($request->goal_id)
            ->users()
            ->wherePivot('user_id', '=', $user_id)
            ->wherePivot('is_del', '=', 0)
            ->first();

        if (!$user_goal) {
            return API::response()->array(['status' => false, 'message' => "未制定该目标"]);
        }

        DB::table('user_goal')->where('id', '=', $user_goal->pivot->id)
            ->update([
                'is_public' =>(int)($request->is_public),
                'is_push' => (int)($request->is_push),
                'remind_time' => $request->is_push == true ? $request->remind_time : ''
            ]);

        $this->_insert_items($user_id, $user_goal->pivot->goal_id, $request->items);

        return API::response()->array(['status' => true, 'message' => "更新成功", "data" => []]);
    }

    public function getGoalChart($goal_id,Request $request)
    {
//        $messages = [
//            'required' => '缺少参数 :attribute',
//        ];
////
//        $validation = Validator::make(Input::all(), [
//            'page' => '',
//            'per_page' => ''
//        ], $messages);

        $user_id  = $this->auth->user()->user_id;

//        $goal = User::find($user_id)->goals()
//            ->wherePivot('goal_id','=',$goal_id)
//            ->wherePivot('is_del','=',0)
//            ->first();

        $mode  = $request->input('mode',"week");

        $end_date  = $request->input('day',date('Y-m-d'));

        $end_dt = new Carbon($end_date);

        $result = [];
        $data = [];

        $total_checkin_days = 0;

        $total_days = 0;
        
        if($mode == 'week') {
            for($i=5;$i>=0;$i--) {
                if($i < 5 ) {
                    $end_dt->subWeek();
                }

                $week = $end_dt->weekOfYear;
                $year = $end_dt->year;

                $start_day = $end_dt->startOfWeek()->toDateString();
                $end_day = $end_dt->endOfWeek()->toDateString();

                $data[$i]['start_date'] = $start_day;
                $data[$i]['end_date'] = $end_day;

                // 获取日期范围内的打卡次数
                $count = DB::table('checkin')
                    ->where('goal_id', $goal_id)
                    ->where('user_id', $user_id)
                    ->whereRaw('YEAR(checkin_day)=' . $year)
                    ->whereRaw('WEEK(checkin_day)=' . $week)
                    ->count();

                $total_checkin_days += $count;

                $data[$i]['checkin_count'] = $count;
                $data[$i]['checkin_rate'] = round($count*100/7);
                $data[$i]['label'] = '第'.$week.'周';
                $data[$i]['week'] = $week;
            }

            $total_days = 42;

            $result['title'] = '第'.$data[0]['week'].'周-第'.$data[5]['week'].'周';

        } else if($mode == 'month'){
            for($i=5;$i>=0;$i--) {
                if($i < 5 ) {
                    $end_dt->subMonth();
                }

                $month = $end_dt->month;
                $year = $end_dt->year;

                $total_days += $end_dt->daysInMonth;

                $start_day = $end_dt->startOfMonth()->toDateString();
                $end_day = $end_dt->startOfMonth()->toDateString();

                $data[$i]['start_date'] = $start_day;
                $data[$i]['end_date'] = $end_day;

                // 获取日期范围内的打卡次数
                $count = DB::table('checkin')
                    ->where('goal_id', $goal_id)
                    ->where('user_id', $user_id)
                    ->whereRaw('YEAR(checkin_day)=' . $year)
                    ->whereRaw('MONTH(checkin_day)=' . $month)
                    ->count();

                $total_checkin_days += $count;

                $data[$i]['checkin_count'] = $count;
                $data[$i]['checkin_rate'] = round($count*100/$end_dt->daysInMonth);
                $data[$i]['label'] = $month.'月';
                $data[$i]['month'] = $month;
            }

            $result['title'] = $data[0]['month'].'月 - '.$data[5]['month'].'月';
        } else if ($mode == 'year') {
            for($i=5;$i>=0;$i--) {
                if($i < 5 ) {
                    $end_dt->subYear();
                }

                $year = $end_dt->year;

                $year_days = $end_dt->isLeapYear()?366:365;

                $total_days += $year_days;

                $start_day = $end_dt->startOfYear()->toDateString();
                $end_day = $end_dt->endOfYear()->toDateString();

                $data[$i]['start_date'] = $start_day;
                $data[$i]['end_date'] = $end_day;

                // 获取日期范围内的打卡次数
                $count = DB::table('checkin')
                    ->where('goal_id', $goal_id)
                    ->where('user_id', $user_id)
                    ->whereRaw('YEAR(checkin_day)=' . $year)
                    ->count();

                $total_checkin_days += $count;

                $data[$i]['checkin_count'] = $count;
                $data[$i]['checkin_rate'] = round($count*100/$year_days);
                $data[$i]['label'] = $year.'年';
                $data[$i]['year'] = $year;
            }

            $result['title'] = $data[0]['year'].'年 - '.$data[5]['year'].'年';
        }

        if($end_date == date('Y-m-d')) {
            $result['next'] = "";
        } else {
            $next_dt =new Carbon($end_date);
            $result['next'] = $next_dt->addDay()->toDateString();
        }

        $prev_dt = new Carbon($data[0]['start_date']);

        $result['prev'] = $prev_dt->subDay()->toDateString();

        $result['data'] = array_values($data);

        $result ['total_days'] = $total_days;
        $result ['checkin_count'] = $total_checkin_days;
        $result ['checkin_rate'] = round($total_checkin_days*100/$total_days);

        return $result;
    }

    public function getGoalEvents($goal_id,Request $request)
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

    	$validation = Validator::make(Input::all(), [
            'page'        =>  '',
            'per_page'        =>  ''
    	],$messages);

        if ($validation->fails()) {
            return API::response()->error(implode(',',$validation->errors()->all()),500);
        }

        $user_id = $this->auth->user()->user_id;

        $page  = $request->input('page',1);
        $per_page = $request->input('per_page',20);

        $events = Event::where('goal_id', $goal_id)
            ->where('is_public','=',1)
            ->orderBy('create_time', 'DESC')->skip($page*$per_page)
            ->take($per_page)->get();

        $result = [];

        foreach ($events as $key => $event) {
            $result[$key]['content'] = $event->content;


            if ($event['type'] == 'USER_CHECKIN') {
                $checkin = DB::table('checkin')
                    ->where('checkin_id', $event->event_value)
                    ->first();
                $result[$key]['content'] = $checkin?$checkin->checkin_content:'';

                $items = DB::table('checkin_item')
                    ->join('user_goal_item', 'user_goal_item.item_id', '=', 'checkin_item.item_id')
                    ->where('checkin_id', $event->event_value)
                    ->get();
                $attaches = DB::table('attachs')
                    ->where('attachable_id', $event->event_value)
                    ->where('attachable_type', 'checkin')
                    ->get();
            }
            // 后去是否点赞过

            $is_like =  Event::find($event->event_id)
                ->likes()
                ->where('user_id','=',$user_id)
                ->first();
            
            $result[$key]['owner'] = $event->user;
            $result[$key]['goal'] = $event->goal;

            $result[$key]['id'] = $event->event_id;
            $result[$key]['like_count'] = $event->like_count;
            $result[$key]['comment_count'] = $event->comment_count;
            $result[$key]['is_hot'] = $event->is_hot;
            $result[$key]['is_public'] = $event->is_public;
            $result[$key]['is_like'] = $is_like?true:false;
            $result[$key]['created_at'] = Carbon::parse($event->created_at)->toDateTimeString();
            $result[$key]['updated_at'] = Carbon::parse($event->updated_at)->toDateTimeString();

        }

//        $events = User::find($user_id)->events()->skip($offset)->take($limit)->get();

        return $result;
    }

    public function new_messages()
    {
        $validation = Validator::make(Input::all(), [
            'user_id'       =>  'required',     // 用户id
        ]);

        if($validation->fails()){
          return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $user_id  = Input::get('user_id');

        $count = Message::where('to_user',$user_id)
                    ->where('status','0')
                    ->count();

        return API::response()->array(['status' => true, 'message' =>"",'count'=>$count]);;

    }

    public function messages()
    {
        // $validation = Validator::make(Input::all(), [
        //     'user_id'       =>  'required',     // 用户id
        // ]);

        // if($validation->fails()){
        //   return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        // }

        $user_id  = $this->auth->user()->user_id;

        $like_count = Message::where('to_user',$user_id)
                    ->where('status',0)
                    ->where('type',2)
                    ->count();


        $comment_count = Message::where('to_user',$user_id)
                    ->where('status',0)
                    ->where('type',3)
                    ->count();


        $fan_count = Message::where('to_user',$user_id)
                    ->where('status',0)
                    ->where('type',4)
                    ->count();


        $at_count = Message::where('to_user',$user_id)
                    ->where('status',0)
                    ->where('type',5)
                    ->count();

        $notice_count = Message::where('to_user',$user_id)
                    ->where('status',0)
                    ->where('type',6)
                    ->count();


        return API::response()->array(['status' => true, 'message'=>'','data'=>compact('like_count','comment_count','at_count','fan_count','like_count','notice_count')])->statusCode(200);

    }

    // 个人资料更新
    public function profile(){
        $user_id = $this->auth->user()->user_id;

        $user = User::find($user_id);
        $nickname = Input::get('nickname');
        $signature = Input::get('signature');
        $user_avatar = Input::get('user_avatar');

        $user->nickname = $nickname;
        $user->signature = $signature;
        $user->user_avatar = $user_avatar;
        $user->save();

        return API::response()->array(['status' => true, 'message'=>'','data'=>''])->statusCode(200);

    }

    // 提交反馈
    public function feedback(Request $request)
    {
        Log::info('反馈请求数据');
        Log::info($request);

        $messages = [
            'required' => '缺少参数 :attribute',
        ];

        $validation = Validator::make(Input::all(), [
            'content'		=> 	'required',		// 内容
            'attaches'      =>  '',             // 附件
            'device'        =>  '',             // 设备信息
            'version'        =>  '',            // 版本
        ],$messages);

        if($validation->fails()){
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $data =  [
            'user_id'=>$this->auth->user()->user_id,
            'content'=>Input::get('content'),
            'device'=>json_encode(Input::get('device')),
            'version'=>Input::get('version'),
            'create_time'=>time(),
        ];

        $feedback_id = DB::table('feedback')->insertGetId($data);

        // 更新附件
        if($attaches =Input::get('attaches')) {
            foreach($attaches as $attach) {
                $attach = Attach::find($attach['id']);
                $attach->attachable_id = $feedback_id;
                $attach->attachable_type = 'feedback';
                $attach->save();
            }
        }

        return API::response()->array(['status' => true, 'message' =>'反馈成功'])->statusCode(200);

    }


    // 关注用户
    public function follow(Request $request)
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

        $validation = Validator::make(Input::all(), [
            'follow_user_id'		=> 	'required',		// 内容
        ],$messages);

        if($validation->fails()){
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $user_id = $this->auth->user()->user_id;

        // 判断是否为用户自己
        if($request->follow_user_id == $user_id) {
            return API::response()->array(['status' => false, 'message' => '亲,你不能关注自己的'])->statusCode(200);
        }

        // 判断是否关注
        $is_follow = DB::table('user_follow')
            ->where('user_id',$user_id)
            ->where('follow_user_id',$request->follow_user_id)
            ->first();

        if($is_follow) {
            return API::response()->array(['status' => false, 'message' => '用户已关注'])->statusCode(200);
        }

        DB::table('user_follow')->insert([
            'user_id'=> $user_id,
            'follow_user_id'=>$request->follow_user_id,
            'create_time'=>time()
        ]);

        // 更新用户表
        $follow_user = User::find($request->follow_user_id);
        $follow_user->increment('fans_count');
        $user = User::find($user_id);
        $user->increment('follow_count');

        //发送消息
        $message = new Message();
        $message->from_user = $user_id;
        $message->to_user = $request->follow_user_id;
        $message->type = 4 ;
        $message->title = '' ;
        $message->content = '' ;
        $message->msgable_id = $request->follow_user_id;
        $message->msgable_type = 'App\User';
        $message->create_time  = time();
        $message->save();

        // 推送
        $content = $user->nickname?$user->nickname:'神秘小伙伴'.'关注了你';

        $push = new MyJpush();
        $push->pushToSingleUser($request->follow_user_id,$content);

        return API::response()->array(['status' => true, 'message' => '关注成功'])->statusCode(200);

    }

    // 关注用户
    public function unfollow(Request $request)
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

        $validation = Validator::make(Input::all(), [
            'follow_user_id'		=> 	'required',		// 内容
        ],$messages);

        if($validation->fails()){
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $user_id = $this->auth->user()->user_id;

        // 判断是否为用户自己
        if($request->follow_user_id == $user_id) {
            return API::response()->array(['status' => false, 'message' => '亲,你不能关注自己的'])->statusCode(200);
        }

        // 判断是否关注
        $is_follow = DB::table('user_follow')
            ->where('user_id',$user_id)
            ->where('follow_user_id',$request->follow_user_id)
            ->first();

        if(!$is_follow) {
            return API::response()->array(['status' => false, 'message' => '未关注该用户'])->statusCode(200);
        }

        DB::table('user_follow')
            ->where('user_id','=',$user_id)
            ->where('follow_user_id','=',$request->follow_user_id)
            ->delete();

        // 更新用户表
        // 更新用户表
        $follow_user = User::find($request->follow_user_id);
        $follow_user ->decrement('fans_count');
        $user = User::find($user_id);
        $user->decrement('follow_count');

        return API::response()->array(['status' => true, 'message' => '关注成功'])->statusCode(200);

    }

    /**
     * 举报
     */
    public function report(Request $request)
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

        $validation = Validator::make(Input::all(), [
            'obj_id'		=> 	'required',		//
            'obj_type'		=> 	'required',		//
            'reason'		=> 	'required',		//
        ],$messages);

        if($validation->fails()){
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $report = new Report();
        $report->user_id = $this->auth->user()->user_id;
        $report->reason = $request->reason;
        $report->reportable_type = $request->obj_type;
        $report->reportable_id = $request->obj_id;
        $report->create_time = time();

        $report->save();

        return API::response()->array(['status' => true, 'message' => '举报成功'])->statusCode(200);

    }


    public function fans(Request $request) {
        // 关注时间排序

        $follow_user_id = $request->user_id;
        $offset =  $request->offset;

        $users = DB::table('user_follow')
            ->join('users','users.user_id','=','user_follow.user_id')
            ->where('follow_user_id','=',$follow_user_id)
            ->orderBy('create_time','asc')
            ->skip($offset)
            ->limit(20)
            ->get();

        return API::response()->array(['status' => true, 'message' => '','data'=>$users]);


    }

    public function follows(Request $request) {
        // 关注时间排序

        $follow_user_id = $request->user_id;
        $offset =  $request->offset;

        $users = DB::table('user_follow')
            ->join('users','users.user_id','=','user_follow.follow_user_id')
            ->where('user_follow.user_id','=',$follow_user_id)
            ->orderBy('create_time','asc')
            ->skip($offset)
            ->limit(20)
            ->get();

        return API::response()->array(['status' => true, 'message' => '','data'=>$users]);

    }

    public function energy(Request $request) {
        // 关注时间排序

        $user_id = $request->user_id;
        $offset =  $request->offset;

        $logs = DB::table('energy')
            ->join('energy_type','energy_type.name','=','energy.obj_type')
            ->where('user_id','=',$user_id)
            ->orderBy('create_time','desc   ')
            ->skip($offset)
            ->limit(20)
            ->get();

        return API::response()->array(['status' => true, 'message' => '','data'=>$logs]);


    }

    public function level(Request $request) {
        // 关注时间排序

        $user_id = $request->user_id;

        $user = User::find($user_id);

        $rank = DB::table('users')
            ->where('checkin_count', '>', $user->checkin_count)
            ->count();

        $levels = DB::table('users')
            ->select(DB::raw('count(*) as count,level'))
            ->groupBy('level')
            ->get();

        $data = [0,0,0,0,0,0,0,0,0];

        foreach($levels as $level){
            $data[$level->level] = $level->count;
        }

        return API::response()->array(['status' => true, 'message' => '','data'=>['level'=>$user->level,'count'=>$user->checkin_count,'rank'=>$rank+1,
        'levels'=>$data]]);


    }
    

}