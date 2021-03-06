<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Api\V3\Transformers\UserTransformer;
use App\Http\Controllers\Api\V3\Transformers\EventTransformer;
use App\Http\Controllers\Api\V3\Transformers\UserGoalTransformer;
use App\Models\CheckinItem;
use Auth;
use Validator;

use App\User;
use App\Goal;
use App\Checkin;
use App\Models\Event;
use App\Models\UserGoal;
use App\Models\UserGoalItem;
use App\Models\Energy;
use App\Models\Attach;
use Carbon\Carbon;

use API;
use DB;
use Log;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use League\Fractal\Serializer\ArraySerializer;

class GoalController extends BaseController
{

    // 获取目标详情
    public function getGoalDetail($goal_id,Request $request)
    {
        $user_id = $this->auth->user()->id;

        $goal = Goal::find($goal_id);

        if(!$goal) {
            $this->response->error("未查找到目标",500);
        }

        // 判断是否已经指定了该目标
        $user_goal = UserGoal::where('goal_id','=',$goal_id)
            ->where('user_id','=',$user_id)
            ->first();

        $new_goal = [];

        $new_goal['id'] = $goal->id;
        $new_goal['name'] = $goal->name;
        $new_goal['is_follow'] = $user_goal?true:false;
        $new_goal['follow_nums'] = $goal->follow_nums;
        $new_goal['checkin_count'] =  Checkin::where('goal_id','=',$goal_id)->count();

        return $new_goal;
    }

    // 获取目标动态
    public function getGoalEvents($goal_id,Request $request){
        $user_id = $this->auth->user()->id;

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);

        $events = Event::where('goal_id','=',$goal_id)
            ->orderBy('created_at','desc')
            ->skip(($page-1)*$per_page)
            ->limit($per_page)
            ->get();

        return $this->response->collection($events, new EventTransformer(),[],function($resource, $fractal){
            $fractal->setSerializer(new ArraySerializer());
        });
    }

    // 获取目标成员
    public function getGoalMembers($goal_id,Request $request){
        $user_id = $this->auth->user()->id;

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);
        $is_audit = $request->input('is_audit', 1);

        $user_goals = UserGoal::Where('goal_id','=',$goal_id)
            ->where('is_audit','=',$is_audit)
            ->orderBy('created_at','desc')
            ->skip(($page-1)*$per_page)
            ->limit($per_page)
            ->get();

        $new_users = [];

        foreach ($user_goals as $k=>$user_goal) {
            $new_users[$k]['id'] = $k+1;
            $new_users[$k]['total_days'] = $user_goal->total_days;
            $new_users[$k]['user_id'] = $user_goal->user_id;
            $new_users[$k]['nickname'] = $user_goal->user->nickname;
            $new_users[$k]['avatar_url'] = $user_goal->user->avatar_url;
            $new_users[$k]['last_checkin_at'] = $user_goal->last_checkin_at;
        }

        return $new_users;
    }


    // 获取目标排行
    public function getGoalTop($goal_id,Request $request){
        $user_id = $this->auth->user()->id;

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);

        $user_goals = UserGoal::where('goal_id','=',$goal_id)
            ->orderBy('total_days','desc')
            ->limit(10)
            ->get();

        $new_users = [];

        foreach ($user_goals as $k=>$user_goal) {
            $new_users[$k]['id'] = $k+1;
            $new_users[$k]['total_days'] = $user_goal->total_days;
            $new_users[$k]['user_id'] = $user_goal->user_id;
            $new_users[$k]['nickname'] = $user_goal->user->nickname;
            $new_users[$k]['avatar_url'] = $user_goal->user->avatar_url;
        }

        return $new_users;

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

    // 制定一个目标
    public function doFollow($goal_id,Request $request)
    {
        $user_id = $this->auth->user()->id;

        // 查询是否已经制定
        $is_follow = UserGoal::where('goal_id', '=', $goal_id)
            ->where('user_id', '=', $user_id)
            ->first();

        if ($is_follow) {
            return $this->response->error('你已经制定该目标了',500);
        }

        $goal = Goal::find($goal_id);

        $user_goal = new UserGoal();
        $user_goal->user_id = $user_id;
        $user_goal->goal_id = $goal_id;
        $user_goal->name = $goal->name;
        $user_goal->save();

        User::find($user_id)->increment('goal_count', 1);
        Goal::find($goal_id)->increment('follow_nums', 1);

        return $this->response->noContent();
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
//            'days.min' => '天数不能为负',
//            'days.max' => '超过最大设定天数',
            'start_date.date_format' => '开始日期格式错误',
            'end_date.date_format' => '结束日期格式错误',
            'start_date.after' => '开始日期不得小于今天',
            'end_date.after' => '结束日期不得小于今天',
//            'desc.max' => '描述内容不得超过250个字符',
        ];

        $validation = Validator::make(Input::all(), [
            'name' => 'required|max:30',     // 名称
//            'days' => 'numeric|min:0|max:9999',  // 天数
            'start_date' => 'date|date_format:Y-m-d|after:today',    //开始日期
            'end_date' => 'date|date_format:Y-m-d|after:today',      //结束日期
            'desc' => 'max:255',             // 描述
            'is_public' => '',             // 是否公开

        ], $messages);

        if ($validation->fails()) {
            return $this->response->error(implode(',', $validation->errors()->all()), 500);
        }

        $user = $this->auth->user();
        $user_id = $user->id;

        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $date_type =  $request->input('date_type',1);
        $type =  $request->input('type',1);
        $time_type =  $request->input('type',1);
        $start_time = $request->input('start_time');
        $end_time = $request->input('end_time');
        $items = $request->input('items');

        $days = 0;

        // 判断用户创建目标个数
        $count = UserGoal::Where('user_id','=',$user_id)
            ->where('is_archive','=','0')
            ->count();

        $max_count = 5;

        if($user['vip_type']>0) {
            $max_count = 10;
        }

        if($count > $max_count) {
            return $this->response->error("已达到创建目标个数上限", 500);
        }

        // 如果是短期目标，检查日期范围
        if($date_type == 2) {

            if(!$start_date || !$end_date) {
                return $this->response->error("开始或结束日期不得为空", 500);
            }

            if ($end_date < $start_date) {
                return $this->response->error("结束日期不得小于开始日期", 500);
            }

            $start_dt = Carbon::parse($start_date);
            $end_dt = Carbon::parse($end_date);

            $days = $start_dt->diffInDays($end_dt);
        }

        // 如果是指定时间，检查时间范围
        if($time_type == 2) {

            if(!$start_time || !$start_time) {
                return $this->response->error("开始或结束时间不得为空", 500);
            }

            if ($end_time < $start_time) {
                return $this->response->error("结束时间不得小于开始时间", 500);
            }
        }

        $goal_name = trim($request->input('name'));
        $goal_desc = trim($request->input('desc'));
        $icon  = $request->input('icon','shuidi');
        $color = $request->input('color','primary');

        // 判断目标是否存在
        $goal = Goal::where('name', '=', $goal_name)->first();

        // 若不存在新建
        if (empty($goal)) {
            $goal = new Goal();
            $goal->name = $goal_name;
            $goal->create_user = $user_id;
            $goal->type = $type;
            $goal->icon = $icon;
            $goal->color = $color;
            $goal->save();
        }

        // 判断是否已经制定该目标
        $user_goal = UserGoal::where('user_id','=',$user_id)
            ->where('goal_id', '=', $goal->id)
            ->first();

        if ($user_goal) {
            return $this->response->error("你已经制定过该目标,请勿重复添加", 500);
        }

        $user_goal = new UserGoal();
        $user_goal->user_id = $user_id;
        $user_goal->goal_id = $goal->id;
        $user_goal->name = $goal_name;
        $user_goal->desc = $goal_desc;
        $user_goal->date_type = $date_type;
        $user_goal->start_date = $start_date;
        $user_goal->end_date = $end_date;
        $user_goal->time_type = $date_type;
        $user_goal->start_time = $start_time;
        $user_goal->end_time = $end_time;
        $user_goal->expect_days = $days;
        $user_goal->icon = $icon;
        $user_goal->color = $color;
        $user_goal->remind_sound = $request->input('remind_sound');
        $user_goal->remind_vibration = $request->input('remind_vibration');
        $user_goal->remind_time = $request->input('remind_time');
        $user_goal->status = ($date_type==2 && ($start_date > date('Y-m-d'))) ? 0:1;
        $user_goal->is_public = $request->input('is_public')?1:0;
        $user_goal->weekday = implode(';',$request->input('weeks'));
        $user_goal->save();

        // 插入用户项目
        foreach($items as $item) {
            $user_goal_item = new UserGoalItem();
            $user_goal_item->goal_id = $goal->id;
            $user_goal_item->user_id = $user_id;
            $user_goal_item->item_name =$item['name'];
            $user_goal_item->item_unit =$item['unit'];
            $user_goal_item->item_expect =$item['expect'];
            $user_goal_item->save();
        }

        // 更新用户信息
        User::find($user_id)->increment('goal_count', 1);
        // 更新目标信息
        Goal::find($goal->id)->increment('follow_nums', 1);

        $new_goal = [];
        $new_goal['id'] = $goal->id;

        return $new_goal;
    }

    public function update(Request $request){

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

        if($user_goal->date_type == 2) {
            if($user_goal->end_date < date('Y-m-d')) {
                return $this->response->error('目标已结束', 500);
            }
        }

        // TODO
        // 判断是否在打卡周期内
        if($user_goal->weekday) {
            $weekdays = explode(';',$user_goal->weekday);
            if(!in_array(date('w'),$weekdays)) {
                return $this->response->error('不在打卡周期内', 500);
            }
        }

        $series_days = $user_goal->series_days;

        // 获取当天的打卡次数
        $today_checkin_count = Checkin::where('user_id', '=', $user_id)
            ->where('goal_id', '=', $goal_id)
            ->where('checkin_day', '=', $day)
            ->count();

//        if ($today_checkin_count >= $user_goal->max_daily_count) {
//            return $this->response->error('超过当日打卡最大次数', 500);
//        }

        // 保存打卡记录
        $checkin = new Checkin();
        $checkin->content = nl2br($content);
        $checkin->checkin_day = $day;
        $checkin->obj_id = $goal_id;
        $checkin->goal_id = $goal_id;
        $checkin->obj_type = 'GOAL';
        $checkin->checkinable_id = $goal_id;
        $checkin->checkinable_type = 'goal';
        $checkin->user_id = $user_id;
        $checkin->is_public = $request->is_public?(int)$request->is_public:$user_goal->is_public;
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

        // 单次打卡奖励
        $single_add_coin = 0;
        // 连续打卡奖励
        $series_add_coin = 0;

        if($today_checkin_count == 0) {
            $single_add_coin = 2;

            // 计算连续打卡奖励
            if(!$isReCheckin) {
                if ($series_days>=5&&$series_days<10) {
                    $series_add_coin = 5;
                } else if ($series_days>=10&&$series_days<20) {
                    $series_add_coin = 10;
                }else if ($series_days>=20) {
                    $series_add_coin = 20;
                }
            }
        }

        // 计算连续打卡天数
        if (date('Y-m-d', strtotime($user_goal->last_checkin_at)) == date("Y-m-d", strtotime("-1 day", strtotime($day)))) {
            if($today_checkin_count == 0) {
                $series_days += 1;
            }
        } else {
            $series_days = 1;
        }

        $total_days = $user_goal->total_days;

        if($today_checkin_count == 0) {
            $total_days++;
        }

        $checkin->total_days = $total_days;
        $checkin->series_days = $series_days;

        $checkin->save();

        $user_goal->total_days = $total_days;
        $user_goal->series_days = $series_days;

        $user_goal->total_count += 1;

        $user_goal->last_checkin_at = $day < date('Y-m-d') ? $day.' 23:59:59' : date('Y-m-d H:i:s');
        $user_goal->save();

        User::find($user_id)->increment('checkin_count', 1);

        if($single_add_coin > 0) {
            $energy = new Energy();
            $energy->user_id = $user_id;
            $energy->change = $single_add_coin;
            $energy->obj_type = 'checkin';
            $energy->obj_id = $checkin->id;
            $energy->create_time = time();
            $energy->save();
            User::find($user_id)->increment('energy_count', $single_add_coin);
        }

        if($series_add_coin > 0) {
            $energy = new Energy();
            $energy->user_id = $user_id;
            $energy->change = $series_add_coin;
            $energy->obj_type = 'series_checkin';
            $energy->obj_id = $checkin->id;
            $energy->create_time = time();
            $energy->save();
            User::find($user_id)->increment('energy_count', $series_add_coin);
        }

        // 发表动态
        $event = new Event();
        $event->goal_id = $goal_id;
        $event->user_id = $user_id;
        $event->event_value = $checkin->id;
        $event->eventable_id = $checkin->id;
        $event->eventable_type = 'checkin';
        $event->type = 'USER_CHECKIN';
        $event->is_public = $request->is_public?(int)$request->is_public:$user_goal->is_public;
        $event->create_time = time();
        $event->save();

        // 处理动态内容
        if ($content) {
            $this->_parse_content($content, $user_id, $event->event_id);
        }

        return compact('series_add_coin','single_add_coin','total_days','event');
    }


    public function getCheckin($checkin_id,Request $request)
    {
        $checkin = Checkin::find($checkin_id);

        $user = $this->auth->user();

        $new_checkin = [];

        $new_checkin['id'] = $checkin->id;
        $new_checkin['day'] = $checkin->checkin_day;
        $new_checkin['content'] = $checkin->content;

        // 获取items

        $checkin_items = DB::table('checkin_item')
            ->where('checkin_id', $checkin_id)
            ->get();

        $goal_items = DB::table('user_goal_item')
            ->where('user_id', $user->id)
            ->where('goal_id', $checkin->goal_id)
            ->get();

        $new_items = [];

        foreach($goal_items as $k=>$item) {

            $new_items[$k]['id'] = $item->item_id;
            $new_items[$k]['name'] = $item->item_name;
            $new_items[$k]['value'] = 0;
            $new_items[$k]['unit'] = $item->item_unit;

            foreach($checkin_items as $k2=>$v) {
                if($item->item_id == $v->item_id) {
                    $new_items[$k]['value'] = $v->item_value;
                }
            }
        }

        $new_checkin['items'] = $new_items;

        $new_attachs = [];

        foreach($checkin->attaches as $k=>$attach) {
            $new_attachs[$k]['id'] = $attach->id;
            $new_attachs[$k]['name'] = $attach->name;
//				$new_attachs[$k]['url'] = "http://drip.growu.me/uploads/images/".$attach->path.'/'.$attach->name;
            $new_attachs[$k]['url'] = "http://file.growu.me/".$attach->name."?imageslim";
        }

        $new_checkin['attachs'] = $new_attachs;

        return $new_checkin;
    }

    public function deleteCheckin($checkin_id,Request $request) {
        $checkin = Checkin::find($checkin_id);

        // 删除动态
        Event::Where('event_value','=',$checkin_id)
            ->delete();

        // 删除打卡项目
        CheckinItem::Where('checkin_id','=',$checkin_id)
            ->delete();

        // 删除打卡
        $checkin->delete();

        return $this->response->noContent();
    }

    public function updateCheckin($checkin_id,Request $request)
    {
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

        $checkin = Checkin::find($checkin_id);

        if (empty($checkin)) {
            return $this->response->error('打卡记录不存在', 500);
        }

        $user_goal = UserGoal::where('user_id','=',$user_id)
            ->where('goal_id', '=', $checkin->goal_id)
            ->first();

        if (empty($user_goal)) {
            return $this->response->error('未设定该目标', 500);
        }

        if($user_goal->start_date > date('Y-m-d')) {
            return $this->response->error('目标还未开始', 500);
        }

        if($user_goal->date_type == 2) {
            if($user_goal->end_date < date('Y-m-d')) {
                return $this->response->error('目标已结束', 500);
            }
        }

        // TODO
        // 判断是否在打卡周期内
        if($user_goal->weekday) {
            $weekdays = explode(';',$user_goal->weekday);
            if(!in_array(date('w'),$weekdays)) {
                return $this->response->error('不在打卡周期内', 500);
            }
        }

        $series_days = $user_goal->series_days;

        // 获取当天的打卡次数
        $today_checkin_count = Checkin::where('user_id', '=', $user_id)
            ->where('goal_id', '=', $user_goal->goal_id)
            ->where('checkin_day', '=', $day)
            ->count();

//        if ($today_checkin_count >= $user_goal->max_daily_count) {
//            return $this->response->error('超过当日打卡最大次数', 500);
//        }

        // 保存打卡记录
        $checkin->content = nl2br($content);
        $checkin->checkin_day = $day;
        $checkin->is_public = $request->is_public?(int)$request->is_public:$user_goal->is_public;
        $checkin->save();

        // 插入items
        $items = Input::get('items');
        if (!empty($items)) {
            CheckinItem::Where('checkin_id','=',$checkin_id)
                ->delete();

            foreach ($items as $item) {

                DB::table('checkin_item')
                    ->insert([
                        'checkin_id' => $checkin->id,
                        'item_id' => $item['id'],
                        'item_value' => $item['value']
                    ]);
            }
        }

        // 更新附件
        if ($attachs = $request->input('attachs')) {
            Attach::Where('attachable_type','=','checkin')
                ->Where('attachable_id','=',$checkin_id)
                ->delete();

            foreach ($attachs as $attach) {
                $attach = Attach::find($attach['id']);
                $attach->attachabAOle_id = $checkin->id;
                $attach->attachable_type = 'checkin';
                $attach->save();
            }
        }
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

    public function doAudit($goal_id,Request $request)
    {
        $user_id = $request->input('user_id');
        $is_audit = $request->input('is_audit');
        $reason = $request->input('reason');

        $user_goal = UserGoal::Where('goal_id','=',$goal_id)
            ->where('user_id','=',$user_id)
            ->first();

        if(!$user_goal) {
            $this->response->error("未找到审核请求",500);
        }


        $user_goal->is_audit = $is_audit;
        $user_goal->audit_reson = $reason;

        $user_goal->save();
    }

    public function search(Request $request)
    {
        $name = $request->input('name');

        $goals = Goal::OrderBy('follow_nums','desc')
            ->limit(20)
            ->get();

        return response()->json($goals);
    }

}