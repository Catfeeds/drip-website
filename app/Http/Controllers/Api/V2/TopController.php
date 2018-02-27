<?php
/**
 * 事件控制器
 */
namespace App\Http\Controllers\Api\V2;

use Auth;
use Validator;
use API;
use DB;	

use App\User;
use App\Checkin;


use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class TopController extends BaseController {

	public function users()
	{
		$user_id  = $this->auth->user()->id;

		$users = User::join("checkins",'checkins.user_id','=','users.id')
				->select('users.*',DB::raw('count(1) as count'))
				->where(DB::raw('YEAR(checkin_day)'),date('Y'))
				->where(DB::raw('MONTH(checkin_day)'),date('m'))
				->groupBy('users.id')
				->orderBy('count','DESC')
				->take(10)
				->get();

		// 查询当前用户本月打卡的次数
		$count = DB::table('checkins')
					->where('user_id',$user_id)
					->where(DB::raw('YEAR(checkin_day)'),'=',date('Y'))
					->where(DB::raw('MONTH(checkin_day)'),'=',date('m'))
					->groupBy('user_id')
					->count();

		$rank_users = DB::table('checkins')
					->select(DB::raw('count(*) as count'))
					->where(DB::raw('YEAR(checkin_day)'),'=',date('Y'))
					->where(DB::raw('MONTH(checkin_day)'),'=',date('m'))
					->groupBy('user_id')
					->having('count', '>', $count)
					->get();

		$rank = count($rank_users)+1;

		// User::find(8)->checkins;

		$month = date('m');

		return compact('month','users','count','rank');
	}

    public function week(){
        $user_id  = $this->auth->user()->id;

        $pre_users = User::join("checkins",'checkins.user_id','=','users.id')
            ->select('users.*',DB::raw('count(1) as count'))
            ->where(DB::raw('YEAR(checkin_day)'),date('Y'))
            ->where(DB::raw('WEEK(checkin_day,1)'),date('w'))
            ->groupBy('users.id')
            ->orderBy('count','DESC')
            ->take(10)
            ->get();

        $users = [];

        foreach($pre_users as $k=>$user) {
            $users[$k]['id'] = $user->user_id;
            $users[$k]['nickname'] = $user->nickname;
            $users[$k]['avatar_url'] = str_replace('https://www.keepdays.com',"http://www.keepdays.com",$user->avatar_url);
            $users[$k]['checkin_count'] = $user->count;
        }

        // 查询当前用户本月打卡的次数
        $count = DB::table('checkins')
            ->where('user_id',$user_id)
            ->where(DB::raw('YEAR(checkin_day)'),'=',date('Y'))
            ->where(DB::raw('WEEK(checkin_day,1)'),date('w'))
            ->groupBy('user_id')
            ->count();

        $rank_users = DB::table('checkins')
            ->select(DB::raw('count(*) as count'))
            ->where(DB::raw('YEAR(checkin_day)'),'=',date('Y'))
            ->where(DB::raw('WEEK(checkin_day,1)'),date('w'))
            ->groupBy('user_id')
            ->having('count', '>', $count)
            ->get();

        $rank = count($rank_users)+1;

        $week = date('w');

        return compact('week','users','count','rank');
    }

    public function month() {
        $user_id  = $this->auth->user()->id;

        $pre_users = User::join("checkins",'checkins.user_id','=','users.id')
            ->select('users.*',DB::raw('count(1) as count'))
            ->where(DB::raw('YEAR(checkin_day)'),date('Y'))
            ->where(DB::raw('MONTH(checkin_day)'),date('m'))
            ->groupBy('users.id')
            ->orderBy('count','DESC')
            ->take(10)
            ->get();

        $users = [];

        foreach($pre_users as $k=>$user) {
            $users[$k]['id'] = $user->user_id;
            $users[$k]['nickname'] = $user->nickname;
            $users[$k]['avatar_url'] = str_replace('https://www.keepdays.com',"http://www.keepdays.com",$user->avatar_url);
            $users[$k]['checkin_count'] = $user->count;
        }

        // 查询当前用户本月打卡的次数
        $count = DB::table('checkins')
            ->where('user_id',$user_id)
            ->where(DB::raw('YEAR(checkin_day)'),'=',date('Y'))
            ->where(DB::raw('MONTH(checkin_day)'),'=',date('m'))
            ->groupBy('user_id')
            ->count();

        $rank_users = DB::table('checkins')
            ->select(DB::raw('count(*) as count'))
            ->where(DB::raw('YEAR(checkin_day)'),'=',date('Y'))
            ->where(DB::raw('MONTH(checkin_day)'),'=',date('m'))
            ->groupBy('user_id')
            ->having('count', '>', $count)
            ->get();

        $rank = count($rank_users)+1;

        // User::find(8)->checkins;

        $month = date('m');

        return compact('month','users','count','rank');
    }

    public function year(){
        $user_id  = $this->auth->user()->id;

        $pre_users = User::join("checkins",'checkins.user_id','=','users.id')
            ->select('users.*',DB::raw('count(1) as count'))
            ->where(DB::raw('YEAR(checkin_day)'),date('Y'))
            ->groupBy('users.id')
            ->orderBy('count','DESC')
            ->take(10)
            ->get();

        $users = [];

        foreach($pre_users as $k=>$user) {
            $users[$k]['id'] = $user->user_id;
            $users[$k]['nickname'] = $user->nickname;
            $users[$k]['avatar_url'] = str_replace('https://www.keepdays.com',"http://www.keepdays.com",$user->avatar_url);
            $users[$k]['checkin_count'] = $user->count;
        }

        // 查询当前用户本月打卡的次数
        $count = DB::table('checkins')
            ->where('user_id',$user_id)
            ->where(DB::raw('YEAR(checkin_day)'),'=',date('Y'))
            ->groupBy('user_id')
            ->count();

        $rank_users = DB::table('checkins')
            ->select(DB::raw('count(*) as count'))
            ->where(DB::raw('YEAR(checkin_day)'),'=',date('Y'))
            ->groupBy('user_id')
            ->having('count', '>', $count)
            ->get();

        $rank = count($rank_users)+1;

        // User::find(8)->checkins;

        $year = date('Y');

        return compact('year','users','count','rank');
    }


	public function all(){
        $user_id  = $this->auth->user()->id;

        $pre_users = User::join("checkins",'checkins.user_id','=','users.id')
            ->select('users.*',DB::raw('count(1) as count'))
            ->groupBy('users.id')
            ->orderBy('count','DESC')
            ->take(10)
            ->get();

        $users = [];

        foreach($pre_users as $k=>$user) {
            $users[$k]['id'] = $user->user_id;
            $users[$k]['nickname'] = $user->nickname;
            $users[$k]['avatar_url'] = str_replace('https://www.keepdays.com',"http://www.keepdays.com",$user->avatar_url);
            $users[$k]['checkin_count'] = $user->count;
        }

        // 查询当前用户本月打卡的次数
        $count = DB::table('checkins')
            ->where('user_id',$user_id)
            ->groupBy('user_id')
            ->count();

        $rank_users = DB::table('checkins')
            ->select(DB::raw('count(*) as count'))
            ->groupBy('user_id')
            ->having('count', '>', $count)
            ->get();

        $rank = count($rank_users)+1;

        // User::find(8)->checkins;

        $month = date('m');

        return compact('users','count','rank');
    }


}