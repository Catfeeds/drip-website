<?php
/**
 * 动态控制器
 */
namespace App\Http\Controllers\Api\V3;

use App\Like;
use App\Models\UserGoal;
use Auth;
use Validator;
use API;
use DB;
use App\Models\UserFollow;
use Illuminate\Support\Collection;

use Carbon\Carbon;


use App\Models\Event;
use App\Checkin;
use App\User;
use App\Goal;
use App\Models\Message as Message;
use App\Models\Comment as Comment;
use App\Models\Energy as Energy;
use App\Libs\MyJpush as MyJpush;

use Qiniu\Auth as QiniuAuth;
use Qiniu\Storage\UploadManager;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Api\V3\Transformers\EventTransformer;
use League\Fractal\Serializer\ArraySerializer;


class EventController extends BaseController {

	/**
	 * 获取单条动态的详情
	 */
	public function getEventDetail($event_id,Request $request)
	{
		$result = [];

        $user_id = $this->auth->user()->id;

		$event = Event::with('user')->where('event_id',$event_id)->first();

		if(!$event) {
			return $this->response->error('未找到动态信息',500);
		}

		$result['id'] = $event->event_id;
		$result['content'] = $event->event_content;
        $result['like_count'] = $event->like_count;
        $result['comment_count'] = $event->comment_count;
        $result['favourite_count'] = 0;
        $result['created_at'] = Carbon::parse($event->created_at)->toDateTimeString();
		$result['updated_at'] = Carbon::parse($event->updated_at)->toDateTimeString();

		$new_checkin = [];

		if($event->type == 'USER_CHECKIN' ) {
			// 获取对应的信息
			$checkin = Checkin::find($event->event_value);

			$result['content'] = $checkin->content;

            $new_checkin['id'] = $checkin->id;
            $new_checkin['total_days'] = $checkin->total_days;

//			// 获取项目
//			$event->checkin->items = DB::table('checkin_item')
//				->join('user_goal_item','user_goal_item.item_id','=','checkin_item.item_id')
//				->where('checkin_id', $event->event_value)
//				->get();

			$new_attachs = [];

			foreach($checkin->attaches as $k=>$attach) {
				$new_attachs[$k]['id'] = $attach->id;
				$new_attachs[$k]['name'] = $attach->name;
//				$new_attachs[$k]['url'] = "http://drip.growu.me/uploads/images/".$attach->path.'/'.$attach->name;
                $new_attachs[$k]['url'] = "http://file.growu.me/".$attach->name."?imageslim";
            }

            $result['attachs'] = $new_attachs;

			$new_goal = [];
            $new_goal['id'] = $event->goal->id;
            $new_goal['name'] = $event->goal->name;

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
			$user['avatar_url'] = $comment->user->avatar_url;

			$new_comments[$k]['user'] = $user;

			if($comment->parent_id>0) {
                $reply = Comment::with('user')->find($comment->parent_id);

                $new_reply = [];

                $new_reply['id'] = $reply->comment_id;
                $new_reply['content'] = $reply->content;

                $new_user = [];
                $new_user['id'] = $reply->user->id;
                $new_user['nickname'] = $reply->user->nickname;

                $new_reply['user'] = $new_user;

                $new_comments[$k]['reply'] = $new_reply;

			} else {
				$new_comments[$k]['reply'] = null;
			}

            $new_comments[$k]['created_at'] = date('Y-m-d H:i:s',$comment->create_time);
		}

		$result['comments'] = $new_comments;

		// 获取点赞
		$likes = Like::with('user')->where('event_id',$event_id)->orderBy('create_time','desc')->take(6)->get();

		$new_likes = [];

		foreach($likes as $k=>$like) {
			$new_likes[$k]['id'] = $like->like_id;

			$user = [];

			$user['id'] = $like->user->id;
			$user['nickname'] = $like->user->nickname;
			$user['avatar_url'] = $like->user->avatar_url;

			$new_likes[$k]['user'] = $user;
		}

		$result['likes'] = $new_likes;

		$user = User::find($event->user_id);

		$new_user = [];
		$new_user['id'] = $user->id;
		$new_user['nickname'] = $user->nickname;
		$new_user['avatar_url'] = $user->avatar_url;

        $is_follow = DB::table('user_follows')
            ->where('user_id',$user_id)
            ->where('follow_user_id',$user->user_id)
            ->first();

        $new_user['is_follow'] = $is_follow?true:false;

		$result['user'] = $new_user;

        $is_like = Event::find($event_id)
            ->likes()
            ->where('user_id', '=', $user_id)
            ->first();

        $result['is_like'] = $is_like ? true : false;

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

		$user_id = $this->auth->user()->id;

		$page = $request->input('page',1);
		$per_page = $request->input('per_page',20);

		$likes = Like::with('user')->where('event_id',$event_id)->orderBy('create_time','desc')->skip(($page-1)*$per_page)->take($per_page)->get();

		$new_likes = [];

		foreach($likes as $k=>$like) {
			$new_likes[$k]['id'] = $like->like_id;

			$user = [];

			$user['id'] = $like->user->id;
			$user['nickname'] = $like->user->nickname;
			$user['signature'] = $like->user->signature;
			$user['avatar_url'] = $like->user->avatar_url;

			$is_follow = DB::table('user_follows')
				->where('user_id',$user_id)
				->where('follow_user_id',$like->user->user_id)
				->first();

			// 判断是否关注该用户
			$user['is_follow'] = $is_follow?true:false;
			$new_likes[$k]['user'] = $user;
		}

		return $new_likes;

	}

	public function getHotEvents(Request $request) {

		$page = $request->input('page',1);
		$per_page = $request->input('per_page',10);

		$events = Event::where('is_hot','=',1)
			->where('is_public','=',1)
			->orderBy('created_at','DESC')
            ->skip(($page-1)*$per_page)
			->take($per_page)
            ->get();

        return $this->response->collection($events, new EventTransformer(),[],function($resource, $fractal){
            $fractal->setSerializer(new ArraySerializer());
        });
	}

    public function getFollowEvents(Request $request) {

        $page = $request->input('page',1);
        $per_page = $request->input('per_page',20);

        $user_id = $this->auth->user()->id;

        DB::connection()->enableQueryLog();

        $followings = UserFollow::where('user_id','=',$user_id)->lists('follow_user_id');

        $events = Event::where('is_public','=','1')
            ->whereIn('user_id',$followings)
            ->orderBy('created_at','desc')
            ->skip(($page-1)*$per_page)
            ->take($per_page)
            ->get();

//                print_r(DB::getQueryLog());

        return $this->response->collection($events, new EventTransformer(),[],function($resource, $fractal){
            $fractal->setSerializer(new ArraySerializer());
        });

    }

    /**
	 * 动态点赞
	 */
	public function like($event_id,Request $request)
	{

        $user = $this->auth->user();

        $user_id = $this->auth->user()->id;

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

				// 查询今天点赞的次数
                $count = DB::table('energy')
                    ->where('user_id',$user_id)
                    ->where('obj_type','like')
                    ->whereRaw('FROM_UNIXTIME(create_time,"%Y-%m-%d") = ?',[date('Y-m-d')])
                    ->count();

                    if($count<5) {
                        $energy = new Energy();
                        $energy->user_id = $user_id;
                        $energy->change = 2;
                        $energy->obj_type = 'like';
                        $energy->obj_id = $event->event_id;
                        $energy->create_time = time();
                        $energy->save();

                        $user->energy_count += 2;
                        $user->save();
                    }


				$this->response->noContent();
			} else {
				$this->response->error('点赞失败，请重试',500);
			}
		}
	}

	public function unLike($event_id,Request $request)
	{
		$user_id = $this->auth->user()->id;

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

			if( $this->auth->user()->id != $event->user_id) {
                //  消息
                $message = new Message();
                $message->from_user = $this->auth->user()->id;
                $message->to_user = $event->user_id;
                $message->type = 2 ;
                $message->msgable_id = $like_id;
                $message->msgable_type = 'App\Like';
                $message->create_time  = time();
                $message->save();

                // TODO 开启推送
                $name = $this->auth->user()->nickname?$this->auth->user()->nickname:'一个神秘的小伙伴';
                $content = $name.' 鼓励了你';
//
                $push = new MyJpush();
                $push->pushToSingleUser($event->user_id,$content);
            }

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

	// 发表评论
	public function comment($event_id,Request $request)
	{
		$messages = [
			'required' => '缺少参数 :attribute',
		];

		$validation = Validator::make(Input::all(), [
			'content'       => 'required',      // 评论内容,
			'reply_id'		=> 	'',		// 动态id
		],$messages);

		if($validation->fails()){
            return $this->response->error(implode(',',$validation->errors()), 500);
		}

		$content = $request->input('content');
		$reply_id = $request->input('reply_id');

		$user_id = $this->auth->user()->id;

		// 判断EVENT是否存在
		$event = Event::find($event_id);

		if(!$event) {
            $this->response->error('动态信息不存在',500);
		}

		$comment = new Comment();
		$comment->event_id = $event_id;
		$comment->content = trim($content);
		$comment->parent_id = $reply_id;
        $comment->reply_id = $reply_id;
        $comment->user_id = $user_id;
		$comment->create_time = time();
		$comment->save();

		// 更新EVENT信息
		$event->comment_count += 1;
		$event->save();

		if($event->user_id != $this->auth->user()->id) {
			//  消息
			$message = new Message();
			$message->from_user = $this->auth->user()->id;
			$message->to_user = $event->user_id;
			$message->type = 3 ;
			$message->msgable_id = $comment->comment_id;
			$message->msgable_type = 'App\Comment';

			$message->create_time  = time();
			$message->save();

			// 推送
			$name = $this->auth->user()->nickname?$this->auth->user()->nickname:'一个神秘的小伙伴';
			$content = $name.' 评论了你';

			$push = new MyJpush();
			$push->pushToSingleUser($event->user_id,$content);
		}


		$comment =  Comment::with('user')->find($comment->comment_id);



		$new_comment = [];

        $new_comment['id'] = $comment->comment_id;
        $new_comment['content'] =  $comment->content;
        $new_comment['like_count'] =$comment->like_count;

        $user = [];

        $user['id'] = $comment->user->user_id;
        $user['nickname'] = $comment->user->nickname;
        $user['avatar_url'] = $comment->user->avatar_url;

        $new_comment['user'] = $user;

        if($comment->parent_id>0) {
            $new_comment['reply'] =  Comment::with('user')->find($comment->parent_id);
        }

        return $new_comment;
	}

	/**
     * 删除动态信息
     */
	public function deleteEvent($event_id,Request $request)
    {
        $event = Event::find($event_id);

        if(!$event) {
            $this->response->error('动态信息不存在',500);
        }

        $user_id = $this->auth->user()->id;

        // 判断是否有权限
        if($event->user_id != $user_id) {
            $this->response->error('没有权限删除',500);
        }


        $event->delete();

        $this->response->noContent();
    }

    /**
     * 更新动态信息
     * @param $event_id
     * @param Request $request
     */
    public function updateEvent($event_id,Request $request)
    {
        $event = Event::find($event_id);

        if(!$event) {
            $this->response->error('动态信息不存在',500);
        }

        $user_id = $this->auth->user()->id;

        // 判断是否有权限
        if($event->user_id != $user_id) {
            $this->response->error('没有权限操作',500);
        }

        $event->update($request->all());

        $this->response->noContent();
    }


    public function share($event_id,Request $request) {

        $event = Event::find($event_id);

        $user = User::find($event->user_id);

        $user_goal = UserGoal::Where('user_id','=',$user->id)
                        ->where('goal_id','=',$event->goal_id)
            ->first();

        $width = 600;
        $height = 1000;

        //创建画布
        $im = imagecreatetruecolor($width, $height);

        //填充画布背景色
        $color = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $color);

        // 分享图
        $checkin = Checkin::find($event->event_value);

        if($checkin->attaches&&count($checkin->attaches)>0) {

            $attach = $checkin->attaches[0];
            $img_path = public_path('uploads/images/'.$attach->path.'/'.$attach->name);

        } else {
            $img_arr = [
                '804X5loicV0.jpg',
                'dch7r8qAzpg.jpg',
                'CWRxBIFRgH4.jpg',
                'dKJXkKCF2D8.jpg',
                'DMkRMy8GEZ8.jpg',
            ];

            $img_name = $img_arr[array_rand($img_arr,1)];
            $img_path = public_path('unsplash/'.$img_name);
        }

        list($g_w,$g_h) = getimagesize($img_path);
        $bgImg = $this->_createImageFromFile($img_path);
        imagecopyresized($im, $bgImg, 0, 0, 0, 0, $width, 400, $g_w, $g_h);

        //字体文件
        $font_file = public_path('fonts/yahei.ttf');
        $font_file2 = public_path('fonts/Oswald-Regular.ttf');

        //设定字体的颜色
        $font_color_1 = ImageColorAllocate ($im, 140, 140, 140);
        $font_color_2 = ImageColorAllocate ($im, 28, 28, 28);
        $font_color_white = ImageColorAllocate ($im, 255, 255, 255);

        // 天数
        $day_text = date('d');
        imagettftext($im, 100,0, 20, 250, $font_color_white ,$font_file2, $day_text);

        // 月份
        $month_text = gmstrftime("%B",time());
        imagettftext($im, 60,0, 20, 350, $font_color_white ,$font_file, $month_text);

        // 用户
        $text2 = $user->nickname;
        imagettftext($im, 20,0, $this->_getFontCenterX($text2,$font_file,20,$width), 480, $font_color_2 ,$font_file, $text2);

        // 动态
        $text1 = '『 '.$user_goal->name.'』';
        imagettftext($im, 22,0, $this->_getFontCenterX($text1,$font_file,22,$width), 550, $font_color_2 ,$font_file, $text1);

        // 数据
        imagettftext($im, 14,0, $this->_getFontCenterX('打卡天数',$font_file,14,$width/2), 650, $font_color_2 ,$font_file, '打卡天数');

        $checkin_days = $user_goal->total_days;
        imagettftext($im, 60,0, $this->_getFontCenterX($checkin_days,$font_file2,60,$width/2), 750, $font_color_2 ,$font_file2, $checkin_days);

        imageline($im,309,650,309,750,$font_color_1);
        imagettftext($im, 14,0, $this->_getFontCenterX('打卡次数',$font_file,14,$width/2)+$width/2, 650, $font_color_2 ,$font_file, '打卡次数');

        $checkin_count = $user_goal->total_count;
        imagettftext($im, 60,0, $this->_getFontCenterX($checkin_count,$font_file2,60,$width/2)+$width/2, 750, $font_color_2 ,$font_file2, $checkin_count);

        // 宣传
        imageline($im,30,850,570,850,$font_color_1);
        imagettftext($im, 16,0, 50, 900, $font_color_2 ,$font_file, '水滴打卡');
        imagettftext($im, 14,0, 50, 950, $font_color_1 ,$font_file, '见证持之以恒的力量');

        //二维码
        list($l_w,$l_h) = getimagesize(public_path('img/qrcode.png'));
        $logoImg = @imagecreatefrompng(public_path('img/qrcode.png'));
        imagecopyresized($im, $logoImg, 450, 880, 0, 0, 100, 100, $l_w, $l_h);


        Header("Content-Type: image/jpg");
        $file_name = time().'.jpg';
//
        imagejpeg($im,public_path('share/'.$file_name));

        //释放空间
        imagedestroy($im);
        imagedestroy($bgImg);
        imagedestroy($logoImg);


        $accessKey = 'Gp_kwMCtSa1jdalGbgv4h8Xk1JMA2vDqPyVIVVu5';
        $secretKey = 'DmjVDP_FxJuFccMRUpomHou-nmNw6QzDDLmyqC0D';
        $auth = new QiniuAuth($accessKey, $secretKey);
        $bucket = 'drip';
        $token = $auth->uploadToken($bucket);

        $uploadMgr = new UploadManager();

        list($ret, $err) = $uploadMgr->putFile($token, 'share/'.$file_name, public_path('share/'.$file_name));
        if ($err !== null) {
            return $this->response->error('图片类型不合法',500);
        }


//
        return $this->response->array(['url'=>'http://file.growu.me/'.$ret['key'].'?imageslim']);
    }

    private function _getFontCenterX($text,$font_file,$font_size,$width)
    {
        $result = imagettfbbox($font_size,0,$font_file, $text);

        $textWidth = $result[2]-$result[0];

        $x= ceil(($width - $textWidth) / 2);

        return $x;
    }

    /**
     * 从图片文件创建Image资源
     * @param $file 图片文件，支持url
     * @return bool|resource    成功返回图片image资源，失败返回false
     */
    private function _createImageFromFile($file){
        if(preg_match('/http(s)?:\/\//',$file)){
            $fileSuffix = $this->_getNetworkImgType($file);
            echo $fileSuffix;
        }else{
            $fileSuffix = pathinfo($file, PATHINFO_EXTENSION);
        }

        if(!$fileSuffix) return false;

        switch ($fileSuffix){
            case 'jpeg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'jpg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'png':
                $theImage = @imagecreatefrompng($file);
                break;
            case 'gif':
                $theImage = @imagecreatefromgif($file);
                break;
            default:
                $theImage = @imagecreatefromstring(file_get_contents($file));
                break;
        }


        return $theImage;
    }

    /**
     * 获取网络图片类型
     * @param $url  网络图片url,支持不带后缀名url
     * @return bool
     */
    private function _getNetworkImgType($url){
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url); //设置需要获取的URL
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //支持https
        curl_exec($ch);//执行curl会话
        $http_code = curl_getinfo($ch);//获取curl连接资源句柄信息
        curl_close($ch);//关闭资源连接
        var_dump($http_code);

        if ($http_code['http_code'] == 200) {
            $theImgType = explode('/',$http_code['content_type']);

            if($theImgType[0] == 'image'){
                return $theImgType[1];
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}