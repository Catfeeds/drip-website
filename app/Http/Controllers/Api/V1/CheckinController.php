<?php
/**
 * 订单控制器
 */
namespace App\Http\Controllers\Api\V1;

use App\Models\Attach;
use Auth;

use App\User;
use App\Checkin;
use App\Event;
use App\Models\Topic as Topic;
use App\Models\Energy as Energy;

use Validator;
use API;
use DB;
use Log;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class CheckinController extends BaseController {

    /**
     * 创建接口
     */
    public function create(Request $request) {

        Log::info($request);


    	$this->validate($request, [
	        'obj_id'	=> 'required',	// 对象id
	        'obj_type' 	=> 'required',	// 对象类型 GOAL,PLAN,GROUP
	        'content' 	=> '',	// 备注
            'is_public' => '',          // 是否公开
	        'day'    	=> 'date',		// 打卡类型
	        'items'   	=> '',          // 项目
	        'attaches'   => '',         // 附件
	    ]);

	    $day = $request->input('day');
	    $user_id = $this->auth->user()->user_id;
	   	$obj_id = $request->input('obj_id');
	    $obj_type = $request->input('obj_type');
	    $content = $request->input('content');


    	// 是否补打卡
    	$isReCheckin = false;
        $add_energy = 0;


        $today = date('Y-m-d');

    	if(isset($day)) {
            $isReCheckin = true;
    		if($day > $today || ($today-$day) > 3 ) {
                return $this->response->array(['status'=>false,'message'=>'日期非法']);
    		}
    	} else {
    		$day = $today;
    	}

    	if(strtolower($obj_type) === 'goal') {
    		// 获取目标信息
    		$user_goal = User::find($user_id)
                            ->goals()
                            ->wherePivot('goal_id', '=', $obj_id)
                            ->wherePivot('is_del','=',0)
                            ->first();

            if(empty($user_goal)) {
                return $this->response->array(['status'=>false,'message'=>'未设定该目标']);
            }

            $series_days = $user_goal->pivot->series_days;

    		// 获取当天的打卡记录
    		$user_checkin = Checkin::where('user_id', '=', $user_id)
                    ->where('goal_id', '=', $obj_id)
                    ->where('checkin_day', '=', $day)
                    ->first();

    		// 如果存在该条打卡记录
    		if($user_checkin) {
               return $this->response->array(['status'=>false,'message'=>'今日已打卡']);
    		}

    		$checkin = new Checkin();
    		$checkin->checkin_content = nl2br($content);
    		$checkin->checkin_day = $day;
            $checkin->obj_id = $obj_id;
            $checkin->goal_id = $obj_id;
            $checkin->obj_type = $obj_type;
            $checkin->user_id = $user_id;
            $checkin->is_public = $request->is_public;
            $checkin->checkin_time = time();
            $checkin->save();

            if(!$isReCheckin) {
                if($series_days<10){
                    $add_energy = 5;
                }else if ($series_days>=10&&$series_days<30) {
                    $add_energy = 10;
                } else if ($series_days>=30&&$series_days<60) {
                    $add_energy = 15;
                }else if ($series_days>=60) {
                    $add_energy = 20;
                }
            }

            // 如果存在该条打卡记录
            if(date('Y-m-d',$user_goal->pivot->last_checkin_time)==date("Y-m-d",strtotime("-1 day",strtotime($day)))) {
                $series_days += 1;
            } else {
                $series_days = 1;
            }

            $total_days = $user_goal->pivot->total_days;

            $total_days ++;

            $checkin->total_days = $total_days;
            $checkin->series_days = $series_days;

            $checkin->save();

            // 插入items
            $items = Input::get('items');
            if(!empty($items)) {
                foreach($items as $item) {
                    DB::table('checkin_item')
                        ->insert([
                           'checkin_id'=>$checkin->checkin_id,
                            'item_id'=>$item['item_id'],
                            'item_value'=>$item['item_expect']
                        ]);
                }
            }

            // 更新附件
            if($attaches = $request->input('attaches')) {
                foreach($attaches as $attach) {
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
            $user_goal->pivot->last_checkin_time = $day<$today?strtotime($day)+86439:time();
            $user_goal->pivot->save();
            // $user_goal->updateExistingPivot();
//
//            User::find($user_id)
//                ->goals()
//                ->wherePivot('is_del','=',0)
//                ->updateExistingPivot($obj_id,$data);

            User::find($user_id)->increment('checkin_count',1);
            User::find($user_id)->increment('energy_count',$add_energy);

            if($add_energy == 0) {
                $energy = new Energy();
                $energy->user_id = $user_id;
                $energy->change = $add_energy;
                $energy->obj_type = 'checkin';
                $energy->obj_id = $checkin->checkin_id;
                $energy->create_time = time();
                $energy->save();
            }

            $event = new Event();
            $event->goal_id = $obj_id;
            $event->user_id = $user_id;
            $event->event_value = $checkin->checkin_id;
            $event->type = 'USER_CHECKIN';
            if($request->is_public) {
                $event->is_public = $request->is_public;
            }
            $event->create_time = time();

            $event->save();

            //更新用户目标表
            if($content) {
                $this->_parse_content($content,$user_id,$event->event_id);
            }

    	}

        return $this->response->array(['status'=>true,'message'=>'打卡成功','data'=>['energy'=>$add_energy,'event_id'=>$event->event_id]]);

    }


    private function _parse_content($content,$user_id,$event_id){
        $topic_pattern = "/\#([^\#|.]+)\#/";
        preg_match_all($topic_pattern,$content,$topic_array);
        foreach($topic_array[0] as $v) {
            // 查找是否存在

            $name = str_replace('#','',$v);

            $topic = Topic::where('name','=',$name)->first();

            if(!$topic) {
                $topic = new Topic();
                $topic->name = $name;
                $topic->create_time = time();
                $topic->create_user = $user_id;
            }

            // 插入对应关系
            DB::table('event_topic')->insert(['topic_id'=>$topic->id,'event_id'=>$event_id]);

            $topic->follow_count += 1;
            $topic->save();
        }

    }
}