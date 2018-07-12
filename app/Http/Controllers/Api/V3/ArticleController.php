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
        $articles = Article::OrderBy('is_top','desc')
        ->orderBy('created_at','desc')
//        ->limit(10)
        ->get();

        return $articles;
    }

    public function getDetail($article_id,Request $request) {
           $article = Article::find($article_id);

           if(!$article) {
               return $this->response->error("文章不存在",500);
           }

           $new_article = [];

           $new_article['id'] = $article->id;
           $new_article['title'] = $article->title;

           $new_article['content'] = $article->content;


        return $new_article;
    }
}