<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/2/24
 */

namespace  App\Http\Controllers\Api\V2\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\UserGoal;

use DB;

class UserGoalTransformer extends TransformerAbstract
{
    private $params = [];

    function __construct($params = [])
    {
        $this->params = $params;
    }

    public function transform(UserGoal $user_goal)
    {
        $items = DB::table('user_goal_item')
            ->where('goal_id', $user_goal->goal_id)
            ->where('user_id', $user_goal->user_id)
            ->get();

        $new_items = array();

        foreach($items as $k=>$item) {
            $new_items[$k]['id'] = $item->item_id;
            $new_items[$k]['name'] = $item->item_name;
            $new_items[$k]['expect'] = $item->item_expect;
            $new_items[$k]['unit'] = $item->item_unit;
            $new_items[$k]['type'] = $item->type;
        }

        $result = [];
        $result['id'] = $user_goal->goal_id;
        $result['name'] = $user_goal->name;
        //TODO 删除is_checkin
        $result['is_checkin'] = date('Y-m-d',strtotime($user_goal->last_checkin_at)) >= date('Y-m-d') ? true : false;
        $result['is_today_checkin'] = date('Y-m-d',strtotime($user_goal->last_checkin_at)) >= date('Y-m-d') ? true : false;
        $result['max_daily_count'] = $user_goal->max_daily_count;
        $result['remind_time'] = $user_goal->remind_time ? substr($user_goal->remind_time, 0, 5) : null;
        $result['is_remind'] = $user_goal->remind_time ?true:false;
//        $result['expect_days'] =  $user_goal->expect_days>0?$user_goal->expect_days:ceil((time() -strtotime($user_goal->start_date)) / 86400);
        $result['expect_days'] =  $user_goal->expect_days;

        $result['total_days'] = $user_goal->total_days;
        $result['series_days'] = $user_goal->series_days;
        $result['date_type'] = $user_goal->date_type;
        $result['start_date'] = $user_goal->start_date;
        $result['end_date'] = $user_goal->end_date;
        $result['time_type'] = $user_goal->time_type;
        $result['start_time'] = $user_goal->start_time ? substr($user_goal->start_time, 0, 5) : null;
        $result['end_time'] = $user_goal->end_time ? substr($user_goal->end_time, 0, 5) : null;
        $result['checkin_model'] = $user_goal->checkin_model;

        $weeks = [];

        if($user_goal->weekday) {
            $arr = explode(';',$user_goal->weekday);

            foreach ($arr as $k=>$v) {
                $weeks[] = (int)$v;
            }
        }

        $result['weeks'] = $weeks;
        $result['is_public'] = (boolean)$user_goal->is_public;
        $result['order'] = $user_goal->order;
        $result['status'] = $user_goal->status;
        $result['items'] = $new_items;

        return $result;
    }
}