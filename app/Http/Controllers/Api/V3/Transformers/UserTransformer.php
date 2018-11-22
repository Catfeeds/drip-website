<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/1/9
 */

namespace  App\Http\Controllers\Api\V3\Transformers;

use Carbon\Carbon;
use League\Fractal\TransformerAbstract;
use App\User;
use App\Models\Event;

use DB;

class UserTransformer extends TransformerAbstract
{
    private $params = [];

    function __construct($params = [])
    {
        $this->params = $params;
    }

    public function transform(User $user) {

        $new_user = [];
        $new_user['id'] = $user->id;
        $new_user['email'] = $user->email;
        $new_user['phone'] = $user->phone;
        $new_user['is_vip'] = $user->is_vip == 1 ? true : false;
        $new_user['vip_type'] = $user->vip_type;
        $new_user['vip_begin_date'] = $user->vip_begin_date;
        $new_user['vip_end_date'] = $user->vip_end_date;
        $new_user['created_at'] = Carbon::parse($user->created_at)->toDateTimeString();
        $new_user['nickname'] = $user->nickname;
        $new_user['signature'] = $user->signature;
        $new_user['avatar_url'] = $user->avatar_url;
        $new_user['sex'] = $user->sex;
        $new_user['verified_type'] = $user->verified_type;
        $new_user['verified_reason'] = $user->verified_reason;
        $new_user['follow_count'] = $user->follow_count;
        $new_user['fans_count'] = $user->fans_count;
        $new_user['coin'] = $user->energy_count;
        $event_count = Event::where('user_id', $user->id)->count();
        $new_user['event_count'] = $event_count;

        $wechat = DB::table('users_bind')
            ->where('provider', 'wechat')
            ->where('user_id', $user->id)
            ->first();

        $new_wechat = [];
        $new_wechat['nickname'] = $wechat ? $wechat->nickname : null;
        $new_user['wechat'] = $new_wechat;

        $qq = DB::table('users_bind')
            ->where('provider', 'qq')
            ->where('user_id', $user->id)
            ->first();

        $new_qq = [];
        $new_qq['nickname'] = $qq ? $qq->nickname : null;
        $new_user['qq'] = $new_qq;

        $weibo = DB::table('users_bind')
            ->where('provider', 'weibo')
            ->where('user_id', $user->id)
            ->first();

        $new_weibo = [];
        $new_weibo['nickname'] = $weibo ? $weibo->nickname : null;
        $new_user['weibo'] = $new_weibo;

        if($this->params) {
            $this->params['user'] = $new_user;
            return $this->params;
        }

        return $new_user;
    }
}
