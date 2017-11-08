<?php

/**
 * 用户管理控制器
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use DB;
use Validator;
use App\Article;
use Yajra\Datatables\Datatables;
use App\Http\Controllers\Controller;
use Qiniu\Auth;


class ArticleController extends Controller
{
    public function index()
    {
        return view('admin.articles.index', []);
    }

    public function create()
    {
        $accessKey = 'Gp_kwMCtSa1jdalGbgv4h8Xk1JMA2vDqPyVIVVu5';
        $secretKey = 'DmjVDP_FxJuFccMRUpomHou-nmNw6QzDDLmyqC0D';
        $auth = new Auth($accessKey, $secretKey);
        $bucket = 'drip';
        $token = $auth->uploadToken($bucket);

        return view('admin.articles.create', compact('token'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'category' => 'required',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('/admin/blog/articles')
                ->withInput()
                ->withErrors($validator);
        }

        $article = new Article();
        $article->title = $request->input('title');
        $article->content = $request->input('content');
        $article->category_id = $request->input('category');
        $article->user_id = 1;

        $article->save();

        return redirect('/admin/blog/articles');
    }

    public function lists(){
        return Datatables::of(Article::query())
            ->addColumn('action', function ($article) {
                return '<a href="#edit-'.$article->id.'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> 编辑</a>';
            })
//            ->editColumn('reg_time', function ($user) {
//                return $user->reg_time>0?date("Y-m-d H:i:s",$user->reg_time):'';
//            })
//            ->editColumn('last_login_time', function ($user) {
//                return $user->last_login_time>0?date("Y-m-d H:i:s",$user->last_login_time):'';
//            })
//            ->editColumn('nickname',  function ($user) {
//                return '<a href="user_view/'.$user->user_id.'" data-toggle="modal" data-target="#user-view-modal"><img src="'.$user->user_avatar.'" class="img-circle" width="24" height="24"> '.$user->nickname."</a>";
//            })
            ->make(true);
    }
}
