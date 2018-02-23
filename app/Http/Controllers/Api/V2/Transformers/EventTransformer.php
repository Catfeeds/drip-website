<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/1/9
 */

namespace  App\Http\Controllers\Api\V2\Transformers;

use League\Fractal\TransformerAbstract;
use App\Event;

use DB;
use Carbon\Carbon;

class EventTransformer extends TransformerAbstract
{
    public function transform(Event $event)
    {
        $new_event = [];

        $new_event['id'] = $event->event_id;
        $new_event['content'] = $event->event_content;
        $new_event['like_count'] = $event->like_count;

        $new_checkin = [];

        if ($event->type == 'USER_CHECKIN') {

            $checkin = DB::table('checkin')
                ->where('checkin_id', $event->event_value)
                ->first();

            $content = $checkin->checkin_content;

            $new_event['content'] = $content ? mb_substr($content, 0, 20) : '';

            $new_checkin['id'] = $checkin->checkin_id;
            $new_checkin['total_days'] = $checkin->total_days;

			$items = DB::table('checkin_item')
					->join('user_goal_item','user_goal_item.item_id','=','checkin_item.item_id')
					->where('checkin_id', $event->event_value)
					->get();

			$new_items = [];

            foreach ($items as $k => $item) {
                $new_items[$k]['id'] = $item->item_id;
                $new_items[$k]['name'] = $item->item_name;
                $new_items[$k]['unit'] = $item->item_unit;
                $new_items[$k]['type'] = $item->item_type;
                $new_items[$k]['value'] = $item->item_value;

            }

            $new_event['items'] = $new_items;

            $attachs = DB::table('attachs')
                ->where('attachable_id', $event->event_value)
                ->where('attachable_type', 'checkin')
                ->get();

            $new_attachs = [];

            foreach ($attachs as $k => $attach) {
                $new_attachs[$k]['id'] = $attach->attach_id;
                $new_attachs[$k]['name'] = $attach->attach_name;
                $new_attachs[$k]['url'] = "http://drip.growu.me/uploads/images/" . $attach->attach_path . '/' . $attach->attach_name;
            }

            $new_event['attachs'] = $new_attachs;
        }

        $new_event['checkin'] = $new_checkin;

        $user = DB::table('users')
            ->where('user_id', $event->user_id)
            ->first();

        $new_user = [];
        $new_user['id'] = $user->user_id;
        $new_user['nickname'] = $user->nickname;
        $new_user['avatar_url'] = $user->user_avatar;
        $new_user['is_vip'] = $user->is_vip==1?true:false;
        $new_event['user'] = $new_user;

        $goal = [];
        $goal['id'] = $event->goal->goal_id;
        $goal['name'] = $event->goal->goal_name;

        $new_event['goal'] = $goal;
        $new_event['created_at'] = Carbon::parse($event->created_at)->toDateTimeString();
        $new_event['updated_at'] = Carbon::parse($event->updated_at)->toDateTimeString();

        return $new_event;
    }
}
