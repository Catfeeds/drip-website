<?php
/**
 * 订单控制器
 */
namespace App\Http\Controllers\Api\V1;

use Auth;
use Validator;

use App\User;
use App\Goal;
use App\Checkin;
use App\Event;


use API;
use DB;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


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

        $user_id = $this->auth->user()->user_id;

        $goal = Goal::find($request->goal_id);

        $is_follow = false;

        // 判断是否已经指定了该目标
        $user_goal = DB::table('user_goal')
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
            $goals = Goal::where('goal_name', 'like', '%' . $request->q . '%')
                ->orderBy('follow_nums', 'desc')
                ->take(20)->get();
        } else {
            $goals = Goal::orderBy('follow_nums', 'desc')
                ->take(20)->get();
        }

        return API::response()->array(['status' => true, 'message' => '', 'data' => $goals])->statusCode(200);
    }

    /**
     * 目标排序
     */
    public function reorder(Request $request)
    {
        $order = $request->order;
        $user_id = $this->auth->user()->user_id;

        if (is_array($order) && !empty($order)) {
            foreach ($order as $k => $v) {
                DB::table('user_goal')
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

        $users = DB::table('user_goal')
            ->join('users', 'users.user_id', '=', 'user_goal.user_id')
            ->where('user_goal.is_del', '0')
            ->where('user_goal.goal_id', $goal_id)
            ->orderBy('user_goal.total_days', 'desc')
            ->take(20)->get();

        return API::response()->array(['status' => true, 'message' => '', 'data' => $users])->statusCode(200);
    }

    public function follow(Request $request)
    {
        $goal_id = $request->goal_id;
        $days = $request->days;
        $user_id = $this->auth->user()->user_id;

        // 查询是否已经制定
        $user_goal = DB::table('user_goal')
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
    public function create()
    {
        $messages = [
            'name.required' => '请输入目标名称',
            'name.max' => '目标名称长度过长',
            'days.numeric' => '天数需为正整数',
            'days.required' => '请输入天数',
            'days.min' => '天数不能为负',
            'days.max' => '超过最大设定天数',
            'desc.max' => '简介内容过长',
        ];

        $validation = Validator::make(Input::all(), [
            'name' => 'required|max:100',     // 名称
            'days' => 'required|numeric|min:0|max:9999',  // 天数           // 天数
            'desc' => 'max:255',             // 描述
        ], $messages);

        if ($validation->fails()) {
            // TODO 删除code字段
            return API::response()->array(['status' => false, 'code' => 'failed', 'message' => $validation->errors()->all('</br>:message')])->statusCode(200);
        }

        $user = $this->auth->user();
        $user_id = $user->user_id;
        $days = Input::get('days') ? Input::get('days') : 0;


        $count = User::find($user_id)
            ->goals()
            ->wherePivot('is_del','=',0)
            ->wherePivot('status','=',0)
            ->count();

        // 判断用户等级和数量
        if($count>=($user->level+1)) {
            return API::response()->array(['status' => false, 'code' => 'failed', 'message' =>'达到目标上限']);
        }

        $need_energy = $days==0?1000:$days;

        // 判断用户是否充足
        if($user->energy_count<$need_energy) {
            return API::response()->array(['status' => false, 'code' => 'failed', 'message' =>'能量值不足']);
        }



        $goal_name = Input::get('name');

        // 判断目标是否存在
        $goal = Goal::where('goal_name', '=', $goal_name)->first();

        // 若不存在新建
        if (empty($goal)) {
            $goal = new Goal();
            $goal->goal_name = $goal_name;
            $goal->create_user = $user_id;
            $goal->follow_nums = 1;
            $goal->create_time = time();
            $goal->save();
        }

        // 判断是否已经指定了该目标
        $user_goal = Goal::find($goal->goal_id)
            ->users()
            ->wherePivot('user_id', '=', $user_id)
            ->wherePivot('is_del', '=', 0)
            ->first();

        if ($user_goal) {
            // TODO 删除code字段
            return API::response()->array(['status' => false, 'code' => '5100', 'message' => "你已经制定过该目标了"]);
        } else {
            $user->goals()->attach($goal->goal_id, [
                'goal_desc' => trim(Input::get('name')),
                // TODO 删除start_time字段
                'start_time' => time(),
                'start_date' => date('Y-m-d'),
                'end_date' => $days > 0 ? date('Y-m-d', strtotime('+' . ($days - 1) . ' days')) : '',
                'expect_days' => $days,
            ]);

            User::find($user_id)->increment('goal_count', 1);
            Goal::find($goal->goal_id)->increment('follow_nums', 1);
        }

        $goal = User::find($user_id)->goals()->wherePivot('goal_id', '=', $goal->goal_id)->first();

        // TODO 删除code字段
        return API::response()->array(['status' => true, 'code' => 0, 'message' => "添加成功", "data" => $goal]);

    }

    /**
     * 更新目标
     */
    public function update(Request $request)
    {
        $messages = [
            'days.numeric' => '天数需为正整数',
            'days.required' => '请输入天数',
            'days.min' => '天数不能为负',
            'days.max' => '超过最大设定天数',
            'desc.max' => '简介内容过长',
        ];


        $validation = Validator::make(Input::all(), [
            'goal_id' => 'required',     // 目标id
            'goal_name' => '',     // 目标名称
            'expect_days' => 'required|numeric|min:0|max:9999',  // 天数           // 天数
            'goal_desc' => 'max:255',             // 描述
//			'items'		=>  [],             // 统计项目
//			'is_public' =>  '',             // 是否公开
//			'is_push'   =>  '',				// 是否推送
//			'push_time' =>  '',             // 推送时间
        ], $messages);

        if ($validation->fails()) {
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $user_id = $this->auth->user()->user_id;
        $user = User::find($user_id);
        $goal_name = Input::get('goal_name');

//		// 判断目标是否存在
//		$goal = Goal::where('goal_name','=',$goal_name)->first();
//
//		// 若不存在新建
//		if(empty($goal)) {
//			$goal = new Goal();
//			$goal->goal_name = $goal_name;
//			$goal->save();
//		}

        // 判断是否已经指定了该目标
        $user_goal = Goal::find($request->goal_id)
            ->users()
            ->wherePivot('user_id', '=', $user_id)
            ->wherePivot('is_del', '=', 0)
            ->first();

//		$pivot = Input::get('pivot');

//		if(!$user_goal) {
//			return API::response()->array(['status' => true, 'message' =>"更新成功","data"=>['goal_id'=>$goal->goal_id]]);

//		} else {


//		return API::response()->array(['status' => true, 'message' =>"更新成功","data"=>['goal_id'=>$goal->goal_id]]);
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
                'is_public' => $request->is_public ? (int)($request->is_public) : 1,
                'is_push' => $request->is_push ? (int)($request->is_push) : 0,
                'remind_time' => $request->is_push == true ? $request->remind_time : ''
            ]);

        $this->_insert_items($user_id, $user_goal->pivot->goal_id, $request->items);

        return API::response()->array(['status' => true, 'message' => "更新成功", "data" => []]);

    }


    public function checkins()
    {
        $validation = Validator::make(Input::all(), [
            'goal_id' => 'required',     // 目标id
            'year' => 'required',     // 年数
            'month' => 'required',     // 月数
        ]);

        if ($validation->fails()) {
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $user_id = $this->auth->user()->user_id;

        $goal_id = Input::get('goal_id');
        $year = Input::get('year');
        $month = Input::get('month');

        $checkins = Checkin::where('goal_id', '=', $goal_id)
            ->where('user_id', '=', $user_id)
            ->whereRaw('YEAR(checkin_day)=' . $year)
            ->whereRaw('MONTH(checkin_day)=' . $month)
            ->get();

//        print_r($checkins);

        foreach ($checkins as $k => $checkin) {
            $items = DB::table("checkin_item")
                ->join('user_goal_item', 'checkin_item.item_id', '=', 'user_goal_item.item_id')
                ->where('checkin_id', $checkin->checkin_id)
                ->get();

            $checkins[$k]['items'] = $items;

            // 获取附件
            $checkins[$k]['attaches'] = DB::table('attachs')
                ->where('attachable_id', $checkin->checkin_id)
                ->where('attachable_type', 'checkin')
                ->get();
        }

        return API::response()->array(['status' => true, 'message' => '', 'data' => $checkins]);


    }

    public function events()
    {
        $validation = Validator::make(Input::all(), [
            'goal_id' => 'required',     // 目标id
        ]);

        if ($validation->fails()) {
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $goal_id = Input::get('goal_id');

        $events = Event::where('goal_id', $goal_id)->orderBy('create_time', 'DESC')->skip(10)
            ->take(20)->get();

        foreach ($events as $key => $event) {
            if ($event['type'] == 'USER_CHECKIN') {
                $events[$key]['checkin'] = $event->checkin;
                $events[$key]['checkin']['items'] = DB::table('checkin_item')
                    ->join('user_goal_item', 'user_goal_item.item_id', '=', 'checkin_item.item_id')
                    ->where('checkin_id', $event->event_value)
                    ->get();
                $events[$key]['checkin']['attaches'] = DB::table('attachs')
                    ->where('attachable_id', $event->event_value)
                    ->where('attachable_type', 'checkin')
                    ->get();
            }
            $events[$key]['user'] = $event->user;
            $events[$key]['goal'] = $event->goal;
        }


        return API::response()->array(['status' => true, 'message' => '', 'data' => $events]);


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

        $user_id = $this->auth->user()->user_id;

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

    public function checkin()
    {
        $validation = Validator::make(Input::all(), [
            'goal_id' => 'required',     // 目标id
            'day' => 'required'    //日期
        ]);

        if ($validation->fails()) {
            return API::response()->array(['code' => 'failed', 'message' => $validation->errors()])->statusCode(200);
        }

        $goal_id = Input::get('goal_id');
        $day = Input::get('day');

        $user_id = $this->auth->user()->user_id;

        $checkin = Checkin::where('goal_id', '=', $goal_id)->where('user_id', '=', $user_id)->where('checkin_day', '=', $day)->first();

        if ($checkin) {
            $items = DB::table("checkin_item")
                ->join('user_goal_item', 'checkin_item.item_id', '=', 'user_goal_item.item_id')
                ->where('checkin_id', $checkin->checkin_id)
                ->get();

            $checkin->items = $items;
        }


        return API::response()->array(['code' => 0, 'data' => $checkin]);

    }

    public function delete()
    {
        $validation = Validator::make(Input::all(), [
            'goal_id' => 'required',     // 目标id
        ]);

        $user_id = $this->auth->user()->user_id;
        $goal_id = Input::get('goal_id');

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
                ->updateExistingPivot($goal_id, ['is_del' => 1]);

            return API::response()->array(['status' => true, 'message' => "删除成功"]);

        } else {
            return API::response()->array(['status' => false, 'message' => "未设定该目标"]);
        }

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

        $user_id = $this->auth->user()->user_id;
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

        $user_id = $this->auth->user()->user_id;
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
                $count = DB::table('checkin')
                    ->where('goal_id', Input::get('goal_id'))
                    ->where('user_id', $user_id)
                    ->whereRaw('YEAR(checkin_day)=' . ($current_year - 1))
                    ->whereRaw('WEEK(checkin_day)=' . ($weeks + $i))
                    ->count();
                array_unshift($y, $count);

                if ($items) {
                    foreach ($items as $key => $item) {
                        $sum = DB::table('checkin')
                            ->join('checkin_item', 'checkin_item.checkin_id', '=', 'checkin.checkin_id')
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
                $count = DB::table('checkin')
                    ->where('goal_id', Input::get('goal_id'))
                    ->where('user_id', $user_id)
                    ->whereRaw('YEAR(checkin_day)=' . $current_year)
                    ->whereRaw('WEEK(checkin_day)=' . $i)
                    ->count();
                array_unshift($y, $count);

                if ($items) {
                    foreach ($items as $key => $item) {
                        $sum = DB::table('checkin')
                            ->join('checkin_item', 'checkin_item.checkin_id', '=', 'checkin.checkin_id')
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

        $user_id = $this->auth->user()->user_id;
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
                $count = DB::table('checkin')
                    ->where('goal_id', Input::get('goal_id'))
                    ->where('user_id', $user_id)
                    ->whereRaw('YEAR(checkin_day)=' . ($current_year - 1))
                    ->whereRaw('MONTH(checkin_day)=' . (12 + $i))
                    ->count();
                array_unshift($y, $count);
                if ($items) {
                    foreach ($items as $key => $item) {
                        $sum = DB::table('checkin')
                            ->join('checkin_item', 'checkin_item.checkin_id', '=', 'checkin.checkin_id')
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
                $count = DB::table('checkin')
                    ->where('goal_id', Input::get('goal_id'))
                    ->where('user_id', $user_id)
                    ->whereRaw('YEAR(checkin_day)=' . $current_year)
                    ->whereRaw('MONTH(checkin_day)=' . $i)
                    ->count();

                array_unshift($y, $count);

                if ($items) {
                    foreach ($items as $key => $item) {
                        $sum = DB::table('checkin')
                            ->join('checkin_item', 'checkin_item.checkin_id', '=', 'checkin.checkin_id')
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

        $user_id = $this->auth->user()->user_id;
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
            $count = DB::table('checkin')
                ->where('goal_id', Input::get('goal_id'))
                ->where('user_id', $user_id)
                ->whereRaw('YEAR(checkin_day)=' . $i)
                ->count();
            array_unshift($y, $count);
            if ($items) {
                foreach ($items as $key => $item) {
                    $sum = DB::table('checkin')
                        ->join('checkin_item', 'checkin_item.checkin_id', '=', 'checkin.checkin_id')
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