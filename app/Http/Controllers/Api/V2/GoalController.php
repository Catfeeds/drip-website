<?php

namespace App\Http\Controllers\Api\V2;

use Auth;
use Validator;

use App\User;
use App\Goal;
use App\Checkin;
use App\Models\Event;
use App\Models\UserGoal;
use App\Models\Energy;
use App\Models\Attach;
use Carbon\Carbon;

use API;
use DB;
use Log;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Api\V2\Transformers\UserGoalTransformer;
use League\Fractal\Serializer\ArraySerializer;

class GoalController extends BaseController
{


    public function info(Request $request)
    {
        $messages = [
            'goal_id.required' => '请输入目标名称',
        ];

        $validation = Validator::make(Input::all(), [
            'goal_id' => 'required|',     // 名称
        ], $messages);

        if ($validation->fails()) {
            return API::response()->array(['status' => false, 'message' => $validation->errors()->all('</br>:message')])->statusCode(200);
        }

        $user_id = $this->auth->user()->id;

        $goal = Goal::find($request->goal_id);

        $is_follow = false;

        // 判断是否已经指定了该目标
        $user_goal = DB::table('user_goals')
            ->where('goal_id', '=', $request->goal_id)
            ->where('user_id', '=', $user_id)
            ->where('is_del', '=', 0)
            ->first();

        if ($user_goal) {
            $is_follow = true;
        }

        $goal->create_user = User::find($goal->create_user);

        $goal->is_follow = $is_follow;


        return API::response()->array(['status' => true, 'message' => '', 'data' => $goal])->statusCode(200);

    }

    /**
     * 获取所有目标
     */
    public function all(Request $request)
    {

        if ($request->q) {
            $goals = Goal::where('name', 'like', '%' . $request->q . '%')
                ->orderBy('follow_nums', 'desc')
                ->take(20)->get();
        } else {
            $goals = Goal::orderBy('follow_nums', 'desc')
                ->take(20)->get();
        }

        return API::response()->array(['status' => true, 'message' => '', 'data' => $goals])->statusCode(200);
    }


    public function getUserGoals($user_id,Request $request)
    {

        $date = $request->input("day", date('Y-m-d'));

        $user_goals = UserGoal::where('user_id','=',$user_id)
            ->where('is_public','=',1)
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->where('user_goals.end_date', '>=', $date)
                    ->orWhere('user_goals.end_date', '=', NULL);
            })
            ->orderBy('user_goals.status', 'asc')
            ->orderBy('user_goals.order', 'asc')
            ->get();

        return $this->response->collection($user_goals, new UserGoalTransformer(),[],function($resource, $fractal){
            $fractal->setSerializer(new ArraySerializer());
        });
    }


    /**
     * 目标排序
     */
    public function reorder(Request $request)
    {
        $order = $request->order;
        $user_id = $this->auth->user()->id;

        if (is_array($order) && !empty($order)) {
            foreach ($order as $k => $v) {
                DB::table('user_goals')
                    ->where('user_id', '=', $user_id)
                    ->where('goal_id', '=', $v['goal_id'])
                    ->update(['order' => $v['index']]);
            }
        }


        return API::response()->array(['status' => true, 'message' => '更新成功'])->statusCode(200);

    }

    /**
     * 获取目标排行
     */
    public function top(Request $request)
    {
        $goal_id = $request->goal_id;

        $users = DB::table('user_goals')
            ->join('users', 'users.user_id', '=', 'user_goals.user_id')
            ->where('user_goals.is_del', '0')
            ->where('user_goals.goal_id', $goal_id)
            ->orderBy('user_goals.total_days', 'desc')
            ->take(20)->get();

        return API::response()->array(['status' => true, 'message' => '', 'data' => $users])->statusCode(200);
    }

    public function follow(Request $request)
    {
        $goal_id = $request->goal_id;
        $days = $request->days;
        $user_id = $this->auth->user()->id;

        // 查询是否已经制定
        $user_goal = DB::table('user_goals')
            ->where('goal_id', '=', $goal_id)
            ->where('user_id', '=', $user_id)
            ->where('is_del', '=', 0)
            ->first();

        $user = User::find($user_id);

        if ($user_goal) {
            return API::response()->array(['status' => false, 'message' => '你已经制定该目标了'])->statusCode(200);
        } else {
            $user->goals()->attach($goal_id, [
                'goal_desc' => trim(Input::get('name')),
                // TODO 删除start_time字段
                'start_time' => time(),
                'start_date' => date('Y-m-d'),
                'end_date' => $days > 0 ? date('Y-m-d', strtotime('+' . ($days - 1) . ' days')) : '',
                'expect_days' => $days,
            ]);
            User::find($user_id)->increment('goal_count', 1);
            Goal::find($goal_id)->increment('follow_nums', 1);
        }

        return API::response()->array(['status' => true, 'message' => '制定成功'])->statusCode(200);

    }

    /**
     * 创建目标
     */
    public function create(Request $request)
    {
        $messages = [
            'name.required' => '请输入名称',
            'name.max' => '名称不得超过30个字符',
//            'days.numeric' => '天数需为正整数',
//            'days.required' => '请输入天数',
//            'days.min' => '天数不能为负',
//            'days.max' => '超过最大设定天数',
            'start_date.date_format' => '开始日期格式错误',
            'end_date.date_format' => '结束日期格式错误',
            'start_date.after' => '开始日期不得小于今天',
            'end_date.after' => '结束日期不得小于今天',
            'desc.max' => '描述内容不得超过250个字符',
        ];

        $validation = Validator::make(Input::all(), [
            'name' => 'required|max:30',     // 名称
//            'days' => 'required|numeric|min:0',  // 天数
            'start_date' => 'date|date_format:Y-m-d|after:today',    //开始日期
            'end_date' => 'date|date_format:Y-m-d|after:today',      //结束日期
            'desc' => 'max:255',             // 描述
            'is_public' => '',             // 描述
            'remind_time' => '',             // 描述

        ], $messages);

        if ($validation->fails()) {
            return $this->response->error(implode(',', $validation->errors()->all()), 500);
        }

        $user_id = $this->auth->user()->id;

        $start_date = $request->input('start_date', date('Y-m-d'));
        $end_date = $request->input('end_date');

        if ($end_date && $end_date < $start_date) {
            return $this->response->error("结束日期不得小于开始日期", 500);
        }

        $goal_name = trim($request->input('name'));
        $goal_desc = trim($request->input('desc'));

        // 判断目标是否存在
        $goal = Goal::where('name', '=', $goal_name)->first();

        // 若不存在新建
        if (empty($goal)) {
            $goal = new Goal();
            $goal->name = $goal_name;
            $goal->create_user = $user_id;
            $goal->follow_nums = 1;
            $goal->save();
        }

        // 判断是否已经制定该目标
        $user_goal = UserGoal::where('user_id','=',$user_id)
            ->where('goal_id', '=', $goal->id)
            ->first();

        if ($user_goal) {
            return $this->response->error("你已经制定过该目标,请勿重复添加", 500);
        }

        $expect_days = 0;

        if (!empty($end_date)) {
            $start_dt = Carbon::parse($start_date);
            $end_dt = Carbon::parse($end_date);
            $expect_days = $start_dt->diffInDays($end_dt);
        }

        $user_goal = new UserGoal();
        $user_goal->user_id = $user_id;
        $user_goal->goal_id = $goal->id;
        $user_goal->name = $goal_name;
        $user_goal->desc = $goal_desc;
        $user_goal->start_date = $start_date;
        $user_goal->end_date = $end_date;
        $user_goal->expect_days = $expect_days;
        $user_goal->status = ($start_date > date('Y-m-d')) ? 0:1;
        $user_goal->remind_time = $request->input('remind_time');
        $user_goal->save();

        // 更新用户信息
        User::find($user_id)->increment('goal_count', 1);
        // 更新目标信息
        Goal::find($goal->id)->increment('follow_nums', 1);

        $new_goal = [];
        $new_goal['id'] = $goal->id;

        return $new_goal;
    }

    /**
     * 删除目标
     */
    public function delete($goal_id,Request $request)
    {
        $user_id = $this->auth->user()->id;

        $user_goal = UserGoal::where('user_id','=',$user_id)
            ->where('goal_id', '=', $goal_id)
            ->first();

        if(!$user_goal) {
            return $this->response->error("未设定该目标", 500);
        }

        $user_goal->delete();

        return $this->response->noContent();
    }

    /**
     * 目标打卡
     * @param $goal_id
     * @param Request $request
     * @return array
     */
    public function checkin($goal_id, Request $request)
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

        $isReCheckin = false;
        $day = $request->input('day', date('Y-m-d'));

        if($day<date('Y-m-d')) {
            $isReCheckin = true;
        }

        $content = trim($request->input('content'));

            $user_id = $this->auth->user()->id;

        $user_goal = UserGoal::where('user_id','=',$user_id)
            ->where('goal_id', '=', $goal_id)
            ->first();

        if (empty($user_goal)) {
            return $this->response->error('未设定该目标', 500);
        }

        if($user_goal->start_date > date('Y-m-d')) {
            return $this->response->error('目标还未开始', 500);
        }

        if($user_goal->end_date) {
            if($user_goal->end_date < date('Y-m-d')) {
                return $this->response->error('目标已结束', 500);
            }
        }

        $series_days = $user_goal->series_days;

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
        $checkin->content = nl2br($content);
        $checkin->checkin_day = $day;
        $checkin->obj_id = $goal_id;
        $checkin->goal_id = $goal_id;
        $checkin->obj_type = 'GOAL';
        $checkin->user_id = $user_id;
        $checkin->is_public = $request->is_public?(int)$request->is_public:$user_goal->is_public;
        $checkin->save();

        // 单次打卡奖励
        $single_add_coin = 0;

        if(!$isReCheckin) {
            // TODO 判断当前目标今天是否打卡
            $single_add_coin = 2;
        }

        // 连续打卡奖励
        $series_add_coin = 0;

        if(!$isReCheckin) {
            if ($series_days>=5&&$series_days<10) {
                $series_add_coin = 5;
            } else if ($series_days>=10&&$series_days<20) {
                $series_add_coin = 10;
            }else if ($series_days>=20) {
                $series_add_coin = 20;
            }
        }

        // 如果存在该条打卡记录
        if (date('Y-m-d', strtotime($user_goal->last_checkin_at)) == date("Y-m-d", strtotime("-1 day", strtotime($day)))) {
            $series_days += 1;
        } else {
            $series_days = 1;
        }

        $total_days = $user_goal->total_days;

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
                        'checkin_id' => $checkin->id,
                        'item_id' => $item['id'],
                        'item_value' => $item['expect']
                    ]);
            }
        }

        // 更新附件
        if ($attachs = $request->input('attachs')) {
            foreach ($attachs as $attach) {
                $attach = Attach::find($attach['id']);
                $attach->attachable_id = $checkin->id;
                $attach->attachable_type = 'checkin';
                $attach->save();
            }
        }

        $user_goal->total_days = $total_days;
        $user_goal->series_days = $series_days;
        $user_goal->last_checkin_at = $day < date('Y-m-d') ? $day.' 23:59:59' : date('Y-m-d H:i:s');
        $user_goal->save();

        User::find($user_id)->increment('checkin_count', 1);
        User::find($user_id)->increment('energy_count', 1);

        if($single_add_coin > 0) {
            $energy = new Energy();
            $energy->user_id = $user_id;
            $energy->change = $single_add_coin;
            $energy->obj_type = 'checkin';
            $energy->obj_id = $checkin->id;
            $energy->create_time = time();
            $energy->save();
        }

        if($series_add_coin > 0) {
            $energy = new Energy();
            $energy->user_id = $user_id;
            $energy->change = $series_add_coin;
            $energy->obj_type = 'series_checkin';
            $energy->obj_id = $checkin->id;
            $energy->create_time = time();
            $energy->save();
        }

        $event = new Event();
        $event->goal_id = $goal_id;
        $event->user_id = $user_id;
        $event->event_value = $checkin->id;
        $event->type = 'USER_CHECKIN';
        $event->is_public = $request->is_public?(int)$request->is_public:$user_goal->is_public;
        $event->create_time = time();

        $event->save();

        //更新用户目标表
        if ($content) {
            $this->_parse_content($content, $user_id, $event->event_id);
        }
        return compact('series_add_coin','single_add_coin');
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
                $topic->save();
            }

            // 插入对应关系
            DB::table('event_topic')->insert(['topic_id' => $topic->id, 'event_id' => $event_id]);

            $topic->follow_count += 1;
            $topic->save();
        }

    }




    public function setting(Request $request)
    {
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

        $user_id = $this->auth->user()->id;

        // 判断是否已经指定了该目标
        $user_goal = Goal::find($request->goal_id)
            ->users()
            ->wherePivot('user_id', '=', $user_id)
            ->wherePivot('is_del', '=', 0)
            ->first();

        if (!$user_goal) {
            return API::response()->array(['status' => false, 'message' => "未制定该目标"]);
        }

        DB::table('user_goals')->where('id', '=', $user_goal->pivot->id)
            ->update([
                'is_public' => (int)($request->is_public),
                'is_push' => (int)($request->is_push),
                'remind_time' => $request->is_push == true ? $request->remind_time : ''
            ]);

        $this->_insert_items($user_id, $user_goal->pivot->goal_id, $request->items);

        return API::response()->array(['status' => true, 'message' => "更新成功", "data" => []]);

    }

    //  插入统计项目
    private function _insert_items($user_id, $goal_id, $items)
    {

        // 删除
        $result = DB::table('user_goal_item')
            ->where('user_id', $user_id)
            ->where('goal_id', $goal_id)
            ->where('is_del', 0)
            ->delete();

//		var_dump($goal_id);

        foreach ($items as $item) {

            DB::table('user_goal_item')->insert(
                [
                    'item_name' => $item['item_name'],
                    'item_unit' => $item['item_unit'],
                    'item_expect' => $item['item_expect'],
                    'create_time' => time(),
                    'goal_id' => $goal_id,
                    'user_id' => $user_id
                ]
            );

        }
    }

    public function items()
    {
        $validation = Validator::make(Input::all(), [
            'goal_id' => 'required',     // 目标id
        ]);

        if ($validation->fails()) {
            //TODO 删除code
            return API::response()->array(['status' => false, 'code' => 'failed', 'message' => $validation->errors()])->statusCode(200);
        }

        $goal_id = Input::get('goal_id');

        $user_id = $this->auth->user()->id;

        $items = DB::table('user_goal_item')
            ->where('goal_id', $goal_id)
            ->where('user_id', $user_id)
            ->where('is_del', '0')
            ->get();

        // $items = Goal::find($goal_id)->items()->where('user_id','=',$user_id)->get();

//		return compact('items');
        //TODO 删除items
        return API::response()->array(['status' => true, 'message' => '', 'items' => $items, 'data' => $items])->statusCode(200);


    }

    public function remind()
    {
        $validation = Validator::make(Input::all(), [
            'goal_id' => 'required',     // 目标id
            'remind_time' => 'required',     // 提醒时间

        ]);

        if ($validation->fails()) {
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $user_id = $this->auth->user()->id;
        $goal_id = Input::get('goal_id');
        $remind_time = Input::get('remind_time');

        // DB::connection()->enableQueryLog();


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
                ->updateExistingPivot($goal_id, ['remind_time' => $remind_time]);

            return API::response()->array(['status' => true, 'message' => "设置成功"]);

        } else {
            return API::response()->array(['status' => false, 'message' => "未设定该目标"]);
        }

    }

    public function week()
    {
        header("Access-Control-Allow-Origin:*");

        $validation = Validator::make(Input::all(), [
            'goal_id' => 'required',     // 目标id
        ]);

        if ($validation->fails()) {
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $user_id = $this->auth->user()->id;
        $goal_id = Input::get('goal_id');

        // 获取最近几周的打卡情况
        $current_year = date('Y');
//        $current_week = intval(date('W'));
        $current_week = 3;

        // 获取打卡的统计项
        $items = DB::table('user_goal_item')
            ->where('goal_id', $goal_id)
            ->where('user_id', $user_id)
            ->where('is_del', 0)
            ->get();

        $items = json_decode(json_encode($items), true);

        $y = [];
        $x = [];
        $summary = [];

        for ($i = $current_week; $i >= $current_week - 5; $i--) {
            if ($i <= 0) {
                $weeks = date("W", mktime(0, 0, 0, 12, 28, $current_year - 1));
                array_unshift($x, '第' . ($weeks + $i) . '周');
                $count = DB::table('checkins')
                    ->where('goal_id', Input::get('goal_id'))
                    ->where('user_id', $user_id)
                    ->whereRaw('YEAR(checkin_day)=' . ($current_year - 1))
                    ->whereRaw('WEEK(checkin_day)=' . ($weeks + $i))
                    ->count();
                array_unshift($y, $count);

                if ($items) {
                    foreach ($items as $key => $item) {
                        $sum = DB::table('checkins')
                            ->join('checkin_item', 'checkin_item.checkin_id', '=', 'checkins.id')
                            ->where('goal_id', Input::get('goal_id'))
                            ->where('user_id', $user_id)
                            ->where('item_id', $item['item_id'])
                            ->whereRaw('YEAR(checkin_day)=' . ($current_year - 1))
                            ->whereRaw('WEEK(checkin_day)=' . ($weeks + $i))
                            ->sum('item_value');
                        $items[$key]['sum'] = $sum;
                    }
                    array_unshift($summary, ['count' => $count, 'items' => $items]);
                } else {
                    array_unshift($summary, ['count' => $count, 'items' => []]);
                }


            } else {
                array_unshift($x, '第' . $i . '周');
                $count = DB::table('checkins')
                    ->where('goal_id', Input::get('goal_id'))
                    ->where('user_id', $user_id)
                    ->whereRaw('YEAR(checkin_day)=' . $current_year)
                    ->whereRaw('WEEK(checkin_day)=' . $i)
                    ->count();
                array_unshift($y, $count);

                if ($items) {
                    foreach ($items as $key => $item) {
                        $sum = DB::table('checkins')
                            ->join('checkin_item', 'checkin_item.checkin_id', '=', 'checkins.id')
                            ->where('goal_id', Input::get('goal_id'))
                            ->where('user_id', $user_id)
                            ->where('item_id', $item['item_id'])
                            ->whereRaw('YEAR(checkin_day)=' . $current_year)
                            ->whereRaw('WEEK(checkin_day)=' . $i)
                            ->sum('item_value');
                        $items[$key]['sum'] = $sum;
                    }
                    array_unshift($summary, ['count' => $count, 'items' => $items]);
                } else {
                    array_unshift($summary, ['count' => $count, 'items' => []]);
                }
            }

        }


        return API::response()->array(['status' => true, 'message' => '', 'data' => compact('x', 'y', 'summary')])->statusCode(200);

    }

    public function month()
    {
        header("Access-Control-Allow-Origin:*");

        $validation = Validator::make(Input::all(), [
            'goal_id' => 'required',     // 目标id
        ]);

        if ($validation->fails()) {
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $user_id = $this->auth->user()->id;
        $goal_id = Input::get('goal_id');

        // 获取最近几周的打卡情况
        $current_year = date('Y');
        $current_month = intval(date('m'));
//        $current_month = 3;

        $y = [];
        $x = [];
        $summary = [];
        // 获取打卡的统计项
        $items = DB::table('user_goal_item')
            ->where('goal_id', $goal_id)
            ->where('user_id', $user_id)
            ->where('is_del', 0)
            ->get();

        $items = json_decode(json_encode($items), true);

        DB::connection()->enableQueryLog();

        for ($i = $current_month; $i >= $current_month - 5; $i--) {
            if ($i <= 0) {
                array_unshift($x, (12 + $i) . '月');
                $count = DB::table('checkins')
                    ->where('goal_id', Input::get('goal_id'))
                    ->where('user_id', $user_id)
                    ->whereRaw('YEAR(checkin_day)=' . ($current_year - 1))
                    ->whereRaw('MONTH(checkin_day)=' . (12 + $i))
                    ->count();
                array_unshift($y, $count);
                if ($items) {
                    foreach ($items as $key => $item) {
                        $sum = DB::table('checkins')
                            ->join('checkin_item', 'checkin_item.checkin_id', '=', 'checkins.id')
                            ->where('goal_id', Input::get('goal_id'))
                            ->where('user_id', $user_id)
                            ->where('item_id', $item['item_id'])
                            ->whereRaw('YEAR(checkin_day)=' . ($current_year - 1))
                            ->whereRaw('MONTH(checkin_day)=' . (12 + $i))
                            ->sum('item_value');
                        $items[$key]['sum'] = $sum;
                    }
                    array_unshift($summary, ['count' => $count, 'items' => $items]);
                } else {
                    array_unshift($summary, ['count' => $count, 'items' => []]);
                }
            } else {
                array_unshift($x, $i . '月');
                $count = DB::table('checkins')
                    ->where('goal_id', Input::get('goal_id'))
                    ->where('user_id', $user_id)
                    ->whereRaw('YEAR(checkin_day)=' . $current_year)
                    ->whereRaw('MONTH(checkin_day)=' . $i)
                    ->count();

                array_unshift($y, $count);

                if ($items) {
                    foreach ($items as $key => $item) {
                        $sum = DB::table('checkins')
                            ->join('checkin_item', 'checkin_item.checkin_id', '=', 'checkins.id')
                            ->where('goal_id', Input::get('goal_id'))
                            ->where('user_id', $user_id)
                            ->where('item_id', $item['item_id'])
                            ->whereRaw('YEAR(checkin_day)=' . ($current_year))
                            ->whereRaw('MONTH(checkin_day)=' . $i)
                            ->sum('item_value');
                        $items[$key]['sum'] = $sum;
                    }

                    array_unshift($summary, ['count' => $count, 'items' => $items]);
                } else {
                    array_unshift($summary, ['count' => $count, 'items' => []]);
                }
            }

        }

        return API::response()->array(['status' => true, 'message' => '', 'data' => compact('x', 'y', 'summary')])->statusCode(200);
    }

    public function year()
    {
        header("Access-Control-Allow-Origin:*");

        $validation = Validator::make(Input::all(), [
            'goal_id' => 'required',     // 目标id
        ]);

        if ($validation->fails()) {
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $user_id = $this->auth->user()->id;
        $goal_id = Input::get('goal_id');

        // 获取最近几周的打卡情况
        $current_year = date('Y');

        $y = [];
        $x = [];
        $summary = [];
        // 获取打卡的统计项
        $items = DB::table('user_goal_item')
            ->where('goal_id', $goal_id)
            ->where('user_id', $user_id)
            ->where('is_del', 0)
            ->get();

        $items = json_decode(json_encode($items), true);


        for ($i = $current_year; $i >= $current_year - 5; $i--) {
            array_unshift($x, $i);
            $count = DB::table('checkins')
                ->where('goal_id', Input::get('goal_id'))
                ->where('user_id', $user_id)
                ->whereRaw('YEAR(checkin_day)=' . $i)
                ->count();
            array_unshift($y, $count);
            if ($items) {
                foreach ($items as $key => $item) {
                    $sum = DB::table('checkins')
                        ->join('checkin_item', 'checkin_item.checkin_id', '=', 'checkins.id')
                        ->where('goal_id', Input::get('goal_id'))
                        ->where('user_id', $user_id)
                        ->where('item_id', $item['item_id'])
                        ->whereRaw('YEAR(checkin_day)=' . $i)
                        ->sum('item_value');
                    $items[$key]['sum'] = $sum;
                }
                array_unshift($summary, ['count' => $count, 'items' => $items]);
            } else {
                array_unshift($summary, ['count' => $count, 'items' => []]);
            }
        }
        return API::response()->array(['status' => true, 'message' => '', 'data' => compact('x', 'y', 'summary')])->statusCode(200);
    }

}