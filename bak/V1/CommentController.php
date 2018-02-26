<?php
/**
 * Created by PhpStorm.
 * User: Jason.z
 * Date: 2016/12/8
 * Time: 下午7:28
 */

namespace App\Http\Controllers\Api\V1;

use App\Models\Comment;
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


class CommentController extends BaseController {


    public function like()
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

        $validation = Validator::make(Input::all(), [
            'comment_id'		=> 	'required',		// 评论id
        ],$messages);

        if($validation->fails()){
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        $comment_id =  Input::get('comment_id');

        $user_id = $this->auth->user()->user_id;

        $is_like = false;

        $comment = Comment::find($comment_id);

        if(!$comment) {
            return API::response()->array(['status' => false, 'message' => '评论不存在'])->statusCode(200);
        }
        
        // 查询是否
        $like = DB::table('comment_like')
            ->where('comment_id',$comment_id)
            ->where('user_id',$user_id)
            ->first();

        if($like) { //取消点赞
            DB::table('comment_like')
                ->where('comment_id',$comment_id)
                ->where('user_id',$user_id)
                ->delete();

            $comment->decrement('like_count',1);

        } else {
            DB::table('comment_like')->insert([
                ['comment_id' => $comment_id, 'user_id' => $user_id,'create_time'=>time()]
            ]);

            $comment->increment('like_count',1);

            $is_like = true;
        }

        return API::response()->array(['status' => true, 'message' => '','data'=>compact('is_like')])->statusCode(200);

    }

}