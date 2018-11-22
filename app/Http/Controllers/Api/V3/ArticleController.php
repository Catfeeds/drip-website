<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/7/5
 */

namespace App\Http\Controllers\Api\V3;

use App\Like;
use Auth;
use Validator;
use API;
use DB;
use App\Models\UserFollow;
use Illuminate\Support\Collection;

use Carbon\Carbon;


use App\Article;
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
use App\Http\Controllers\Api\V3\Transformers\EventTransformer;
use League\Fractal\Serializer\ArraySerializer;


class ArticleController extends BaseController {

    public function getTop(Request $request) {
        $articles = Article::Where('is_top',1)
            ->orderBy('order','desc')
            ->orderBy('created_at','desc')
        ->limit(5)
        ->get();

        return response()->json($articles);

    }

    public function getDetail($article_id,Request $request) {
           $article = Article::find($article_id);

           if(!$article) {
               return $this->response->error("文章不存在",500);
           }

           $new_article = [];

           $new_article['id'] = $article->id;
           $new_article['title'] = $article->title;
            $new_article['like_count'] = $article->like_count;
           $new_article['content'] = $article->content;

        // 获取评论
        $comments = Comment::with('user')
            ->where('commentable_id',$article_id)
            ->where('commentable_type',"articles")
            ->orderBy('like_count','desc')
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

            $new_comments[$k]['reply'] = null;

            $new_comments[$k]['created_at'] = date('Y-m-d H:i:s',$comment->create_time);
        }

        $new_article['comments'] = $new_comments;


        return $new_article;
    }

    public function doComment($article_id,Request $request) {
        $reply_id  = $request->input('reply_id');
        $content  = $request->input('content');

        $user = $this->auth->user();

        $comment = new Comment();
        $comment->content = trim($content);
        $comment->parent_id = $reply_id;
        $comment->reply_id = $reply_id;
        $comment->user_id = $user->id;
        $comment->commentable_id = $article_id;
        $comment->commentable_type = 'articles';
        $comment->save();

        $comment =  Comment::with('user')->find($comment->comment_id);

        return response()->json($comment);
    }

    public function doLike($article_id,Request $request) {
        $article = Article::find($article_id);

        if($article) {
            $article->like_count += 1;
            $article->save();
        }
    }
}