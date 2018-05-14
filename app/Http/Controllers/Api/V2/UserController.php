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
use GuzzleHttp\Client;

use App\User;
use App\Checkin;
use App\Models\Message;
use App\Goal;
use App\Models\Attach;
use App\Models\Report;
use App\Models\Topic;
use App\Models\UserGoal;
use App\Models\Event;
use App\Models\UserFollow;
use App\Like;
use App\Models\Comment;
use App\Models\Energy;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Api\V2\Transformers\UserTransformer;
use App\Http\Controllers\Api\V2\Transformers\UserGoalTransformer;
use League\Fractal\Serializer\ArraySerializer;


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

            $user_follow = DB::table('user_follows')
                ->where('user_id', $this->auth->user()->id)
                ->where('follow_user_id', $user_id)
                ->first();

            if ($user_follow) {
                $is_follow = true;
            }

            $new_user['is_follow'] = $is_follow;

            $new_user['id'] = $user->id;
            $new_user['nickname'] = $user->nickname;
            $new_user['signature'] = $user->signature;
            $new_user['fans_count'] = $user->fans_count;
            $new_user['follow_count'] = $user->follow_count;
            $new_user['avatar_url'] = $user->avatar_url;
            $new_user['sex'] = $user->sex;
            $new_user['verified_type'] = $user->verified_type;
            $new_user['verified_reason'] = $user->verified_reason;

            $new_user['is_vip'] = $user->is_vip == 1 ? true : false;

        }


        // TODO 判断用户是否存在
        return $new_user;

    }

    /**
     * 更新用户信息
     * @param $user_id
     * @param Request $request
     * @return array
     */
    public function updateUser($user_id, Request $request)
    {
        Log::info('更新用户信息');
        Log::info($request);

        $input = $request->all();

        $user = $this->auth->user();


        //TODO 兼容老版本
        if(isset($input['user_avatar'])) {
            $input['avatar_url'] = $input['user_avatar'];
            unset($input['user_avatar']);
        }

        if ($input) {
            $user->update($input);
        }

        $new_user = [];
        $new_user['id'] = $user->id;
        $new_user['is_vip'] = $user->is_vip == 1 ? true : false;
        $new_user['created_at'] = $user->created_at;
        $new_user['nickname'] = $user->nickname;
        $new_user['signature'] = $user->signature;
        $new_user['avatar_url'] = $user->avatar_url;
        $new_user['follow_count'] = $user->follow_count;
        $new_user['sex'] = $user->sex;
        $new_user['fans_count'] = $user->fans_count;
        $event_count = Event::where('user_id', $user->id)->count();
        $new_user['event_count'] = $event_count;

        return $new_user;
    }

    public function getUserEvents($user_id, Request $request)
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

        $current_user_id = $this->auth->user()->id;

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);

        $events = Event::where('user_id', $user_id)
            ->where('is_public', '=', 1)
            ->orderBy('create_time', 'DESC')->skip(($page - 1) * $per_page)
            ->take($per_page)->get();

        $result = [];

        foreach ($events as $key => $event) {
            $result[$key]['content'] = $event->content;

            $new_checkin = [];

            if ($event['type'] == 'USER_CHECKIN') {
                $checkin = Checkin::find($event->event_value);

                $new_attachs = [];

                if ($checkin) {
                    $result[$key]['content'] = $checkin->content;
                    $new_checkin['total_days'] = $checkin->total_days;
                    $new_checkin['id'] = $checkin->id;

                    foreach ($checkin->attaches as $k => $attach) {
                        $new_attachs[$k]['id'] = $attach->id;
                        $new_attachs[$k]['name'] = $attach->name;
                        $new_attachs[$k]['url'] = "http://www.keepdays.com/uploads/images/" . $attach->path . '/' . $attach->name;
                    }
                }

                $items = DB::table('checkin_item')
                    ->join('user_goal_item', 'user_goal_item.item_id', '=', 'checkin_item.item_id')
                    ->where('checkin_id', $event->event_value)
                    ->get();




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
            $new_user['avatar_url'] = $event->user->avatar_url;

            $result[$key]['user'] = $new_user;

            $goal = [];
            $goal['id'] = $event->goal->id;
            $goal['name'] = $event->goal->name;

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

    // 获取用户的目标列表
    public function getGoals(Request $request)
    {
        $messages = [
        ];

        $validation = Validator::make(Input::all(), [
            'day' => '',        // 具体日期
        ], $messages);

        if ($validation->fails()) {
            return API::response()->error(implode(',', $validation->errors()->all()), 500);
        }

        $user_id = $this->auth->user()->id;

        $date = $request->input("day");

//        DB::enableQueryLog();

        $user_goals = UserGoal::where('user_id', '=', $user_id)
            ->where(function ($query) use ($date) {
                if ($date) {
                    $query->where('start_date', '<=', $date)
                        ->where('end_date', '>=', $date)
                        ->orWhere('end_date', '=', NULL);
                }
            })
            ->orderBy('user_goals.status', 'asc')
            ->orderBy('user_goals.order', 'asc')
            ->get();

//                print_r(DB::getQueryLog());

        return $this->response->collection($user_goals, new UserGoalTransformer(), [], function ($resource, $fractal) {
            $fractal->setSerializer(new ArraySerializer());
        });
    }

    public function getPhotos($user_id, Request $request)
    {

        $attaches = User::find($user_id)
            ->attaches()
            ->where('attachable_id', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $new_attachs = [];

        foreach ($attaches as $k => $attach) {
            $new_attachs[$k]['id'] = $attach->id;
//            $new_attachs[$k]['url'] = 'http://drip.growu.me/uploads/images/' . $attach->path . '/' . $attach->name;
            $new_attachs[$k]['url'] = 'http://file.growu.me/' . $attach->name.'?imageslim';

        }

        return $new_attachs;
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

        $user_id = $this->auth->user()->id;

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
        $user_id = $this->auth->user()->id;

        return UserGoal::where('user_id', '=', $user_id)
            ->where(function ($query) use ($date) {
                if ($date) {
                    $query->where('start_date', '<=', $date)
                        ->where('end_date', '>=', $date)
                        ->orWhere('end_date', '=', NULL);
                }
            })
            ->count();
    }

    // 获取单个目标
    public function getGoal($goal_id, Request $request)
    {
        $user_id = $this->auth->user()->id;

        $user_goal = UserGoal::where('user_id', '=', $user_id)
            ->where('goal_id', '=', $goal_id)
            ->first();

        if (!$user_goal) {
            return $this->response->error("未制定该目标", 500);
        }

        return $this->response->item($user_goal, new UserGoalTransformer(), [], function ($resource, $fractal) {
            $fractal->setSerializer(new ArraySerializer());
        });

    }

    public function updateGoal($goal_id, Request $request)
    {

        $goal = Goal::findOrFail($goal_id);

        $user_id = $this->auth->user()->id;

        // 判断是否已经指定了该目标
        $user_goal = UserGoal::where('user_id', '=', $user_id)
            ->where('goal_id', '=', $goal_id)
            ->first();

        if (!$user_goal) {
            return $this->response->error("未制定该目标", 500);
        }

        $date_type = $request->input('date_type',1);
        $start_date = $request->input('start_date',date('Y-m-d'));
        $end_date = $request->input('end_date');
        $days = $request->input('expect_days',0);

        $input = $request->all();

        if($date_type == 2) {
            if ($end_date < $start_date) {
                return $this->response->error("结束日期不得小于开始日期", 500);
            }

            $start_dt = Carbon::parse($start_date);
            $end_dt = Carbon::parse($end_date);
            $max_days = $start_dt->diffInDays($end_dt);

            if($days > $max_days) {
                return $this->response->error("目标天数大于日期范围", 500);
            }
        }

        if ($input) {

            unset($input['items']);

            if($request->has('weeks')) {
                $input['weekday'] = implode(';',$input['weeks']);
                unset($input['weeks']);
            }

            // 修改目标状态
            if($date_type == 1) {
                $input['status'] = 1;
                $input['start_date'] = date('Y-m-d');
            } else {
                if($start_date <= date('Y-m-d')) {
                    $input['status'] = 1;
                } else {
                    $input['status'] = 0;
                }
            }

//            if(!$input['is_remind']) {
//                $input['remind_time'] = null;
//            }

            unset($input['is_remind']);

            $user_goal->update($input);

            $this->_insert_items($user_id, $goal_id, $request->input('items'));

        }


        return $goal;
    }

    private function _insert_items($user_id, $goal_id, $items)
    {
        if (!$items) return;

        DB::table('user_goal_item')
            ->where('user_id', $user_id)
            ->where('goal_id', $goal_id)
            ->where('is_del', 0)
            ->delete();

        foreach ($items as $item) {

            DB::table('user_goal_item')->insert(
                [
                    'item_name' => $item['name'],
                    'item_unit' => $item['unit'],
                    'item_expect' => $item['expect'],
                    'type' => $item['type'],
                    'create_time' => time(),
                    'goal_id' => $goal_id,
                    'user_id' => $user_id
                ]
            );

        }
    }


    public function getGoalWeek($goal_id, Request $request)
    {

        $user_id = $this->auth->user()->id;

        $dt = new Carbon();

        $start_day = $dt->startOfWeek();

        $result = [];

        for ($i = 0; $i < 7; $i++) {

            if ($i > 0) {
                $start_day->addDay();
            }

            $is_checkin = Checkin::where('goal_id', $goal_id)
                ->where('user_id', $user_id)
                ->where('checkin_day', $start_day->toDateString())
                ->first();

            $result[$i] = $is_checkin ? true : false;
        }

        return $result;

    }

    // 根据日期获取打卡
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

        $user_id = $this->auth->user()->id;

        // 获取当日打卡
        $checkins = Checkin::where('checkin_day','=',$day)
            ->where('user_id', $user_id)
            ->where('goal_id', $goal_id)
            ->orderBy('created_at','desc')
            ->get();

        $result = [];

        foreach ($checkins as $k=>$checkin) {
            $result[$k]['id'] = $checkin->id;
            $result[$k]['content'] = $checkin->content;
            $result[$k]['created_at']  = Carbon::parse($checkin->created_at)->toDateTimeString();

            // 获取EVENT
            $event = Event::where('eventable_id',$checkin->id)
                ->where('eventable_type','checkin')
                ->first();

            $result[$k]['event_id'] = is_null($event)?null:$event->event_id;
        }

        return $result;
    }

    public function getGoalCalendar($goal_id, Request $request)
    {

        $user_id = $this->auth->user()->id;

        // 获取所有的打卡日期

        $days = DB::table('checkins')
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

        $user_id = $this->auth->user()->id;

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
                $count = DB::table('checkins')
                    ->where('goal_id', $goal_id)
                    ->where('user_id', $user_id)
                    ->whereYear('checkin_day','=',$year)
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
                $count = DB::table('checkins')
                    ->where('goal_id', $goal_id)
                    ->where('user_id', $user_id)
                    ->whereYear('checkin_day','=',$year)
                    ->whereMonth('checkin_day','=',$month)
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
                $count = DB::table('checkins')
                    ->where('goal_id', $goal_id)
                    ->where('user_id', $user_id)
                    ->whereYear('checkin_day','=',$year)
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

        $user_id = $this->auth->user()->id;

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);

        $events = Event::where('goal_id', $goal_id)
            ->where('is_public', '=', 1)
            ->where('user_id', $user_id)
            ->orderBy('create_time', 'DESC')->skip(($page - 1) * $per_page)
            ->take($per_page)->get();

        $result = [];

        foreach ($events as $key => $event) {
            $result[$key]['content'] = $event->content;

            $new_checkin = [];

            if ($event['type'] == 'USER_CHECKIN') {
                $checkin = Checkin::find($event->event_value);

                $new_attachs = [];

                if ($checkin) {
                    $result[$key]['content'] = $checkin->content;
                    $new_checkin['total_days'] = $checkin->total_days;
                    $new_checkin['id'] = $checkin->id;

                    foreach ($checkin->attaches as $k => $attach) {
                        $new_attachs[$k]['id'] = $attach->id;
                        $new_attachs[$k]['name'] = $attach->name;
                        $new_attachs[$k]['url'] = "http://www.keepdays.com/uploads/images/" . $attach->path . '/' . $attach->name;
                    }
                }

                $items = DB::table('checkin_item')
                    ->join('user_goal_item', 'user_goal_item.item_id', '=', 'checkin_item.item_id')
                    ->where('checkin_id', $event->event_value)
                    ->get();


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
            $new_user['avatar_url'] = $event->user->avatar_url;
            $new_user['is_vip'] = $event->user->is_vip == 1 ? true : false;

            $result[$key]['user'] = $new_user;

            $goal = [];
            $goal['id'] = $event->goal->id;
            $goal['name'] = $event->goal->name;

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

    /**
     * 获取最新的消息
     * @return array
     */
    public function getNewMessages()
    {
        $user_id = $this->auth->user()->id;

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
        $result['fan_count'] = $fan_count;
        $result['notice_count'] = $notice_count;
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

        $user_id = $this->auth->user()->id;

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
        $user_id = $this->auth->user()->id;

        $user = User::find($user_id);
        $nickname = Input::get('nickname');
        $signature = Input::get('signature');
        $avatar_url = Input::get('avatar_url');

        $user->nickname = $nickname;
        $user->signature = $signature;
        $user->avatar_url = $avatar_url;
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
            'user_id' => $this->auth->user()->id,
            'type_id' => $request->input('type'),
            'content' => $request->input('content'),
            'device' => json_encode($request->input('device')),
            'version' => $request->input('app_version'),
            // TODO 移除version字段
            'app_version' => $request->input('app_version'),
            'web_version' => $request->input('web_version'),
            'contact' => $request->input('contact'),
            'create_time' => time(),
        ];

        $feedback_id = DB::table('feedbacks')->insertGetId($data);

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

        $user_id = $this->auth->user()->id;

        // 判断是否为用户自己
        if ($target_id == $user_id) {
            return $this->response->error('关注对象不能为自己', 500);
        }

        // 判断是否关注
        $is_follow = DB::table('user_follows')
            ->where('user_id', $user_id)
            ->where('follow_user_id', $target_id)
            ->first();

        if ($is_follow) {
            return $this->response->error('请勿重复关注', 500);
        }

        DB::table('user_follows')->insert([
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

        $user_id = $this->auth->user()->id;
        // 判断是否为用户自己
        if ($target_id == $user_id) {
            return $this->response->error('关注对象不能为自己', 500);
        }

        // 判断是否关注
        $is_follow = DB::table('user_follows')
            ->where('user_id', $user_id)
            ->where('follow_user_id', $target_id)
            ->first();

        if (!$is_follow) {
            return $this->response->error('已取消关注', 500);
        }

        DB::table('user_follows')
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
        $report->user_id = $this->auth->user()->id;
        $report->reason = $request->reason;
        $report->reportable_type = $request->obj_type;
        $report->reportable_id = $request->obj_id;
        $report->create_time = time();

        $report->save();

        return API::response()->array(['status' => true, 'message' => '举报成功'])->statusCode(200);

    }

    // TODO 删除
    public function getFans($user_id, Request $request)
    {
        // 关注时间排序


        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);


        $users = DB::table('user_follows')
            ->join('users', 'users.id', '=', 'user_follow.user_id')
            ->where('follow_user_id', '=', $user_id)
            ->orderBy('create_time', 'asc')
            ->skip(($page - 1) * $per_page)
            ->limit($per_page)
            ->get();

        $new_users = [];

        foreach ($users as $k => $user) {

//            $new_user = [];

            $new_users[$k]['id'] = $user->id;
            $new_users[$k]['nickname'] = $user->nickname;
            $new_users[$k]['signature'] = $user->signature;
            $new_users[$k]['avatar_url'] = $user->avatar_url;

            $is_follow = DB::table('user_follows')
                ->where('user_id', $user_id)
                ->where('follow_user_id', $user->id)
                ->first();

            // 判断是否关注该用户
            $new_users[$k]['is_follow'] = $is_follow ? true : false;

//            $new_users[$k]['user'] = $new_user;

        }

        return $new_users;

    }


    /**
     * 获取用户关注列表
     * @param $user_id
     * @param Request $request
     * @return array
     */
    public function getFollowers($user_id, Request $request)
    {
        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);

        $current_user = $this->auth->user();

        $user_follows = UserFollow::where('follow_user_id','=',$user_id)
            ->orderBy('created_at', 'asc')
            ->skip(($page-1)*$per_page)
            ->limit($per_page)
            ->get();

        $new_users = [];

        foreach ($user_follows as $k => $user_follow) {

            $user = $user_follow->user;

            $new_users[$k]['id'] = $user->id;
            $new_users[$k]['nickname'] = $user->nickname;
            $new_users[$k]['signature'] = $user->signature;
            $new_users[$k]['avatar_url'] = $user->avatar_url;

            $is_follow = UserFollow::where('user_id', $current_user->id)
                ->where('follow_user_id', $user->id)
                ->first();

            // 判断是否关注该用户
            $new_users[$k]['is_follow'] = $is_follow ? true : false;
        }

        return $new_users;

    }


    /**
     * 获取关注用户列表
     * @param $user_id
     * @param Request $request
     * @return array
     */
    public function getFollowings($user_id, Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page;

        $current_user = $this->auth->user();

        $user_follows = UserFollow::where('user_id','=',$user_id)
            ->orderBy('created_at', 'asc')
            ->skip(($page-1)*$per_page)
            ->limit($per_page)
            ->get();

        $new_users = [];

        foreach ($user_follows as $k => $user_follow) {

            $user = $user_follow->follow_user;

            $new_users[$k]['id'] = $user->id;
            $new_users[$k]['nickname'] = $user->nickname;
            $new_users[$k]['signature'] = $user->signature;
            $new_users[$k]['avatar_url'] = $user->avatar_url;

            // 判断是否关注该用户
            $is_follow = UserFollow::where('user_id', $current_user->id)
                ->where('follow_user_id', $user->id)
                ->first();

            $new_users[$k]['is_follow'] = $is_follow ? true : false;
        }

        return $new_users;
    }

    public function energy(Request $request)
    {
        $user_id = $request->user_id;
        $offset = $request->offset;

        $logs = DB::table('energy')
            ->join('energy_type', 'energy_type.name', '=', 'energy.obj_type')
            ->where('user_id', '=', $user_id)
            ->orderBy('create_time', 'desc')
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
        $user_id = $this->auth->user()->id;

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

            $is_follow = DB::table('user_follows')
                ->where('user_id', $user_id)
                ->where('follow_user_id', $user->id)
                ->first();

            // 判断是否关注该用户

            $new_user = [];

            $new_user['is_follow'] = $is_follow ? true : false;
            $new_user['id'] = $user->id;
            $new_user['nickname'] = $user->nickname;
            $new_user['avatar_url'] = $user->avatar_url;

            $new_messages[$k]['user'] = $new_user;


        }


        return $new_messages;
    }

    public function getMessageDetail($id)
    {
        $message = DB::table('messages')
            ->where('message_id', $id)
            ->first();

        $new_message = array();


        if ($message) {
            $new_message['id'] = $message->message_id;
            $new_message['title'] = $message->title;
            $new_message['content'] = $message->content;
        }

        return $new_message;
    }


    public function getCommentMessages(Request $request)
    {
        $user_id = $this->auth->user()->id;

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

            $new_user['id'] = $user->id;
            $new_user['nickname'] = $user->nickname;
            $new_user['avatar_url'] = $user->avatar_url;

            $new_messages[$k]['user'] = $new_user;

            $comment = Comment::find($message->msgable_id);

            $new_comment = [];
            $new_comment['id'] = $comment->comment_id;
            $new_comment['content'] = $comment->content;
            $new_comment['event_id'] = $comment->event_id;

            if ($comment->parent_id == 0) {
                $event = Event::findOrFail($comment->event_id);
                if ($event->type == 'USER_CHECKIN') {
                    $new_comment['source'] = $event->checkin->content;
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
        $user_id = $this->auth->user()->id;

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
                $new_like['source'] = $event->checkin->content;
            } else {
                $new_like['source'] = $event->event_content;
            }

            $new_message = [];

            $new_message['like'] = $new_like;

            $new_message['id'] = $message->message_id;
            $new_message['created_at'] = date('Y-m-d H:i:s', $message->create_time);

            $user = User::find($message->from_user);

            $new_user = [];
            $new_user['id'] = $user->id;
            $new_user['nickname'] = $user->nickname;
            $new_user['avatar_url'] = $user->avatar_url;

            $is_follow = DB::table('user_follows')
                ->where('user_id', $user_id)
                ->where('follow_user_id', $user->id)
                ->first();

            $new_user['is_follow'] = $is_follow ? true : false;
            $new_message['user'] = $new_user;

            array_push($new_messages, $new_message);
        }


        return $new_messages;
    }

    public function getNoticeMessages(Request $request)
    {
        $user_id = $this->auth->user()->id;

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 20);

        $messages = DB::table('messages')
            ->where('type', 6)
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
            ->where('type', 6)
            ->where('status', 0)
            ->update([
                'status' => 1
            ]);

        $new_messages = [];

        foreach ($messages as $k => $message) {

            $new_message = [];
            $new_message['id'] = $message->message_id;
            $new_message['title'] = $message->title;
            $new_message['content'] = $message->content ? mb_substr(strip_tags($message->content), 0, 150) : '';
            $new_message['created_at'] = date('Y-m-d H:i:s', $message->create_time);

            array_push($new_messages, $new_message);
        }


        return $new_messages;
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

    public function getCoinLog(Request $request)
    {
        $user_id = $this->auth->user()->id;
        $page = $request->input('page');
        $per_page = $request->input('per_page');

        $logs = DB::table('energy')
            ->join('energy_type', 'energy_type.name', '=', 'energy.obj_type')
            ->where('user_id', '=', $user_id)
            ->orderBy('create_time', 'desc')
            ->skip(($page - 1) * $per_page)
            ->limit($per_page)
            ->get();

        $new_logs = [];

        foreach ($logs as $k => $log) {
            $new_logs[$k]['id'] = $log->id;
            $new_logs[$k]['created_at'] = date('Y-m-d H:i:s', $log->create_time);
            $new_logs[$k]['change'] = $log->change;
            $new_logs[$k]['name'] = $log->name2;

        }

        return $new_logs;

    }

    public function bindPhone(Request $request)
    {
        $messages = [
            'account.required' => '请输入手机号',
            'code.required' => '请输入验证码',
        ];

        $validation = Validator::make(Input::all(), [
            'account' => 'required',        // 邮箱
            'code' => 'required|digits:4'
        ], $messages);

        if ($validation->fails()) {
            return $this->response->error(implode(',', $validation->errors()->all()), 500);
        }

        $phone = $request->input('account');
        $code = $request->input('code');
        $user = $this->auth->user();

        if (!preg_match("/^1[34578]\d{9}$/", $phone)) {
            return $this->response->error('手机号格式不正确', 500);
        }

        // 查找手机号是否被绑定
        $is_bind = User::where('phone', $phone)->first();

        if ($is_bind) {
            return $this->response->error('手机号已被绑定', 500);
        }

        $code = DB::table('verify_code')
            ->where('send_type', 'phone')
            ->where('send_object', $phone)
            ->where('type', 'bind')
            ->where('code', $code)
            ->orderBy('create_time', 'desc')
            ->first();

        if ($code) {
            if ($code->status == 1) {
                return $this->response->error('验证码已使用', 500);
            }

            if ($code->expire_time < time()) {
                return $this->response->error('验证码已过期', 500);
            }
        } else {
            return $this->response->error('验证码不存在', 500);
        }

        $user->phone = $phone;

        $user->save();

        return $this->response->item($user, new UserTransformer);
    }


    public function getUserInfo(Request $request)
    {
        $user = $this->auth->user();
        return $this->response->item($user, new UserTransformer(), [], function ($resource, $fractal) {
            $fractal->setSerializer(new ArraySerializer());
        });
    }

    public function bindEmail(Request $request)
    {
        $messages = [
            'account.required' => '请输入手机号',
            'code.required' => '请输入验证码',
        ];

        $validation = Validator::make(Input::all(), [
            'account' => 'required',        // 邮箱
            'code' => 'required|digits:4'
        ], $messages);

        if ($validation->fails()) {
            return $this->response->error(implode(',', $validation->errors()->all()), 500);
        }

        $email = $request->input('account');
        $code = $request->input('code');
        $user = $this->auth->user();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->error('手机号格式不正确', 500);
        }

        // 查找手机号是否被绑定
        $is_bind = User::where('email', $email)->first();

        if ($is_bind) {
            return $this->response->error('邮箱已被绑定', 500);
        }

        $code = DB::table('verify_code')
            ->where('send_type', 'email')
            ->where('send_object', $email)
            ->where('type', 'bind')
            ->where('code', $code)
            ->orderBy('create_time', 'desc')
            ->first();

        if ($code) {
            if ($code->status == 1) {
                return $this->response->error('验证码已使用', 500);
            }

            if ($code->expire_time < time()) {
                return $this->response->error('验证码已过期', 500);
            }
        } else {
            return $this->response->error('验证码不存在', 500);
        }

        $user->email = $email;

        $user->save();

        return $this->response->item($user, new UserTransformer(), [], function ($resource, $fractal) {
            $fractal->setSerializer(new ArraySerializer());
        });
    }

    public function bindWechat(Request $request)
    {

        $params = $this->_parse_wechat($request);

        Log::debug("微信参数");

        Log::debug($params);


        $provider = DB::table('users_bind')
            ->where('openid', $params['openid'])
            ->where('provider', 'wechat')
            ->first();

        if ($provider) {
            return $this->response->error('该微信号已绑定', 500);
        }

        $user = $this->auth->user();

        DB::table('users_bind')->insert([
            'user_id' => $user->id,
            'openid' => $params['openid'],
            'access_token' => $params['access_token'],
            'expire_in' => $params['expire_in'],
            'avatar' => $params['avatar'],
            'sex' => $params['sex'],
            'province' => $params['province'],
            'city' => $params['city'],
            'nickname' => $params['nickname'],
            'provider' => $params['provider'],
            'unionid' => isset($params['unionid']) ? $params['unionid'] : '',
        ]);

        return $user;


    }

    private function _parse_wechat($request)
    {
        // 获取
        $client = new Client();

        $res = $client->request('GET', 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=wxac31b5ac3e65915a&secret=f8b8aac88586192c2b60bfbbf807ef7d&code=' . $request->code . '&grant_type=authorization_code', []);

        if ($res->getStatusCode() != 200) {
            $this->response->error("请求access_token失败", 500);
        }

        $ret = json_decode($res->getBody());

        if (isset($ret->errcode)) {
            $this->response->error($ret->errmsg, 500);
        }

        $res2 = $client->request('GET', 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $ret->access_token . '&openid=' . $ret->openid);

        if ($res2->getStatusCode() != 200) {
            $this->response->error("请求用户信息失败", 500);
        }

        $ret2 = json_decode($res2->getBody());

        if (isset($ret2->errcode)) {
            $this->response->error($ret2->errmsg, 500);
        }

        return [
            'openid' => $ret2->openid,
            'access_token' => $ret->access_token,
            'expire_in' => time() + 7200,
            'avatar' => $ret2->headimgurl,
            'sex' => $ret2->sex,
            'province' => $ret2->province,
            'city' => $ret2->city,
            'country' => $ret2->country,
            'nickname' => $ret2->nickname,
            'provider' => 'wechat',
            'unionid' => $ret2->unionid,
            'device' => $request->device
        ];
    }

    public function bindWeibo(Request $request)
    {

        $params = $this->_parse_weibo($request);

        $provider = DB::table('users_bind')
            ->where('openid', $params['openid'])
            ->where('provider', 'weibo')
            ->first();

        if ($provider) {
            return $this->response->error('该微博已绑定', 500);
        }

        $user = $this->auth->user();

        DB::table('users_bind')->insert([
            'user_id' => $user->id,
            'openid' => $params['openid'],
            'access_token' => $params['access_token'],
            'expire_in' => $params['expire_in'],
            'avatar' => $params['avatar'],
            'sex' => $params['sex'],
            'province' => $params['province'],
            'city' => $params['city'],
            'nickname' => $params['nickname'],
            'provider' => $params['provider'],
            'unionid' => isset($params['unionid']) ? $params['unionid'] : '',
        ]);

        return $this->response->item($user, new UserTransformer(), [], function ($resource, $fractal) {
            $fractal->setSerializer(new ArraySerializer());
        });

    }

    private function _parse_weibo($request)
    {
        $client = new Client();

        $res = $client->request('GET', 'https://api.weibo.com/2/users/show.json?uid=' . $request->userId . '&access_token=' . $request->access_token, []);

        if ($res->getStatusCode() != 200) {
            $this->response->error("获取用户信息失败", 500);
        }

        $ret = json_decode($res->getBody());

        if (isset($ret->error_code)) {
            $this->response->error($ret->error, 500);
        }

        $sex = 0;

        if ($ret->gender == 'm') {
            $sex = 1;
        } else if ($ret->gender == 'f') {
            $sex = 2;
        }
        return [
            'openid' => $request->userId,
            'access_token' => $request->access_token,
            'expire_in' => $request->expires_time,
            'avatar' => $ret->avatar_hd,
            'sex' => $sex,
            'province' => $ret->province,
            'city' => $ret->city,
            'nickname' => $ret->screen_name,
            'provider' => 'weibo',
            'device' => $request->device
        ];
    }

    public function bindQQ(Request $request)
    {

        $params = $this->_parse_qq($request);

        $provider = DB::table('users_bind')
            ->where('openid', $params['openid'])
            ->where('provider', 'qq')
            ->first();

        if ($provider) {
            return $this->response->error('该QQ已绑定', 500);
        }

        $user = $this->auth->user();

        DB::table('users_bind')->insert([
            'user_id' => $user->id,
            'openid' => $params['openid'],
            'access_token' => $params['access_token'],
            'expire_in' => $params['expire_in'],
            'avatar' => $params['avatar'],
            'sex' => $params['sex'],
            'province' => $params['province'],
            'city' => $params['city'],
            'nickname' => $params['nickname'],
            'provider' => $params['provider'],
            'unionid' => isset($params['unionid']) ? $params['unionid'] : '',
        ]);

        return $this->response->item($user, new UserTransformer(), [], function ($resource, $fractal) {
            $fractal->setSerializer(new ArraySerializer());
        });
    }


    private function _parse_qq($request)
    {
        // 获取
        $client = new Client();

        $app_id = 1106248902;

        $device = $request->device;

        if (isset($device['platform']) && $device['platform'] == 'iOS') {
            $app_id = 1106192747;
        }

        $res = $client->request('GET', 'https://graph.qq.com/user/get_user_info?access_token=' . $request->access_token . '&oauth_consumer_key=' . $app_id . '&openid=' . $request->userid, []);

        if ($res->getStatusCode() != 200) {
            $this->response->error("获取用户信息失败", 500);
        }

        $ret = json_decode($res->getBody());

        if (isset($ret->ret) && $ret->ret != 0) {
            $this->response->error($ret->msg, 500);
        }

        $sex = 0;

        if ($ret->gender == '男') {
            $sex = 1;
        } else if ($ret->gender == '女') {
            $sex = 2;
        }

        return [
            'openid' => $request->userid,
            'access_token' => $request->access_token,
            'expire_in' => $request->expires_time,
            'avatar' => $ret->figureurl_2,
            'sex' => $sex,
            'province' => $ret->province,
            'city' => $ret->city,
            'nickname' => $ret->nickname,
            'provider' => 'qq',
            'device' => $request->device
        ];
    }


    public function buyVip(Request $request)
    {
        $messages = [
            'required' => '缺少参数 :attribute',
            'integer' => '月数必须为正整数',
        ];

        $validation = Validator::make(Input::all(), [
            'num' => 'required|integer',
        ], $messages);

        if ($validation->fails()) {
            return API::response()->error(implode(',', $validation->errors()->all()), 500);
        }

        $user = $this->auth->user();

        $num = $request->input("num", 0);

        if ($user->energy_count < $num * 1000) {
            return API::response()->error("水滴币数量不足", 500);
        }

        if ($user->is_vip == 1) {
            $user->vip_end_date = date('Y-m-d', strtotime($user->vip_end_date . ' + ' . ($num * 30) . ' days'));
        } else {
            $user->is_vip = 1;
            $user->vip_begin_date = date('Y-m-d');
            $user->vip_end_date = date('Y-m-d', strtotime(date('Y-m-d') . ' + ' . ($num * 30) . ' days'));
        }

        $user->energy_count -= $num * 1000;
        $user->save();

        $energy = new Energy();
        $energy->user_id = $user->id;
        $energy->change = -($num * 1000);
        $energy->obj_type = null;
        $energy->obj_id = null;
        $energy->create_time = time();
        $energy->save();

//        return $this->response->item($user, new UserTransformer());
        return $this->response->item($user, new UserTransformer(), [], function ($resource, $fractal) {
            $fractal->setSerializer(new ArraySerializer());
        });
    }

    public function getSearchUsers(Request $request) {
        $text = $request->text;

        $users = User::where('nickname','like','%'.$text.'%')
            ->limit(20)
            ->get();

        $new_users = [];

        foreach($users as $k=>$user) {
            $new_users[$k]['id'] = $user->id;
            $new_users[$k]['nickname'] = $user->nickname;
            $new_users[$k]['avatar_url'] = $user->avatar_url;

            $is_follow = DB::table('user_follows')
                ->where('user_id', $this->auth->user()->id)
                ->where('follow_user_id', $user->id)
                ->first();

            $new_users[$k]['is_follow'] = $is_follow?true:false;

        }

        return $new_users;

    }
}