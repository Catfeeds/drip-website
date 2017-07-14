<?php
/**
 * Created by PhpStorm.
 * User: Jason.z
 * Date: 2016/12/9
 * Time: 上午9:34
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Models\Good as Good;
use Yajra\Datatables\Datatables;


use App\Http\Requests;
use App\Http\Controllers\Controller;

class MallController extends Controller
{
    //
    public function index() {
        return view('admin.mall.index');
    }

    public function create(Request $request) {
        $type = $request->type;
        $no = $request->no;
        $content = $request->content;

        $version  = new Version();
        $version->type = $type;
        $version->no = $no;
        $version->content = nl2br($content);
        $version->create_time = time();
        $version->save();

        return ['status'=>true,'message'=>'添加成功'];

    }

    public function ajax_goods()
    {
        return Datatables::of(Good::query())
            ->addColumn('action', function ($version) {
                return '<a href="#" data-id="'.$version->id.'" class="btn btn-xs btn-danger"><i class="glyphicon glyphicon-delete"></i> 删除</a>';
            })
            ->editColumn('create_time', function ($version) {
                return date("Y-m-d H:i:s",$version->create_time);
            })
            ->editColumn('status', function ($good) {
                switch ($good->status) {
                    case 0:
                        return '<span class="label label-success">未上架</span>';
                        break;
                    case 1:
                        return '<span class="label label-warning">已上架</span>';
                        break;
                    default;
                        return '<span class="label">未知</span>';
                        break;
                }
            })
            ->make(true);
    }
}