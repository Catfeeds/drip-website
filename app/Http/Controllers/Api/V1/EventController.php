<?php
/**
 * 动态控制器
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
use App\Models\Comment as Comment;
use App\Models\Energy as Energy;
use App\Libs\MyJpush as MyJpush;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class EventController extends BaseController {

	/**
	 * 获取单条动态的详情
	 */
	public function info()
	{
		$messages = [
			'required' => '缺少参数 :attribute',
		];

		$validation = Validator::make(Input::all(), [
			'event_id'		=> 	'required',		// 动态id
		],$messages);

		if($validation->fails()){
			return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
		}

		$event_id = Input::get('event_id');

		$event = Event::with('user')->where('event_id',$event_id)->first();

		if(!$event) {
			return API::response()->array(['status' => false, 'message' => '未找到动态信息'])->statusCode(200);
		}

		if($event->type == 'USER_CHECKIN' ) {
			// 获取对应的信息
			$event->checkin = Checkin::find($event->event_value);
			// 获取项目
			$event->checkin->items = DB::table('checkin_item')
				->join('user_goal_item','user_goal_item.item_id','=','checkin_item.item_id')
				->where('checkin_id', $event->event_value)
				->get();

			// 获取附件
			$event->checkin->attaches = DB::table('attachs')
				->where('attachable_id', $event->event_value)
				->where('attachable_type','checkin')
				->get();

			$event->goal =  $event->goal;
		}

		// 获取评论
		$comments = Comment::with('user')
			->where('event_id',$event_id)
			->orderBy('create_time','desc')
			->take(10)
			->get();

		foreach($comments as $comment) {
			if($comment->parent_id>0) {
				$comment->parent = Comment::with('user')->find($comment->parent_id);
			} else {
				$comment->parent = null;
			}
		}

		$event->comments = $comments;

		// 获取点赞
		$event->likes = Like::with('user')->where('event_id',$event_id)->orderBy('create_time','desc')->take(6)->get();

		return API::response()->array(['status' => true, 'message' =>'获取成功','data'=>$event])->statusCode(200);
	}

	/**
	 * 获取动态
	 */
	public function all()
	{
		$offset = Input::get('offset')?Input::get('offset'):0;
		$limit = Input::get('limit')?Input::get('limit'):20;
		$type = Input::get('type')?Input::get('type'):'new';

		$user_id = Input::get('user_id');

		if($user_id) {
			if($user_id == $this->auth->user()->user_id) {
				$events = Event::where('user_id',$user_id)->orderBy('create_time','DESC')->skip($offset)
					->take($limit)->get();
			} else {
				$events = Event::where('user_id',$user_id)
					->where('is_public','=',1)
					->orderBy('create_time','DESC')->skip($offset)
					->take($limit)->get();
			}

		} else {
			$user_id = $this->auth->user()->user_id;
			if($type == 'hot') {
				$events = Event::where('is_hot','=',1)
					->where('is_public','=',1)
					->orderBy('create_time','DESC')->skip($offset)
					->take($limit)->get();
			} else if($type == 'follow') {
				$events =DB::table('events')
					->join('user_follow','user_follow.follow_user_id','=','events.user_id')
					->select('user_follow.follow_user_id','events.*')
					->where('user_follow.user_id','=',$user_id)
					->where('events.is_public','=',1)
					->orderBy('events.create_time','desc')
					->skip($offset)
					->take($limit)
					->get();
			} else {
				$events =
					Event::where('is_public','=',1)
					->orderBy('create_time','DESC')->skip($offset)
					->take($limit)->get();
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

		return API::response()->array(['status' => true, 'message' =>'','data'=>$new_events])->statusCode(200);
	}

	/**
	 * 动态点赞
	 */
	public function like()
	{
		$messages = [
			'required' => '缺少参数 :attribute',
		];

		$validation = Validator::make(Input::all(), [
      		'event_id'		=> 	'required',		// 动态id
    	],$messages);

    	if($validation->fails()){
			return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
	    }

	    $event_id =  Input::get('event_id');

	    $user_id = $this->auth->user()->user_id;

		// 查询Event是否存在
	    $event = Event::find($event_id);

		if(!$event) {
			return API::response()->array(['status' => false, 'message' => '动态信息不存在'])->statusCode(200);
		}

		// 查询是否为自己点赞
		if($event->user_id == $user_id) {
			return API::response()->array(['status' => false, 'message' => '不能给自己点赞'])->statusCode(200);
		}

		// 查询用户是否点赞
		$is_like =  Event::find($event_id)
						->likes()
						->where('user_id','=',$user_id)
						->first();

		if($is_like) {
			// 删除点赞记录
			$like = Like::find($is_like->like_id);
			if($like->delete()) {
				$this->_update_like_count($event,$like->id,'delete');
				return API::response()->array(['status' => true, 'message' => '取消成功','data'=>'cancle'])->statusCode(200);
			} else {
				return API::response()->array(['status' => false, 'message' => '取消失败'])->statusCode(200);
			}
		} else {
			// 新增点赞记录
			$like = new Like();
			$like->user_id = $user_id;
			$like->event_id = $event_id;
			$like->create_time = time();

			if($like->save()) {
				$this->_update_like_count($event,$like->like_id,'save');
				return API::response()->array(['status' => true, 'message' => '点赞成功','data'=>'post'])->statusCode(200);
			} else {
				return API::response()->array(['status' => false, 'message' => '点赞失败'])->statusCode(200);
			}
		}

	}

	// 更新点赞信息
	private function _update_like_count($event,$like_id=0,$action='save') {

		$user = User::find($event->user_id);

		if($action == 'save') {
			$event->like_count += 1;
			$user->like_count += 1;
			$user->energy_count += 1;

			$energy = new Energy();
			$energy->user_id = $event->user_id;
			$energy->change = 1;
			$energy->obj_type = 'like';
			$energy->obj_id = $like_id;
			$energy->create_time = time();
			$energy->save();

			$event->save();
			$user->save();



			//  消息
			$message = new Message();
			$message->from_user = $this->auth->user()->user_id;
			$message->to_user = $event->user_id;
			$message->type = 2 ;
			$message->msgable_id = $like_id;
			$message->msgable_type = 'App\Like';

			$message->create_time  = time();
			$message->save();

			// 推送
			$name = $this->auth->user()->nickname?$this->auth->user()->nickname:'keeper'.$this->auth->user()->user_id;
			$content = $name.' 鼓励了你';

			$push = new MyJpush();
			$push->pushToSingleUser($event->user_id,$content);

		}

		if($action == 'delete') {
			if($event->like_count>0) $event->like_count -= 1 ;
			if($user->like_count>0) $user->like_count -= 1 ;
			if($user->energy_count>0) {
				$user->energy_count -= 1;

				$energy = new Energy();
				$energy->user_id = $event->user_id;
				$energy->change = -1;
				$energy->obj_type = 'like';
				$energy->obj_id = $like_id;
				$energy->create_time = time();
				$energy->save();
			}

			$event->save();
			$user->save();



		}
	}

	/**
	 * 获取点赞列表
	 */
	public function likes()
	{
		$messages = [
			'required' => '缺少参数 :attribute',
		];

		$validation = Validator::make(Input::all(), [
			'event_id'		=> 	'required',		// 动态id
		],$messages);

		if($validation->fails()){
			return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
		}

		$event_id = Input::get('event_id');

		$users = Like::with('user')->where('event_id',$event_id)->take(20)->get();

		return API::response()->array(['status' => true, 'message' =>'','data'=>$users])->statusCode(200);

	}

	// 发表评论
	public function comment()
	{
		$messages = [
			'required' => '缺少参数 :attribute',
		];

		$validation = Validator::make(Input::all(), [
			'event_id'		=> 	'required',		// 动态id
			'content'       => 'required',      // 评论内容,
			'parent_id'		=> 	'required',		// 动态id
		],$messages);

		if($validation->fails()){
			return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
		}

		$event_id = Input::get('event_id');
		$content = Input::get('content');
		$parent_id = Input::get('parent_id');

		$user_id = $this->auth->user()->user_id;

		// 判断EVENT是否存在
		$event = Event::find($event_id);

		if(!$event) {
			return API::response()->array(['status' => false, 'message' =>'动态不存在'])->statusCode(200);
		}

		$comment = new Comment();
		$comment->event_id = $event_id;
		$comment->content = trim($content);
		$comment->parent_id = $parent_id;
		$comment->user_id = $user_id;
		$comment->create_time = time();
		$comment->save();

		// 更新EVENT信息
		$event->comment_count += 1;
		$event->save();

		if($event->user_id != $this->auth->user()->user_id) {
			//  消息
			$message = new Message();
			$message->from_user = $this->auth->user()->user_id;
			$message->to_user = $event->user_id;
			$message->type = 3 ;
			$message->msgable_id = $comment->comment_id;
			$message->msgable_type = 'App\Comment';

			$message->create_time  = time();
			$message->save();

			// 推送
			$name = $this->auth->user()->nickname?$this->auth->user()->nickname:'keeper'.$this->auth->user()->user_id;
			$content = $name.' 评论了你';

			$push = new MyJpush();
			$push->pushToSingleUser($event->user_id,$content);
		}


		$comment =  Comment::with('user')->find($comment->comment_id);

		if($comment->parent_id>0) {
			$comment->parent =  Comment::with('user')->find($comment->parent_id);
		}

		return API::response()->array(['status' => true, 'message' =>'评论成功','data'=>$comment])->statusCode(200);

	}
}