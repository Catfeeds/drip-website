<?php namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Article;

class ArticleController extends Controller {


    public function index()
    {
        $articles = Article::orderBy('created_at', 'desc')->get();

        return view('articles.index',compact('articles'));
    }

    public function view($id)
    {
        $article = Article::find($id);

        return view('articles.view',compact('article'));

    }

}