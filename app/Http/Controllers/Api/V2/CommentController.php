<?php
/**
 * User: Jason.z
 * Date: 2016/12/8
 * Time: 下午7:28
 */

namespace App\Http\Controllers\Api\V2;

use App\Models\Comment;
use Auth;
use Validator;
use API;
use DB;

use App\Models\Event;
use App\Checkin;
use App\Models\Message as Message;


use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class CommentController extends BaseController
{


    public function like($comment_id)
    {

        $user_id = $this->auth->user()->id;

        $comment = Comment::find($comment_id);

        if (!$comment) {
            return $this->response->error("评论不存在");
        }

        DB::table('comment_like')->insert([
            ['comment_id' => $comment_id, 'user_id' => $user_id, 'create_time' => time()]
        ]);

        $comment->increment('like_count', 1);

        return $this->response->noContent();

    }

    public function unLike($comment_id)
    {
//        $messages = [
//            'required' => '缺少参数 :attribute',
//        ];
//
//        $validation = Validator::make(Input::all(), [
//            'comment_id'		=> 	'required',		// 评论id
//        ],$messages);
//
//        if($validation->fails()){
//            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
//        }

        $user_id = $this->auth->user()->id;

        $comment = Comment::find($comment_id);

        if (!$comment) {
            return $this->response->error('评论不存在');
        }

        DB::table('comment_like')
            ->where('comment_id', $comment_id)
            ->where('user_id', $user_id)
            ->delete();

        $comment->decrement('like_count', 1);

        return $this->response->noContent();

    }


    public function reply($comment_id, Request $request)
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

        $validation = Validator::make(Input::all(), [
            'content' => 'required',        // 评论内容
        ], $messages);

        if ($validation->fails()) {
            return $this->response->error(implode(',', $validation->errors()), 500);
        }


        $user_id = $this->auth->user()->id;

        $is_like = false;

        $reply_comment = Comment::findOrFail($comment_id);

//        if(!$comment) {
//            return API::response()->array(['status' => false, 'message' => '评论不存在']);
//        }

        $content = $request->input('content');

        // 判断EVENT是否存在
        $event = Event::find($reply_comment->event_id);

        if (!$event) {
            return $this->response->error('动态不存在', 500);
        }

        $comment = new Comment();
        $comment->event_id = $reply_comment->event_id;
        $comment->content = trim($content);
        $comment->parent_id = $reply_comment->parent_id == 0 ? $comment_id : $reply_comment->parent_id;
        $comment->reply_id = $reply_comment->parent_id == 0 ? 0 : $comment_id;
        $comment->user_id = $user_id;
        $comment->create_time = time();
        $comment->save();

        // 更新EVENT信息
        $event->comment_count += 1;
        $event->save();

        if ($event->user_id != $this->auth->user()->id) {
            //  消息
            $message = new Message();
            $message->from_user = $this->auth->user()->id;
            $message->to_user = $event->user_id;
            $message->type = 3;
            $message->msgable_id = $comment->comment_id;
            $message->msgable_type = 'App\Comment';

            $message->create_time = time();
            $message->save();

            // 推送
            $name = $this->auth->user()->nickname ? $this->auth->user()->nickname : 'keeper' . $this->auth->user()->id;
            $content = $name . ' 评论了你';

//            $push = new MyJpush();
//            $push->pushToSingleUser($event->user_id,$content);
        }


        return $comment;
    }


}