<?php
/**
 * 用户控制器
 */

namespace App\Http\Controllers\Api\V2;

use Auth;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Validator;
use API;
use DB;
use Log;

use App\User;
use App\Checkin;
use App\Models\Message as Message;
use App\Goal;
use App\Models\Attach as Attach;
use App\Models\Report as Report;
use App\Event;
use App\Like;
use App\Models\Comment as Comment;


use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class UserController extends BaseController
{

    // 取出用户的基本信息
    public function getUser($user_id, Request $request)
    {

        $user = User::find($user_id);

        $new_user = [];

        if ($user) {


            $is_follow = false;

            // 查询是否关注

            $user_follow = DB::table('user_follow')
                ->where('user_id', $this->auth->user()->user_id)
                ->where('follow_user_id', $user_id)
                ->first();

            if ($user_follow) {
                $is_follow = true;
            }

            $new_user['is_follow'] = $is_follow;

            $new_user['id'] = $user->user_id;
            $new_user['nickname'] = $user->nickname;
            $new_user['signature'] = $user->signature;
            $new_user['fans_count'] = $user->fans_count;
            $new_user['follow_count'] = $user->follow_count;
            $new_user['avatar_url'] = $user->user_avatar;
            $new_user['is_vip'] = $user->is_vip == 1 ? true : false;

        }


        // TODO 判断用户是否存在
        return $new_user;

    }


    public function updateUser($user_id, Request $request)
    {
        Log::info('更新用户信息');
        Log::info($request);

        $input = $request->all();

        if ($input) {
            DB::table('users')->where('user_id', '=', $user_id)
                ->update($input);
        }

        $user = User::findOrFail($user_id);

        $new_user = [];
        $new_user['id'] = $user->user_id;
        $new_user['is_vip'] = $user->is_vip == 1 ? true : false;
        $new_user['created_at'] = date('Y-m-d H:i:s', $user->reg_time);
        $new_user['nickname'] = $user->nickname;
        $new_user['signature'] = $user->signature;
        $new_user['avatar_url'] = $user->user_avatar;
        $new_user['follow_count'] = $user->follow_count;
        $new_user['sex'] = $user->sex;
        $new_user['fans_count'] = $user->fans_count;
        $event_count = Event::where('user_id', $user->user_id)->count();
        $new_user['event_count'] = $event_count;

        return $new_user;
    }

    public function getUserEvents($user_id,Request $request)
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

        $validation = Validator::make(Input::all(), [
            'page' => '',
            'per_page' => ''
        ], $messages);

        if ($validation->fails()) {
            return API::response()->error(implode(',', $validation->errors()->all()), 500);
        }

        $current_user_id = $this->auth->user()->user_id;

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);

        $events = Event::where('user_id', $user_id)
            ->where('is_public', '=', 1)
            ->orderBy('create_time', 'DESC')->skip(($page-1)* $per_page)
            ->take($per_page)->get();

        $result = [];

        foreach ($events as $key => $event) {
            $result[$key]['content'] = $event->content;

            $new_checkin = [];

            if ($event['type'] == 'USER_CHECKIN') {
                $checkin = DB::table('checkin')
                    ->where('checkin_id', $event->event_value)
                    ->first();

                if($checkin) {
                    $result[$key]['content'] = $checkin->checkin_content;
                    $new_checkin['total_days'] = $checkin->total_days;
                    $new_checkin['id'] = $checkin->checkin_id;
                }

                $items = DB::table('checkin_item')
                    ->join('user_goal_item', 'user_goal_item.item_id', '=', 'checkin_item.item_id')
                    ->where('checkin_id', $event->event_value)
                    ->get();

                $attachs = DB::table('attachs')
                    ->where('attachable_id', $event->event_value)
                    ->where('attachable_type','checkin')
                    ->get();

                $new_attachs = [];

                foreach($attachs as $k=>$attach) {
                    $new_attachs[$k]['id'] = $attach->attach_id;
                    $new_attachs[$k]['name'] = $attach->attach_name;
                    $new_attachs[$k]['url'] = "http://www.keepdays.com/uploads/images/".$attach->attach_path.'/'.$attach->attach_name;
                }

                $result[$key]['attachs'] = $new_attachs;
            }

            $result[$key]['checkin'] = $new_checkin;

            // 后去是否点赞过

            $is_like = Event::find($event->event_id)
                ->likes()
                ->where('user_id', '=', $current_user_id)
                ->first();

            $new_user = [];
            $new_user['id'] = $event->user->user_id;
            $new_user['nickname'] = $event->user->nickname;
            $new_user['avatar_url'] = $event->user->user_avatar;

            $result[$key]['user'] = $new_user;

            $goal = [];
            $goal['id'] = $event->goal->goal_id;
            $goal['name'] = $event->goal->goal_name;

            $result[$key]['goal'] = $goal;

            $result[$key]['id'] = $event->event_id;
            $result[$key]['like_count'] = $event->like_count;
            $result[$key]['comment_count'] = $event->comment_count;
            $result[$key]['is_hot'] = $event->is_hot;
            $result[$key]['is_public'] = $event->is_public;
            $result[$key]['is_like'] = $is_like ? true : false;
            $result[$key]['created_at'] = Carbon::parse($event->created_at)->toDateTimeString();
            $result[$key]['updated_at'] = Carbon::parse($event->updated_at)->toDateTimeString();

        }

//        $events = User::find($user_id)->events()->skip($offset)->take($limit)->get();

        return $result;
    }

    // 取出登录用户的目标列表
    public function getGoals(Request $request)
    {
        $messages = [
//            'account.required' => '请输入邮箱地址',
        ];

        $validation = Validator::make(Input::all(), [
            'day' => '',        // 具体日期
        ], $messages);

        if ($validation->fails()) {
            return API::response()->error($validation->errors()->all('</br>:message'), 500);
        }

        $user_id = $this->auth->user()->user_id;

        $date = $request->input("day", date('Y-m-d'));

        		DB::enableQueryLog();

        $goals = User::find($user_id)
            ->goals()
            ->wherePivot('is_del', '=', 0)
            ->wherePivot('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->where('user_goal.end_date', '>=', $date)
                    ->orWhere('user_goal.end_date', '=', NULL);
            })
            ->orderBy('remind_time', 'asc')
            ->get();

//        $laQuery = DB::getQueryLog();

//		$lcWhatYouWant = $laQuery[0]['query'];

//		return $laQuery;

        $result = array();

        foreach ($goals as $key => $goal) {

            $result[$key]['id'] = $goal->goal_id;
            // TODO
            // $goals[$key]['name'] = $goal->pivot->name;
            $result[$key]['name'] = $goal->goal_name;
            $result[$key]['is_checkin'] = $goal->pivot->last_checkin_time >= strtotime(date('Y-m-d')) ? true : false;
            $result[$key]['remind_time'] = $goal->pivot->remind_time ? substr($goal->pivot->remind_time, 0, 5) : null;

            // TODO 修改status 0未开始 1进行中 2已结束
//            if($goal->pivot->status==0) {
//                $result[$key]['status'] = 1;
//            } else if ($goal->pivot->status==1) {
//                $result[$key]['status'] = 2;
//            } else if ($goal->pivot->status== -1) {
//                $result[$key]['status'] = 0;
//            }

            $result[$key]['status'] = $goal->pivot->status+1;

        }

        return API::response()->array($result)->statusCode(200);
    }

    public function getGoalsCalendar(Request $request)
    {
        $messages = [
            'start_date.required' => '请输入开始日期',
            'end_date.required' => '请输入结束日期',
        ];

        $validation = Validator::make(Input::all(), [
            'start_date' => 'required',        // 开始日期
            'end_date' => 'required',        // 结束日期
        ], $messages);

        if ($validation->fails()) {
            return API::response()->error($validation->errors()->all('</br>:message'), 500);
        }

        $user_id = $this->auth->user()->user_id;

        $start_date = $request->input("start_date");
        $format_start_date = Carbon::parse($start_date);;
        $end_date = $request->input("end_date");
        $format_end_date = Carbon::parse($end_date);;

        $diffDays = $format_start_date->diffInDays($format_end_date);

        $result = array();

        for ($i = 0; $i <= $diffDays; $i++) {
            $result[] = $this->_get_goals_by_day($format_start_date->addDays($i)->toDateString());
        }

        return API::response()->array($result)->statusCode(200);
    }

    private function _get_goals_by_day($date)
    {
        $user_id = $this->auth->user()->user_id;

        return User::find($user_id)
            ->goals()
            ->wherePivot('is_del', '=', 0)
            ->wherePivot('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->where('user_goal.end_date', '>=', $date)
                    ->orWhere('user_goal.end_date', '=', NULL);
            })
            ->count();
    }

    public function getGoal($goal_id, Request $request)
    {
//    	$validation = Validator::make(Input::all(), [
//      		'user_id'		=> 	'required',		// 用户id
//      		'goal_id'		=>	'required',     // 目标id
//    	]);
//
//    	if($validation->fails()){
//	      return API::response()->array(['status'=>false,'code' => '2001', 'message' => $validation->errors()]);
//	    }

        $user_id = $this->auth->user()->user_id;

        $goal = User::find($user_id)->goals()
            ->wherePivot('goal_id', '=', $goal_id)
            ->wherePivot('is_del', '=', 0)
            ->first();

        if (empty($goal)) {
            return $this->response->error("未制定该目标", 500);
        }

        // 检查今天是否打卡
        $user_checkin = Checkin::where('user_id', '=', $user_id)
            ->where('goal_id', '=', $goal_id)
            ->where('checkin_day', '=', date('Y-m-d'))
            ->first();

        // 如果存在该条打卡记录
        if ($user_checkin) {
            $goal->is_today_checkin = true;
            $goal->pivot->is_today_checkin = true;

        } else {
            $goal->is_today_checkin = false;
            $goal->pivot->is_today_checkin = false;
        }

        // 检查expect_days
        if ($goal->pivot->expect_days == 0) {
            $goal->pivot->expect_days = ceil((time() - $goal->pivot->start_time) / 86400);
        }

        $items = DB::table('user_goal_item')
            ->where('goal_id', $goal_id)
            ->where('user_id', $user_id)
            ->where('is_del', '0')
            ->get();

        $goal->items = $items;

        $result = array();

        $result['id'] = $goal_id;
        $result['name'] = $goal->pivot->name?$goal->pivot->name:$goal->goal_name;
        $result['desc'] = $goal->pivot->desc;
        $result['expect_days'] = $goal->pivot->expect_days;
        $result['total_days'] = $goal->pivot->total_days;
        $result['series_days'] = $goal->pivot->series_days;
        $result['start_date'] = $goal->pivot->start_date;
        $result['end_date'] = $goal->pivot->end_date;
        $result['status'] = $goal->pivot->status+1;
        $result['items'] = $goal->items;
        $result['is_today_checkin'] = $goal->is_today_checkin;


        return $result;
    }

    public function updateGoal($goal_id, Request $request)
    {

        $goal = Goal::findOrFail($goal_id);

//        $messages = [
//            'goal_id.required' => '缺少目标ID参数',
//        ];
//
//        $validation = Validator::make(Input::all(), [
//            'goal_id' => 'required',     // 目标id
//            'items' => [],             // 统计项目
//            'is_public' => '',             // 是否公开
//            'is_push' => '',                // 是否推送
//            'push_time' => '',             // 推送时间
//        ], $messages);
//
//        if ($validation->fails()) {
//            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
//        }

        $user_id = $this->auth->user()->user_id;

        // 判断是否已经指定了该目标
        $user_goal = Goal::find($goal_id)
            ->users()
            ->wherePivot('user_id', '=', $user_id)
            ->wherePivot('is_del', '=', 0)
            ->first();

        if (!$user_goal) {
            return $this->response->error("未制定该目标", 500);
        }

        $input = $request->all();

        if ($input) {

            if($request->has('start_date')) {
                $start_date = $request->input('start_date');
            } else {
                $start_date = $request->input('start_date');
            }

            if($request->has('end_date')) {
                $end_date = $request->input('end_date');
            } else {
                $end_date = $request->input('end_date');
            }

            if($end_date) {
                if($start_date > $end_date) {
                    return $this->response->error("开始时间不得大于结束时间", 500);
                }

                $start_dt = Carbon::parse($start_date);
                $end_dt = Carbon::parse($end_date);

                $expect_days = $start_dt->diffInDays($end_dt);

                $input['expect_days'] = $expect_days;
            }


            DB::table('user_goal')->where('id', '=', $user_goal->pivot->id)
                ->update($input);
        }

//        $this->_insert_items($user_id, $user_goal->pivot->goal_id, $request->items);

        return $goal;
    }

    public function getGoalWeek($goal_id, Request $request)
    {

        $user_id = $this->auth->user()->user_id;

        $dt = new Carbon();

        $start_day = $dt->startOfWeek();

        $result = [];

        for ($i = 0; $i < 7; $i++) {

            if ($i > 0) {
                $dt->addDay();
            }

            $is_checkin = DB::table('checkin')
                ->where('goal_id', $goal_id)
                ->where('user_id', $user_id)
                ->where('checkin_day', $dt->toDateString())
                ->get();
            $result[$i] = $is_checkin ? true : false;
        }

        return $result;

    }

    public function getGoalDay($goal_id, Request $request)
    {

        $messages = [
            'day.required' => '缺少目标ID参数',
        ];

        $validation = Validator::make(Input::all(), [
            'day' => 'required',     // 目标id
        ], $messages);

        if ($validation->fails()) {
            return $this->response->error(implode(',', $validation->errors()), 500);
        }

        $day = $request->input('day', date('Y-m-d'));

        $user_id = $this->auth->user()->user_id;

        $event = Event::where('goal_id', $goal_id)
            ->where('user_id', $user_id)
            ->whereRaw("DATE_FORMAT(created_at,'%Y-%m-%d') = '" . $day . "'")
            ->first();


        $new_event = (object)[];

        if ($event) {
            $new_event->id = $event->event_id;
            $new_event->content = $event->event_content;

            if ($event['type'] == 'USER_CHECKIN') {
                $checkin = $event->checkin;

                if ($checkin) {
                    $new_event->content = $checkin->checkin_content;

                    $new_checkin = [];

                    $new_checkin['day'] = $checkin->checkin_day;
                    $new_checkin['total_days'] = $checkin->total_days;

                    $new_event->checkin = $new_checkin;
                }


                $items = DB::table('checkin_item')
                    ->join('user_goal_item', 'user_goal_item.item_id', '=', 'checkin_item.item_id')
                    ->where('checkin_id', $event->event_value)
                    ->get();
                $attachs = DB::table('attachs')
                    ->where('attachable_id', $event->event_value)
                    ->where('attachable_type', 'checkin')
                    ->get();

                $new_attachs = [];

                foreach ($attachs as $k => $attach) {
                    $new_attachs[$k]['id'] = $attach->attach_id;
                    $new_attachs[$k]['name'] = $attach->attach_name;
                    $new_attachs[$k]['path'] = $attach->attach_path;
                    $new_attachs[$k]['url'] = "http://www.keepdays.com/uploads/images/" . $attach->attach_path . '/' . $attach->attach_name;

                }

                $new_event->attachs = $new_attachs;

                $new_event->user = $event->user;
                $new_event->goal = $event->goal;
            }
        }

        return response()->json($new_event);

//        return $new_event;

    }

    public function getGoalCalendar($goal_id, Request $request)
    {

        $user_id = $this->auth->user()->user_id;

        // 获取所有的打卡日期

        $days = DB::table('checkin')
            ->where('goal_id', $goal_id)
            ->where('user_id', $user_id)
            ->select('checkin_day')
            ->get();

        return $days;
    }


    public function getGoalChart($goal_id, Request $request)
    {
//        $messages = [
//            'required' => '缺少参数 :attribute',
//        ];
////
//        $validation = Validator::make(Input::all(), [
//            'page' => '',
//            'per_page' => ''
//        ], $messages);

        $user_id = $this->auth->user()->user_id;

//        $goal = User::find($user_id)->goals()
//            ->wherePivot('goal_id','=',$goal_id)
//            ->wherePivot('is_del','=',0)
//            ->first();

        $mode = $request->input('mode', "week");

        $end_date = $request->input('day', date('Y-m-d'));

        $end_dt = new Carbon($end_date);

        $result = [];
        $data = [];

        $total_checkin_days = 0;

        $total_days = 0;

        if ($mode == 'week') {
            for ($i = 5; $i >= 0; $i--) {
                if ($i < 5) {
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
                $data[$i]['checkin_rate'] = round($count * 100 / 7);
                $data[$i]['label'] = '第' . $week . '周';
                $data[$i]['week'] = $week;
            }

            $total_days = 42;

            $result['title'] = '第' . $data[0]['week'] . '周-第' . $data[5]['week'] . '周';

        } else if ($mode == 'month') {
            for ($i = 5; $i >= 0; $i--) {
                if ($i < 5) {
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
                $data[$i]['checkin_rate'] = round($count * 100 / $end_dt->daysInMonth);
                $data[$i]['label'] = $month . '月';
                $data[$i]['month'] = $month;
            }

            $result['title'] = $data[0]['month'] . '月 - ' . $data[5]['month'] . '月';
        } else if ($mode == 'year') {
            for ($i = 5; $i >= 0; $i--) {
                if ($i < 5) {
                    $end_dt->subYear();
                }

                $year = $end_dt->year;

                $year_days = $end_dt->isLeapYear() ? 366 : 365;

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
                $data[$i]['checkin_rate'] = round($count * 100 / $year_days);
                $data[$i]['label'] = $year . '年';
                $data[$i]['year'] = $year;
            }

            $result['title'] = $data[0]['year'] . '年 - ' . $data[5]['year'] . '年';
        }

        if ($end_date == date('Y-m-d')) {
            $result['next'] = "";
        } else {
            $next_dt = new Carbon($end_date);
            $result['next'] = $next_dt->addDay()->toDateString();
        }

        $prev_dt = new Carbon($data[0]['start_date']);

        $result['prev'] = $prev_dt->subDay()->toDateString();

        $result['data'] = array_values($data);

        $result ['total_days'] = $total_days;
        $result ['checkin_count'] = $total_checkin_days;
        $result ['checkin_rate'] = round($total_checkin_days * 100 / $total_days);

        return $result;
    }

    public function getGoalEvents($goal_id, Request $request)
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

        $validation = Validator::make(Input::all(), [
            'page' => '',
            'per_page' => ''
        ], $messages);

        if ($validation->fails()) {
            return API::response()->error(implode(',', $validation->errors()->all()), 500);
        }

        $user_id = $this->auth->user()->user_id;

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);

        $events = Event::where('goal_id', $goal_id)
            ->where('is_public', '=', 1)
            ->orderBy('create_time', 'DESC')->skip(($page-1)* $per_page)
            ->take($per_page)->get();

        $result = [];

        foreach ($events as $key => $event) {
            $result[$key]['content'] = $event->content;

            $new_checkin = [];

            if ($event['type'] == 'USER_CHECKIN') {
                $checkin = DB::table('checkin')
                    ->where('checkin_id', $event->event_value)
                    ->first();

                if($checkin) {
                    $result[$key]['content'] = $checkin->checkin_content;
                    $new_checkin['total_days'] = $checkin->total_days;
                    $new_checkin['id'] = $checkin->checkin_id;
                }

                $items = DB::table('checkin_item')
                    ->join('user_goal_item', 'user_goal_item.item_id', '=', 'checkin_item.item_id')
                    ->where('checkin_id', $event->event_value)
                    ->get();

                $attachs = DB::table('attachs')
                    ->where('attachable_id', $event->event_value)
                    ->where('attachable_type','checkin')
                    ->get();

                $new_attachs = [];

                foreach($attachs as $k=>$attach) {
                    $new_attachs[$k]['id'] = $attach->attach_id;
                    $new_attachs[$k]['name'] = $attach->attach_name;
                    $new_attachs[$k]['url'] = "http://www.keepdays.com/uploads/images/".$attach->attach_path.'/'.$attach->attach_name;
                }

                $result[$key]['attachs'] = $new_attachs;
            }

            $result[$key]['checkin'] = $new_checkin;

            // 后去是否点赞过

            $is_like = Event::find($event->event_id)
                ->likes()
                ->where('user_id', '=', $user_id)
                ->first();

            $new_user = [];
            $new_user['id'] = $event->user->user_id;
            $new_user['nickname'] = $event->user->nickname;
            $new_user['avatar_url'] = $event->user->user_avatar;

            $result[$key]['user'] = $new_user;

            $goal = [];
            $goal['id'] = $event->goal->goal_id;
            $goal['name'] = $event->goal->goal_name;

            $result[$key]['goal'] = $goal;

            $result[$key]['id'] = $event->event_id;
            $result[$key]['like_count'] = $event->like_count;
            $result[$key]['comment_count'] = $event->comment_count;
            $result[$key]['is_hot'] = $event->is_hot;
            $result[$key]['is_public'] = $event->is_public;
            $result[$key]['is_like'] = $is_like ? true : false;
            $result[$key]['created_at'] = Carbon::parse($event->created_at)->toDateTimeString();
            $result[$key]['updated_at'] = Carbon::parse($event->updated_at)->toDateTimeString();

        }

//        $events = User::find($user_id)->events()->skip($offset)->take($limit)->get();

        return $result;
    }

    public function getNewMessages()
    {
        $user_id = $this->auth->user()->user_id;

        $total_count = Message::where('to_user', $user_id)
            ->where('status', '0')
            ->count();

        $like_count = Message::where('to_user', $user_id)
            ->where('status', 0)
            ->where('type', 2)
            ->count();

        $comment_count = Message::where('to_user', $user_id)
            ->where('status', 0)
            ->where('type', 3)
            ->count();

        $fan_count = Message::where('to_user', $user_id)
            ->where('status', 0)
            ->where('type', 4)
            ->count();


        $at_count = Message::where('to_user', $user_id)
            ->where('status', 0)
            ->where('type', 5)
            ->count();

        $notice_count = Message::where('to_user', $user_id)
            ->where('status', 0)
            ->where('type', 6)
            ->count();

        $result = [];
        $result['total_count'] = $total_count;
        $result['like_count'] = $like_count;
        $result['comment_count'] = $comment_count;
        $result['at_count'] = $at_count;
        $result['notice'] = $notice_count;

        return $result;
    }

    public function messages()
    {
        // $validation = Validator::make(Input::all(), [
        //     'user_id'       =>  'required',     // 用户id
        // ]);

        // if($validation->fails()){
        //   return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        // }

        $user_id = $this->auth->user()->user_id;

        $like_count = Message::where('to_user', $user_id)
            ->where('status', 0)
            ->where('type', 2)
            ->count();


        $comment_count = Message::where('to_user', $user_id)
            ->where('status', 0)
            ->where('type', 3)
            ->count();


        $fan_count = Message::where('to_user', $user_id)
            ->where('status', 0)
            ->where('type', 4)
            ->count();


        $at_count = Message::where('to_user', $user_id)
            ->where('status', 0)
            ->where('type', 5)
            ->count();

        $notice_count = Message::where('to_user', $user_id)
            ->where('status', 0)
            ->where('type', 6)
            ->count();


        return API::response()->array(['status' => true, 'message' => '', 'data' => compact('like_count', 'comment_count', 'at_count', 'fan_count', 'like_count', 'notice_count')])->statusCode(200);

    }

    // 个人资料更新
    public function profile()
    {
        $user_id = $this->auth->user()->user_id;

        $user = User::find($user_id);
        $nickname = Input::get('nickname');
        $signature = Input::get('signature');
        $user_avatar = Input::get('user_avatar');

        $user->nickname = $nickname;
        $user->signature = $signature;
        $user->user_avatar = $user_avatar;
        $user->save();

        return API::response()->array(['status' => true, 'message' => '', 'data' => ''])->statusCode(200);

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
            'content' => 'required',        // 内容
            'type' => '',             // 附件
            'contact' => '',             // 附件
            'attaches' => '',             // 附件
            'device' => '',             // 设备信息
            'app_version' => '',            // 版本
            'web_version' => ''            // 版本

        ], $messages);

        if ($validation->fails()) {
            return $this->response->error(implode(',', $validation->errors()), 500);
        }

        $data = [
            'user_id' => $this->auth->user()->user_id,
            'type_id' => $request->input('type'),
            'content' => $request->input('content'),
            'device' =>  json_encode( $request->input('device')),
            'version' => $request->input('app_version'),
            // TODO 移除version字段
            'app_version' => $request->input('app_version'),
            'web_version' => $request->input('web_version'),
            'create_time' => time(),
        ];

        $feedback_id = DB::table('feedback')->insertGetId($data);

        // 更新附件
        if ($attaches = $request->input('attaches')) {
            foreach ($attaches as $attach) {
                $attach = Attach::find($attach['id']);
                $attach->attachable_id = $feedback_id;
                $attach->attachable_type = 'feedback';
                $attach->save();
            }
        }

        return $this->response->noContent();

    }


    // 关注用户
    public function follow($target_id, Request $request)
    {
        // 判断目标用户是否不存在
        $follow_user = User::find($target_id);

        if (!$follow_user) {
            return $this->response->error('用户不存在', 500);
        }

        // TODO 判断用户状态

        $user_id = $this->auth->user()->user_id;

        // 判断是否为用户自己
        if ($target_id == $user_id) {
            return $this->response->error('关注对象不能为自己', 500);
        }

        // 判断是否关注
        $is_follow = DB::table('user_follow')
            ->where('user_id', $user_id)
            ->where('follow_user_id', $target_id)
            ->first();

        if ($is_follow) {
            return $this->response->error('请勿重复关注', 500);
        }

        DB::table('user_follow')->insert([
            'user_id' => $user_id,
            'follow_user_id' => $target_id,
            'create_time' => time()
        ]);

        // 更新用户表
        $follow_user->increment('fans_count');
        $user = User::find($user_id);
        $user->increment('follow_count');

        //发送消息
        $message = new Message();
        $message->from_user = $user_id;
        $message->to_user = $target_id;
        $message->type = 4;
        $message->title = '';
        $message->content = '';
        $message->msgable_id = $request->$target_id;
        $message->msgable_type = 'App\User';
        $message->create_time = time();
        $message->save();

        // 推送
//        $content = $user->nickname?$user->nickname:'神秘小伙伴'.'关注了你';

//        $push = new MyJpush();
//        $push->pushToSingleUser($request->follow_user_id,$content);

        return $this->response->noContent();

    }


    // 关注用户
    public function unFollow($target_id, Request $request)
    {
        // 判断目标用户是否不存在
        $follow_user = User::find($target_id);

        if (!$follow_user) {
            return $this->response->error('用户不存在', 500);
        }

        // TODO 判断用户状态

        $user_id = $this->auth->user()->user_id;
        // 判断是否为用户自己
        if ($target_id == $user_id) {
            return $this->response->error('关注对象不能为自己', 500);
        }

        // 判断是否关注
        $is_follow = DB::table('user_follow')
            ->where('user_id', $user_id)
            ->where('follow_user_id', $target_id)
            ->first();

        if (!$is_follow) {
            return $this->response->error('已取消关注', 500);
        }

        DB::table('user_follow')
            ->where('user_id', $user_id)
            ->where('follow_user_id', $target_id)
            ->delete();

        // 更新用户表
        $follow_user->decrement('fans_count');
        $user = User::find($user_id);
        $user->decrement('follow_count');

        return $this->response->noContent();

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
            'obj_id' => 'required',        //
            'obj_type' => 'required',        //
            'reason' => 'required',        //
        ], $messages);

        if ($validation->fails()) {
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


    public function getFans($user_id,Request $request)
    {
        // 关注时间排序


        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);


        $users = DB::table('user_follow')
            ->join('users', 'users.user_id', '=', 'user_follow.user_id')
            ->where('follow_user_id', '=', $user_id)
            ->orderBy('create_time', 'asc')
            ->skip(($page-1)*$per_page)
            ->limit($per_page)
            ->get();

        $new_users = [];

        foreach($users as $k=>$user) {

//            $new_user = [];

            $new_users[$k]['id'] = $user->user_id;
            $new_users[$k]['nickname'] =  $user->nickname;
            $new_users[$k]['signature'] =  $user->signature;
            $new_users[$k]['avatar_url'] =  $user->user_avatar;

            $is_follow = DB::table('user_follow')
                ->where('user_id',$user_id)
                ->where('follow_user_id',$user->user_id)
                ->first();

            // 判断是否关注该用户
            $new_users[$k]['is_follow'] = $is_follow?true:false;

//            $new_users[$k]['user'] = $new_user;

        }

        return $new_users;

    }

    public function getFollowings($user_id,Request $request)
    {
        // 关注时间排序

        $page = $request->page;
        $per_page = $request->per_page;


        $users = DB::table('user_follow')
            ->join('users', 'users.user_id', '=', 'user_follow.follow_user_id')
            ->where('user_follow.user_id', '=', $user_id)
            ->orderBy('create_time', 'asc')
            ->skip($page)
            ->limit($per_page)
            ->get();


        $new_users = [];

        foreach($users as $k=>$user) {

//            $new_user = [];

            $new_users[$k]['id'] = $user->user_id;
            $new_users[$k]['nickname'] =  $user->nickname;
            $new_users[$k]['signature'] =  $user->signature;
            $new_users[$k]['avatar_url'] =  $user->user_avatar;

            $is_follow = DB::table('user_follow')
                ->where('user_id',$user_id)
                ->where('follow_user_id',$user->user_id)
                ->first();

            // 判断是否关注该用户
            $new_users[$k]['is_follow'] = $is_follow?true:false;

//            $new_users[$k]['user'] = $new_user;

        }

        return $new_users;

//        return API::response()->array(['status' => true, 'message' => '', 'data' => $users]);

    }

    public function energy(Request $request)
    {
        // 关注时间排序

        $user_id = $request->user_id;
        $offset = $request->offset;

        $logs = DB::table('energy')
            ->join('energy_type', 'energy_type.name', '=', 'energy.obj_type')
            ->where('user_id', '=', $user_id)
            ->orderBy('create_time', 'desc   ')
            ->skip($offset)
            ->limit(20)
            ->get();

        return API::response()->array(['status' => true, 'message' => '', 'data' => $logs]);


    }

    public function level(Request $request)
    {
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

        $data = [0, 0, 0, 0, 0, 0, 0, 0, 0];

        foreach ($levels as $level) {
            $data[$level->level] = $level->count;
        }

        return API::response()->array(['status' => true, 'message' => '', 'data' => ['level' => $user->level, 'count' => $user->checkin_count, 'rank' => $rank + 1,
            'levels' => $data]]);

    }

    public function getFanMessages(Request $request)
    {
        $user_id = $this->auth->user()->user_id;

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);

        $messages = DB::table('messages')
            ->where('type', 4)
            ->where('to_user', $user_id)
            ->orderBy('messages.status')
            ->orderBy('messages.create_time', 'desc')
            ->skip(($page - 1) * $per_page)
            ->take($per_page)
            ->get();

        // 修改所有未读的状态为已读
        DB::table('messages')
            ->where('to_user', $user_id)
            ->where('type', 4)
            ->where('status', 0)
            ->update([
                'status' => 1
            ]);

        $new_messages = [];

        foreach ($messages as $k => $message) {
            $new_messages[$k]['id'] = $message->message_id;
            $new_messages[$k]['created_at'] = date('Y-m-d H:i:s', $message->create_time);

            $user = User::find($message->from_user);

            $is_follow = DB::table('user_follow')
                ->where('user_id', $user_id)
                ->where('follow_user_id', $user->user_id)
                ->first();

            // 判断是否关注该用户

            $new_user = [];

            $new_user['is_follow'] = $is_follow ? true : false;
            $new_user['id'] = $user->user_id;
            $new_user['nickname'] = $user->nickname;
            $new_user['avatar_url'] = $user->user_avatar;

            $new_messages[$k]['user'] = $new_user;


        }


        return $new_messages;
    }


    public function getCommentMessages(Request $request)
    {
        $user_id = $this->auth->user()->user_id;

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);

        $messages = DB::table('messages')
            ->where('type', 3)
            ->where('to_user', $user_id)
            ->where('msgable_id', '<>', null)
            ->orderBy('messages.status')
            ->orderBy('messages.create_time', 'desc')
            ->skip(($page - 1) * $per_page)
            ->take($per_page)
            ->get();

        // 修改所有未读的状态为已读
        DB::table('messages')
            ->where('to_user', $user_id)
            ->where('type', 3)
            ->where('status', 0)
            ->update([
                'status' => 1
            ]);

        $new_messages = [];

        foreach ($messages as $k => $message) {
            $new_messages[$k]['id'] = $message->message_id;
            $new_messages[$k]['created_at'] = date('Y-m-d H:i:s', $message->create_time);

            $user = User::find($message->from_user);

            $new_user = [];

            $new_user['id'] = $user->user_id;
            $new_user['nickname'] = $user->nickname;
            $new_user['avatar_url'] = $user->user_avatar;

            $new_messages[$k]['user'] = $new_user;

            $comment = Comment::find($message->msgable_id);

            $new_comment = [];
            $new_comment['id'] = $comment->comment_id;
            $new_comment['content'] = $comment->content;
            $new_comment['event_id'] = $comment->event_id;

            if ($comment->parent_id == 0) {
                $event = Event::findOrFail($comment->event_id);
                if ($event->type == 'USER_CHECKIN') {
                    $new_comment['source'] = $event->checkin->checkin_content;
                } else {
                    $new_comment['source'] = $event->event_content;
                }
            } else {
                // 获取回复的评论对象
                if ($comment->reply_id > 0) {
                    $reply_comment = Comment::find($comment->reply_id);
                } else {
                    $reply_comment = Comment::find($comment->parent_id);
                }
                $new_comment['source'] = $reply_comment->content;

            }

            $new_messages[$k]['comment'] = $new_comment;


        }


        return $new_messages;
    }

    public function getLikeMessages(Request $request)
    {
        $user_id = $this->auth->user()->user_id;

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);

        $messages = DB::table('messages')
            ->where('type', 2)
            ->where('to_user', $user_id)
            ->where('msgable_id', '<>', null)
            ->orderBy('messages.status')
            ->orderBy('messages.create_time', 'desc')
            ->skip(($page - 1) * $per_page)
            ->take($per_page)
            ->get();

        // 修改所有未读的状态为已读
        DB::table('messages')
            ->where('to_user', $user_id)
            ->where('type', 2)
            ->where('status', 0)
            ->update([
                'status' => 1
            ]);

        $new_messages = [];

        foreach ($messages as $k => $message) {


            $like = Like::find($message->msgable_id);
            if (!$like) continue;

            $new_like = [];
            $new_like['id'] = $like->like_id;
            $new_like['event_id'] = $like->event_id;

            $event = Event::findOrFail($like->event_id);
            if ($event->type == 'USER_CHECKIN') {

                if (!$event->checkin) continue;
                $new_like['source'] = $event->checkin->checkin_content;
            } else {
                $new_like['source'] = $event->event_content;
            }

            $new_message = [];

            $new_message['like'] = $new_like;

            $new_message['id'] = $message->message_id;
            $new_message['created_at'] = date('Y-m-d H:i:s', $message->create_time);

            $user = User::find($message->from_user);

            $new_user = [];
            $new_user['id'] = $user->user_id;
            $new_user['nickname'] = $user->nickname;
            $new_user['avatar_url'] = $user->user_avatar;

            $is_follow = DB::table('user_follow')
                ->where('user_id', $user_id)
                ->where('follow_user_id', $user->user_id)
                ->first();

            $new_user['is_follow'] = $is_follow ? true : false;
            $new_message['user'] = $new_user;

            array_push($new_messages, $new_message);
        }


        return $new_messages;
    }

    public function deleteGoal($goal_id)
    {

        $user_id = $this->auth->user()->user_id;

        $user_goal = Goal::find($goal_id)
            ->users()
            ->wherePivot('user_id', '=', $user_id)
            ->wherePivot('is_del', '=', 0)
            ->first();

        // var_dump($user_goal);

        // 更改user_goal表的is_del字段
        if ($user_goal) {
            User::find($user_id)
                ->goals()
                ->wherePivot('is_del', '=', 0)
                ->updateExistingPivot($goal_id, ['is_del' => 1]);

            return $this->response->noContent();

        } else {
            return $this->response->error("未设定该目标", 500);
        }
    }

    public function checkinGoal($goal_id, Request $request)
    {
        Log::info("打卡信息");
        Log::info($request);

        $validation = Validator::make(Input::all(), [
            'content' => '',    // 备注
            'is_public' => '',          // 是否公开
            'day' => 'date',        // 打卡类型
            'items' => '',          // 项目
            'attaches' => '',
        ]);

        if ($validation->fails()) {
            return $this->response->error(implode(',', $validation->errors()), 500);
        }

        $day = $request->input('day', date('Y-m-d'));
        $content = $request->input('content');

        $user_id = $this->auth->user()->user_id;

        $user_goal = User::find($user_id)
            ->goals()
            ->wherePivot('goal_id', '=', $goal_id)
            ->wherePivot('is_del', '=', 0)
            ->first();

        if (empty($user_goal)) {
            return $this->response->error('未设定该目标', 500);
        }

        if($user_goal['start_date'] > date('Y-m-d')) {
            return $this->response->error('目标还未开始', 500);
        }

        if($user_goal['end_date']) {
            if($user_goal['end_date'] < date('Y-m-d')) {
                return $this->response->error('目标已结束', 500);
            }
        }

        $series_days = $user_goal->pivot->series_days;

        // 获取当天的打卡记录
        $user_checkin = Checkin::where('user_id', '=', $user_id)
            ->where('goal_id', '=', $goal_id)
            ->where('checkin_day', '=', $day)
            ->first();

        // 如果存在该条打卡记录
        if ($user_checkin) {
            return $this->response->error('今日已打卡', 500);
        }

        $checkin = new Checkin();
        $checkin->checkin_content = nl2br($content);
        $checkin->checkin_day = $day;
        $checkin->obj_id = $goal_id;
        $checkin->goal_id = $goal_id;
        $checkin->obj_type = 'GOAL';
        $checkin->user_id = $user_id;
        $checkin->is_public = $request->is_public;
        $checkin->checkin_time = time();
        $checkin->save();

//        if(!$isReCheckin) {
//            if($series_days<10){
//                $add_energy = 5;
//            }else if ($series_days>=10&&$series_days<30) {
//                $add_energy = 10;
//            } else if ($series_days>=30&&$series_days<60) {
//                $add_energy = 15;
//            }else if ($series_days>=60) {
//                $add_energy = 20;
//            }
//        }

        // 如果存在该条打卡记录
        if (date('Y-m-d', $user_goal->pivot->last_checkin_time) == date("Y-m-d", strtotime("-1 day", strtotime($day)))) {
            $series_days += 1;
        } else {
            $series_days = 1;
        }

        $total_days = $user_goal->pivot->total_days;

        $total_days++;

        $checkin->total_days = $total_days;
        $checkin->series_days = $series_days;

        $checkin->save();

        // 插入items
        $items = Input::get('items');
        if (!empty($items)) {
            foreach ($items as $item) {
                DB::table('checkin_item')
                    ->insert([
                        'checkin_id' => $checkin->checkin_id,
                        'item_id' => $item['item_id'],
                        'item_value' => $item['item_expect']
                    ]);
            }
        }

        // 更新附件
        if ($attachs = $request->input('attachs')) {
            foreach ($attachs as $attach) {
                $attach = Attach::find($attach['id']);
                $attach->attachable_id = $checkin->checkin_id;
                $attach->attachable_type = 'checkin';
                $attach->save();
            }
        }

//            $data = [
//                'series_days'=>$series_days,
//                'total_days'=>$total_days,
//            ];

        $user_goal->pivot->total_days = $total_days;
        $user_goal->pivot->series_days = $series_days;
        $user_goal->pivot->last_checkin_time = $day < date('Y-m-d') ? strtotime($day) + 86439 : time();
        $user_goal->pivot->save();
        // $user_goal->updateExistingPivot();
//
//            User::find($user_id)
//                ->goals()
//                ->wherePivot('is_del','=',0)
//                ->updateExistingPivot($obj_id,$data);

        User::find($user_id)->increment('checkin_count', 1);
        User::find($user_id)->increment('energy_count', 1);

//        if($add_energy == 0) {
//            $energy = new Energy();
//            $energy->user_id = $user_id;
//            $energy->change = $add_energy;
//            $energy->obj_type = 'checkin';
//            $energy->obj_id = $checkin->checkin_id;
//            $energy->create_time = time();
//            $energy->save();
//        }

        $event = new Event();
        $event->goal_id = $goal_id;
        $event->user_id = $user_id;
        $event->event_value = $checkin->checkin_id;
        $event->type = 'USER_CHECKIN';
        $event->is_public = (int)$request->is_public;
        $event->create_time = time();

        $event->save();

        //更新用户目标表
        if ($content) {
            $this->_parse_content($content, $user_id, $event->event_id);
        }

        return response()->json($checkin);

    }

    private function _parse_content($content, $user_id, $event_id)
    {
        $topic_pattern = "/\#([^\#|.]+)\#/";
        preg_match_all($topic_pattern, $content, $topic_array);
        foreach ($topic_array[0] as $v) {
            // 查找是否存在

            $name = str_replace('#', '', $v);

            $topic = Topic::where('name', '=', $name)->first();

            if (!$topic) {
                $topic = new Topic();
                $topic->name = $name;
                $topic->create_time = time();
                $topic->create_user = $user_id;
            }

            // 插入对应关系
            DB::table('event_topic')->insert(['topic_id' => $topic->id, 'event_id' => $event_id]);

            $topic->follow_count += 1;
            $topic->save();
        }

    }

    public function changePassword(Request $request)
    {
        $validation = Validator::make(Input::all(), [
            'old_password' => 'required',
            'new_password' => 'required',
            'new_password_check' => 'required'
        ]);

        if ($validation->fails()) {
            return $this->response->error(implode(',', $validation->errors()), 500);
        }

        $user = $this->auth->user();

        // 检查旧密码
        if ($user->passwd != md5($request->old_password . $user->salt)) {
            return $this->response->error('原密码错误', 500);
        }

        // 生成新密码
        $salt = rand(1000, 9999);
        $user->passwd = md5($request->new_password . $salt);
        $user->salt = $salt;
        $user->save();

        return $this->response->noContent();

    }


}