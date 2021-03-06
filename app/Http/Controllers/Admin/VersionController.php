<?php
/**
 * Created by PhpStorm.
 * User: Jason.z
 * Date: 2016/11/9
 * Time: 上午9:37
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Models\Version;
use App\Models\Channel;
use Yajra\Datatables\Datatables;


use App\Http\Requests;
use App\Http\Controllers\Controller;

class VersionController extends Controller
{
    //
    public function index() {
        return view('admin.version.index');
    }

    public function create(Request $request) {

        $version  = new Version();
        $version->type = $request->input('type');
        $version->app_version =  $request->input('app_version');
        $version->web_version =  $request->input('web_version');;
        $version->content = nl2br($request->input('content'));
        $version->is_push = $request->input('is_push');

        $version->save();

        return ['status'=>true,'message'=>'添加成功'];

    }

    public function get_versions()
    {
        return Datatables::of(Version::query())
            ->addColumn('action', function ($version) {
                return '<button data-id="'.$version->id.'" class="btn btn-xs btn-danger btn-version-del"><i class="glyphicon glyphicon-delete"></i> 删除</a>';
            })
            ->editColumn('type', function ($version) {
                switch ($version->type) {
                    case 1:
                        return '<span class="label label-success">资源更新</span>';
                        break;
                    case 2:
                        return '<span class="label label-warning">整包更新</span>';
                        break;
                    default;
                        return '<span class="label">未知</span>';
                        break;
                }
            })
            ->make(true);
    }

    public function delete(Request $request) {

        $id = $request->input('id');

        $version = Version::find($id);

        if(!$version) {
            return ['status'=>true,'message'=>'对象不存在'];
        }

        $version->delete();

        return ['status'=>true,'message'=>'删除成功'];

    }
}