<?php
/**
 * 消息控制器
 */
namespace App\Http\Controllers\Api\V1;

use Auth;
use Validator;
use API;
use DB;

use App\Event;
use App\Checkin;
use App\Models\Message as Message;



use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class MessageController extends BaseController {


	public function like()
	{
    	$user_id  = $this->auth->user()->user_id;

	    $messages = DB::table('messages')
            ->join('users', 'users.user_id', '=', 'messages.from_user')
			->join('likes', 'likes.like_id', '=', 'messages.msgable_id')
			->where('type',2)
			->where('messages.msgable_type','=','App\Like')
			->where('to_user',$user_id)
			->orderBy('messages.status')
			->orderBy('messages.create_time','desc')
			->take(20)
            ->get();

		// 修改所有未读的状态为已读
		DB::table('messages')
			->where('to_user',$user_id)
			->where('type',2)
			->where('status',0)
			->update([
				'status'=>1
			]);

        return API::response()->array(['status' => true, 'message' =>"",'data'=>$messages]);;
	}

	public function fan()
	{
		$user_id  = $this->auth->user()->user_id;

		$messages = DB::table('messages')
			->join('users', 'users.user_id', '=', 'messages.from_user')
			->where('type',4)
			->where('to_user',$user_id)
			->orderBy('messages.status')
			->orderBy('messages.create_time','desc')
			->take(20)
			->get();

		// 修改所有未读的状态为已读
		DB::table('messages')
			->where('to_user',$user_id)
			->where('type',4)
			->where('status',0)
			->update([
				'status'=>1
			]);

		return API::response()->array(['status' => true, 'message' =>"",'data'=>$messages]);;
	}

	public function comment()
	{
		$user_id  = $this->auth->user()->user_id;

		$messages = DB::table('messages')
			->join('users', 'users.user_id', '=', 'messages.from_user')
			->join('event_comment', 'event_comment.comment_id', '=', 'messages.msgable_id')
			->where('messages.type',3)
			->where('to_user',$user_id)
			->orderBy('messages.status')
			->orderBy('messages.create_time','desc')
			->take(20)
			->get();

		// 修改所有未读的状态为已读
		DB::table('messages')
			->where('to_user',$user_id)
			->where('type',3)
			->where('status',0)
			->update([
				'status'=>1
			]);

		return API::response()->array(['status' => true, 'message' =>"",'data'=>$messages]);;
	}

	public function notice()
	{
		$user_id  = $this->auth->user()->user_id;

		$messages = DB::table('messages')
			->where('messages.type',6)
			->where('to_user',$user_id)
			->orderBy('messages.status')
			->orderBy('messages.create_time','desc')
			->take(20)
			->get();

		// 修改所有未读的状态为已读
		DB::table('messages')
			->where('to_user',$user_id)
			->where('type',6)
			->where('status',0)
			->update([
				'status'=>1
			]);

		return API::response()->array(['status' => true, 'message' =>"",'data'=>$messages]);;
	}
}