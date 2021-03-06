<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/1/9
 */

namespace  App\Http\Controllers\Api\V3\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\Event;
use App\Models\Attach;
use App\Checkin;
use App\User;

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
        $new_event['is_hot'] = (boolean)$event->is_hot;

        $new_checkin = [];

        if ($event->type == 'USER_CHECKIN') {

            $checkin = Checkin::find($event->event_value);

            $content = $checkin->content;

            $new_event['content'] = $content ? mb_substr($content, 0, 20) : '';

            $new_checkin['id'] = $checkin->id;
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
                $new_items[$k]['type'] = $item->type;
                $new_items[$k]['value'] = $item->item_value;
            }

            $new_event['items'] = $new_items;

//            $attachs = DB::table('attachs')
//                ->where('attachable_id', $event->event_value)
//                ->where('attachable_type', 'checkin')
//                ->get();

            $new_attachs = [];

            foreach ($checkin->attaches as $k => $attach) {
                $new_attachs[$k]['id'] = $attach->id;
                $new_attachs[$k]['name'] = $attach->name;
//                $new_attachs[$k]['url'] = "http://drip.growu.me/uploads/images/" . $attach->path . '/' . $attach->name;
                $new_attachs[$k]['url'] = "http://file.growu.me/".$attach->name."?imageslim";
            }

            $new_event['attachs'] = $new_attachs;
        }

        $new_event['checkin'] = $new_checkin;

        $user = User::find($event->user_id);

        $new_user = [];
        $new_user['id'] = $user->id;
        $new_user['nickname'] = $user->nickname;
        $new_user['avatar_url'] = $user->avatar_url;
        $new_user['is_vip'] = $user->is_vip==1?true:false;
        $new_user['verified_type'] = $user->verified_type;
        $new_event['user'] = $new_user;

        $new_event['goal'] = $event->goal;
        $new_event['created_at'] = Carbon::parse($event->created_at)->toDateTimeString();
        $new_event['updated_at'] = Carbon::parse($event->updated_at)->toDateTimeString();

        return $new_event;
    }
}
