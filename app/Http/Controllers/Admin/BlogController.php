<?php

/**
 * 用户管理控制器
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use DB;

use App\User;
use App\Models\Feedback as Feedback;
use App\Models\Message as Message;
use App\Models\Energy as Energy;
use App\Libs\MyJpush as MyJpush;
use App\Libs\MyEmail as MyEmail;

use App\Event;

use Yajra\Datatables\Datatables;


use App\Http\Requests;
use App\Http\Controllers\Controller;

class BlogController extends Controller
{
    //
    public function articles()
    {

        return view('admin.blog.articles', []);
    }
}
