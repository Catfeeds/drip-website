<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/7/12
 */


namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Models\Version;
use App\Models\Channel;
use Yajra\Datatables\Datatables;


use App\Http\Requests;
use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    //
    public function index()
    {
        return view('admin.index.index');
    }
}