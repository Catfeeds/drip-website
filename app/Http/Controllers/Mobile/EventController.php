<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/4/2
 */

namespace App\Http\Controllers\Mobile;

use App\Goal;
use App\Event;
use App\Checkin;
use App\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EventController extends Controller
{

    /**
     * 显示所给定的用户个人数据。
     *
     * @param  int $id
     * @return Response
     */
    public function index()
    {
        return view('home');
    }

    public function getEventDetail($event_id, Request $request)
    {
        // 获取最近6条精选动态
        $events = Event::where('is_hot', '=', 1)
            ->where('is_public', '=', 1)
            ->orderBy('created_at', 'DESC')
            ->take(6)
            ->get();

        $new_events = [];

        foreach ($events as $k => $event) {

            $new_event = [];

            if ($event->type == 'USER_CHECKIN') {

                $checkin = Checkin::find($event->event_value);
                $content = $checkin->content;
                $new_event['content'] = $content ? mb_substr($content, 0, 20) : '';

                $new_attachs = [];

                foreach ($checkin->attaches as $k => $attach) {
                    $new_attachs[$k]['id'] = $attach->id;
                    $new_attachs[$k]['name'] = $attach->name;
                    $new_attachs[$k]['url'] = "http://file.growu.me/" . $attach->name . "?imageslim";
                }

                $new_event['attachs'] = $new_attachs;
            }

            $user = User::find($event->user_id);
            $new_user = [];
            $new_user['id'] = $user->id;
            $new_user['nickname'] = $user->nickname;
            $new_user['avatar_url'] = $user->avatar_url;
            $new_user['is_vip'] = $user->is_vip == 1 ? true : false;
            $new_user['verified_type'] = $user->verified_type;
            $new_event['user'] = $new_user;

            $new_events[] = $new_event;
        }

        // 获取具体的EVENT
        $event = Event::find($event_id);

        $new_event = [];

        if ($event->type == 'USER_CHECKIN') {

            $checkin = Checkin::find($event->event_value);

            $content = $checkin->content;

            $new_event['content'] = $content ? mb_substr($content, 0, 100) : '';

            foreach ($checkin->attaches as $k => $attach) {
                $new_attachs[$k]['id'] = $attach->id;
                $new_attachs[$k]['name'] = $attach->name;
                $new_attachs[$k]['url'] = "http://file.growu.me/" . $attach->name . "?imageslim";
            }

            $new_event['attachs'] = $new_attachs;

            $new_event['created_at'] = $event->created_at;
        }

        $user = User::find($event->user_id);
        $new_user = [];
        $new_user['id'] = $user->id;
        $new_user['nickname'] = $user->nickname;
        $new_user['avatar_url'] = $user->avatar_url;
        $new_user['is_vip'] = $user->is_vip == 1 ? true : false;
        $new_user['verified_type'] = $user->verified_type;
        $new_event['user'] = $new_user;

//        var_dump($new_event);

//        var_dump($new_events);

        return view('event', ['events' => $new_events, 'event' => $new_event]);

    }
}