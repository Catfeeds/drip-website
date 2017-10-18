<?php
/**
 * 动态控制器
 */
namespace App\Http\Controllers\Api\V2;

use App\Like;
use Auth;
use Validator;
use API;
use DB;

use Carbon\Carbon;


use App\Event;
use App\Checkin;
use App\User;
use App\Goal;
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
	public function getEventDetail($event_id,Request $request)
	{
//		$messages = [
//			'required' => '缺少参数 :attribute',
//		];
//
//		$validation = Validator::make(Input::all(), [
//			'event_id'		=> 	'required',		// 动态id
//		],$messages);
//
//		if($validation->fails()){
//			return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
//		}

//		$event_id = Input::get('event_id');

		$result = [];

        $user_id = $this->auth->user()->user_id;

		$event = Event::with('user')->where('event_id',$event_id)->first();

		if(!$event) {
			return $this->response->error('未找到动态信息',500);
		}

		$result['id'] = $event->event_id;
		$result['content'] = $event->event_content;
		$result['created_at'] = Carbon::parse($event->created_at)->toDateTimeString();
		$result['updated_at'] = Carbon::parse($event->updated_at)->toDateTimeString();

		$new_checkin = [];

		if($event->type == 'USER_CHECKIN' ) {
			// 获取对应的信息
			$checkin = Checkin::find($event->event_value);

			$result['content'] = $checkin->checkin_content;

            $new_checkin['id'] = $checkin->checkin_id;
            $new_checkin['total_days'] = $checkin->total_days;

//			// 获取项目
//			$event->checkin->items = DB::table('checkin_item')
//				->join('user_goal_item','user_goal_item.item_id','=','checkin_item.item_id')
//				->where('checkin_id', $event->event_value)
//				->get();

			// 获取附件
			$attachs = DB::table('attachs')
				->where('attachable_id', $event->event_value)
				->where('attachable_type','checkin')
				->get();

			$new_attachs = [];

			foreach($attachs as $k=>$attach) {
				$new_attachs[$k]['id'] = $attach->attach_id;
				$new_attachs[$k]['name'] = $attach->attach_name;
				$new_attachs[$k]['url'] = "http://www.keepdays.com/uploads/images/".$attach->attach_path.'/'.$attach->attach_name;
			}

            $result['attachs'] = $new_attachs;

			$new_goal = [];
            $new_goal['id'] = $event->goal->goal_id;
            $new_goal['name'] = $event->goal->goal_name;

            $result['goal'] =  $new_goal;
		}

        $result['checkin'] =  $new_checkin;

		// 获取评论
		$comments = Comment::with('user')
			->where('event_id',$event_id)
			->orderBy('create_time','desc')
			->take(10)
			->get();

		$new_comments = [];

		foreach($comments as $k=>$comment) {

			$new_comments[$k]['id'] = $comment['comment_id'];
			$new_comments[$k]['content'] = $comment['content'];
			$new_comments[$k]['like_count'] = $comment['like_count'];

			$user = [];

			$user['id'] = $comment->user->user_id;
			$user['nickname'] = $comment->user->nickname;
			$user['avatar_url'] = $comment->user->user_avatar;

			$new_comments[$k]['user'] = $user;

			if($comment->parent_id>0) {
				$parent = Comment::with('user')->find($comment->parent_id);

				$new_comments[$k]['parent'] = $parent;

			} else {
				$new_comments[$k]['parent'] = null;
			}
		}

		$result['comments'] = $new_comments;

		// 获取点赞
		$likes = Like::with('user')->where('event_id',$event_id)->orderBy('create_time','desc')->take(6)->get();

		$new_likes = [];

		foreach($likes as $k=>$like) {
			$new_likes[$k]['id'] = $like->like_id;

			$user = [];

			$user['id'] = $like->user->user_id;
			$user['nickname'] = $like->user->nickname;
			$user['avatar_url'] = $like->user->user_avatar;

			$new_likes[$k]['user'] = $user;
		}

		$result['likes'] = $new_likes;

		$user = DB::table('users')
			->where('user_id', $event->user_id)
			->first();

		$new_user = [];
		$new_user['id'] = $user->user_id;
		$new_user['nickname'] = $user->nickname;
		$new_user['avatar_url'] = $user->user_avatar;

        $is_follow = DB::table('user_follow')
            ->where('user_id',$user_id)
            ->where('follow_user_id',$user->user_id)
            ->first();

        $new_user['is_follow'] = $is_follow?true:false;

		$result['user'] = $new_user;

		return $result;
	}


	public function getEventLikes($event_id,Request $request) {

		$messages = [
			'required' => '缺少参数 :attribute',
        ];

    	$validation = Validator::make(Input::all(), [
			'page'        =>  '',
			'per_page'        =>  ''
		],$messages);

		$user_id = $this->auth->user()->user_id;

		$page = $request->input('page',1);
		$per_page = $request->input('per_page',20);

		$likes = Like::with('user')->where('event_id',$event_id)->orderBy('create_time','desc')->skip(($page-1)*$per_page)->take($per_page)->get();

		$new_likes = [];

		foreach($likes as $k=>$like) {
			$new_likes[$k]['id'] = $like->like_id;

			$user = [];

			$user['id'] = $like->user->user_id;
			$user['nickname'] = $like->user->nickname;
			$user['signature'] = $like->user->signature;
			$user['avatar_url'] = $like->user->user_avatar;

			$is_follow = DB::table('user_follow')
				->where('user_id',$user_id)
				->where('follow_user_id',$like->user->user_id)
				->first();


			// 判断是否关注该用户
			$user['is_follow'] = $is_follow?true:false;


			$new_likes[$k]['user'] = $user;
		}

		return $new_likes;

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


	public function getHotEvents(Request $request) {

		$page = $request->input('page',1);
		$per_page = $request->input('per_page',20);

		$events = Event::where('is_hot','=',1)
			->where('is_public','=',1)
			->orderBy('create_time','DESC')->skip($page*$per_page)
			->take($per_page)->get();

		$new_events = [];

		foreach ($events as $key => $event) {
//			$new_events[$key] = $event;

			$new_events[$key]['id'] = $event->event_id;
			$new_events[$key]['content'] = $event->event_content;
			$new_events[$key]['like_count'] = $event->like_count;


			$new_checkin = [];

			if($event->type == 'USER_CHECKIN') {

				$checkin = DB::table('checkin')
					->where('checkin_id', $event->event_value)
					->first();

				$content = $checkin->checkin_content;

				$new_events[$key]['content'] = $content?mb_substr($content,0,20):'';

                $new_checkin['id'] = $checkin->checkin_id;
                $new_checkin['total_days'] = $checkin->total_days;

//				$new_events[$key]->checkin->items = DB::table('checkin_item')
//					->join('user_goal_item','user_goal_item.item_id','=','checkin_item.item_id')
//					->where('checkin_id', $event->event_value)
//					->get();
				$attachs = DB::table('attachs')
					->where('attachable_id', $event->event_value)
					->where('attachable_type','checkin')
					->get();

				$new_attachs = [];

				foreach($attachs as $k=>$attach) {
					$new_attachs[$k]['id'] = $attach->attach_id;
					$new_attachs[$k]['name'] = $attach->attach_name;
					$new_attachs[$k]['url'] = "http://www.keepdays.com/uploads/images/".$attach->attach_path.'/'.$attach->attach_name;
				}


				$new_events[$key]['attachs'] = $new_attachs;

			}

            $new_events[$key]['checkin'] = $new_checkin;

			$user = DB::table('users')
				->where('user_id', $event->user_id)
				->first();

			$owner = [];
			$owner['id'] = $user->user_id;
			$owner['nickname'] = $user->nickname;
			$owner['avatar_url'] = $user->user_avatar;

			$new_events[$key]['owner'] = $owner;

            $goal = [];
            $goal['id'] = $event->goal->goal_id;
            $goal['name'] = $event->goal->goal_name;

            $new_events[$key]['goal'] = $goal;
            $new_events[$key]['created_at'] = Carbon::parse($event->created_at)->toDateTimeString();
            $new_events[$key]['updated_at'] = Carbon::parse($event->updated_at)->toDateTimeString();

//
//			$new_events[$key]->goal =DB::table('goal')
//				->where('goal_id', $event->goal_id)
//				->first();

		}

		return $new_events;

	}

    public function getFollowEvents(Request $request) {

        $page = $request->input('page',1);
        $per_page = $request->input('per_page',20);

        $user_id = $this->auth->user()->user_id;

        DB::connection()->enableQueryLog();

        $events =DB::table('events')
            ->join('user_follow','user_follow.follow_user_id','=','events.user_id')
            ->select('user_follow.follow_user_id','events.*')
            ->where('user_follow.user_id','=',$user_id)
            ->where('events.is_public','=',1)
            ->orderBy('events.create_time','desc')
            ->skip($page*$per_page)
            ->take($per_page)
            ->get();

//        print_r(DB::getQueryLog());

        $new_events = [];

        foreach ($events as $key => $event) {
//			$new_events[$key] = $event;

            $new_events[$key]['id'] = $event->event_id;
            $new_events[$key]['content'] = $event->content;
            $new_events[$key]['like_count'] = $event->like_count;

            $new_checkin = [];
            $new_goal = [];

            if($event->type == 'USER_CHECKIN') {

                $checkin = DB::table('checkin')
                    ->where('checkin_id', $event->event_value)
                    ->first();

                $content = $checkin->checkin_content;

                $new_events[$key]['content'] = $content;

//				$new_events[$key]->checkin->items = DB::table('checkin_item')
//					->join('user_goal_item','user_goal_item.item_id','=','checkin_item.item_id')
//					->where('checkin_id', $event->event_value)
//					->get();

                $new_checkin['id'] = $checkin->checkin_id;
                $new_checkin['total_days'] = $checkin->total_days;

                $attachs = DB::table('attachs')
                    ->where('attachable_id', $event->event_value)
                    ->where('attachable_type','checkin')
                    ->get();

                $new_attachs = [];

                foreach($attachs as $k=>$attach) {
                    $new_attachs[$k]['id'] = $attach->attach_id;
                    $new_attachs[$k]['name'] = $attach->attach_name;
                    $new_attachs[$k]['url'] = "http://www.keepdays.com/uploads/images/".$attach->attach_path.'/'.$attach->attach_name;
                }

                $new_events[$key]['attachs'] = $new_attachs;

            }

            $new_events[$key]['checkin'] = $new_checkin;

            $user = DB::table('users')
                ->where('user_id', $event->user_id)
                ->first();

            $owner = [];
            $owner['id'] = $user->user_id;
            $owner['nickname'] = $user->nickname;
            $owner['avatar_url'] = $user->user_avatar;

            $new_events[$key]['owner'] = $owner;

            $goal = Goal::find($event->goal_id);

            if($goal) {
                $new_goal['id'] = $goal->goal_id;
                $new_goal['name'] =$goal->goal_name;
            }

            $new_events[$key]['goal'] = $new_goal;
            $new_events[$key]['created_at'] = Carbon::parse($event->created_at)->toDateTimeString();
            $new_events[$key]['updated_at'] = Carbon::parse($event->updated_at)->toDateTimeString();
//
            $is_like = Event::find($event->event_id)
                ->likes()
                ->where('user_id', '=', $user_id)
                ->first();

            $new_events[$key]['is_like'] = $is_like ? true : false;

//			$new_events[$key]->goal =DB::table('goal')
//				->where('goal_id', $event->goal_id)
//				->first();

        }

        return $new_events;

    }

    /**
	 * 动态点赞
	 */
	public function like($event_id,Request $request)
	{
	    $user_id = $this->auth->user()->user_id;

		// 查询Event是否存在
	    $event = Event::find($event_id);

		if(!$event) {
			$this->response->error('动态信息不存在',500);
		}

		// 查询用户是否点赞
		$is_like =  Event::find($event_id)
						->likes()
						->where('user_id','=',$user_id)
						->first();

		if($is_like) {
			$this->response->error('请勿重复点赞',500);
		} else {
			// 新增点赞记录
			$like = new Like();
			$like->user_id = $user_id;
			$like->event_id = $event_id;
			$like->create_time = time();

			if($like->save()) {
				$this->_update_like_count($event,$like->like_id,'save');
				$this->response->noContent();
			} else {
				$this->response->error('点赞失败，请重试',500);
			}
		}
	}

	public function unLike($event_id,Request $request)
	{
		$user_id = $this->auth->user()->user_id;

		// 查询Event是否存在
		$event = Event::find($event_id);

		if(!$event) {
			$this->response->error('动态信息不存在',500);
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
				$this->response->noContent();
			} else {
				$this->response->error('取消失败，请重试',500);
			}
		} else {
			$this->response->error('已取消',500);
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

			// TODO 开启推送
//			$name = $this->auth->user()->nickname?$this->auth->user()->nickname:'keeper'.$this->auth->user()->user_id;
//			$content = $name.' 鼓励了你';
//
//			$push = new MyJpush();
//			$push->pushToSingleUser($event->user_id,$content);

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