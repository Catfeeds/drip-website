<?php
/**
 * Created by PhpStorm.
 * User: tuo3
 * Date: 2016/11/17
 * Time: 下午10:27
 */

namespace App\Http\Controllers\Api\V1;

use App\Like;
use Auth;
use Validator;
use API;
use DB;

use App\Event;
use App\Checkin;
use App\User;
use App\Models\Message as Message;
use App\Models\Topic as Topic;
use App\Libs\MyJpush as MyJpush;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class TopicController extends BaseController {

    public function info(Request $request) {
        $name = $request->name;
        $topic = Topic::Where('name','=',$name)->first();

        return API::response()->array(['status' => true, 'message' =>'','data'=>$topic])->statusCode(200);

    }

    public function events(Request $request) {
        $topic_id = $request->topic_id;
        $topic = Topic::find($topic_id);
        $type = $request->type;

        $events = [];

        if($topic) {
            if($type == 'hot') {
                $events = $topic->events()
                    ->where('is_hot','=',1)
                    ->where('is_public','=',1)
                    ->orderBy('create_time','DESC')
                    ->take(20)->get();
            }  else {
                $events =
                    $topic->events()
                        ->where('is_public','=',1)
                        ->orderBy('create_time','DESC')
                        ->take(20)->get();
            }
        }


        $new_events = [];

        foreach ($events as $key => $event) {
            $new_events[$key] = $event;

            if($event->type == 'USER_CHECKIN') {
                $new_events[$key]->checkin = DB::table('checkin')
                    ->where('checkin_id', $event->event_value)
                    ->first();
                $new_events[$key]->checkin->items = DB::table('checkin_item')
                    ->join('user_goal_item','user_goal_item.item_id','=','checkin_item.item_id')
                    ->where('checkin_id', $event->event_value)
                    ->get();
                $new_events[$key]->checkin->attaches = DB::table('attachs')
                    ->where('attachable_id', $event->event_value)
                    ->where('attachable_type','checkin')
                    ->get();
            }


            $new_events[$key]->user = DB::table('users')
                ->where('user_id', $event->user_id)
                ->first();


            $new_events[$key]->goal =DB::table('goal')
                ->where('goal_id', $event->goal_id)
                ->first();

        }
        
        return API::response()->array(['status' => true, 'message' =>'','data'=>$events])->statusCode(200);
    }
}