<?php
/**
 * 用户控制器
 */
namespace App\Http\Controllers\Api\V1;

use Auth;
use Validator;
use API;
use DB;
use Log;

use App\User;
use App\Checkin;
use App\Models\Message as Message;
use App\Models\Attach as Attach;
use App\Models\Report as Report;


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
    public function goals()
    {
    	$user_id  = $this->auth->user()->user_id;

    	$processed_goals = User::find($user_id)
            ->goals()
            ->wherePivot('is_del','=',0)
            ->wherePivot('status','=',0)
            ->orderBy('order','asc')
            ->get();

        $finished_goals = User::find($user_id)
            ->goals()
            ->wherePivot('is_del','=',0)
            ->wherePivot('status','=',1)
            ->get();

//        DB::connection()->enableQueryLog();

        foreach ($processed_goals as $key => $goal) {
            $processed_goals[$key]['pivot']->is_today_checkin = $goal->pivot->last_checkin_time>=strtotime(date('Y-m-d'))?true:false;
            if($goal->pivot->expect_days==0){
                $processed_goals[$key]['pivot']->expect_days = ceil((time()-$goal->pivot->start_time)/86400);
            }

            $items = DB::table('user_goal_item')
                ->where('goal_id', $goal->goal_id)
                ->where('user_id',$user_id)
                ->where('is_del','0')
                ->get();

            $goal->items = $items;
        }

//        foreach ($finished_goals as $key => $goal) {
//            if($goal->pivot->expect_days==0){
//                $goals[$key]['pivot']['expect_days'] = ceil((time()-$goal->pivot->start_time)/86400);
//            }
//        }

        return API::response()->array(['status' => true, 'message' =>'','data'=>['process'=>$processed_goals,'finish'=>$finished_goals]])->statusCode(200);
    }

    public function goal()
    {
    	$validation = Validator::make(Input::all(), [
      		'user_id'		=> 	'required',		// 用户id
      		'goal_id'		=>	'required',     // 目标id
    	]);

    	if($validation->fails()){
	      return API::response()->array(['status'=>false,'code' => '2001', 'message' => $validation->errors()]);
	    }

    	$user_id  = Input::get('user_id');
    	$goal_id  = Input::get('goal_id');

    	$goal = User::find($user_id)->goals()
            ->wherePivot('goal_id','=',$goal_id)
            ->wherePivot('is_del','=',0)
            ->first();

        // TODO 删除code
        if(empty($goal)) {
            return API::response()->array(['status'=>false,'code' => '2001', 'message' =>"未制定该目标"]);
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

        // TODO 删除code
        return API::response()->array(['status'=>true,'code' => '0', 'message' =>"",'data'=>$goal]);;
    }

    public function events()
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

    	$validation = Validator::make(Input::all(), [
      		'user_id'		=> 	'required',		// 用户id
            'limit'        =>  '',              // 偏移
            'offset'        =>  ''              // 偏移
    	],$messages);

    	if($validation->fails()){
	      return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
	    }

    	$user_id  = Input::get('user_id');
        $limit  = Input::get('limit')?Input::get('limit'):20;
        $offset  = Input::get('offset')?Input::get('offset'):0;

        $events = User::find($user_id)->events()->skip($offset)->take($limit)->get();

        return API::response()->array(['status' => true, 'message' => '','data'=>$events])->statusCode(200);

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