<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/7/4
 */


namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Models\Version;
use App\Models\Channel;
use Yajra\Datatables\Datatables;


use App\Http\Requests;
use App\Http\Controllers\Controller;

class ChannelController extends Controller
{
    //
    public function index() {
        return view('admin.channel.index');
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

    public function get_channels()
    {
        return Datatables::of(Channel::query())
            ->addColumn('action', function ($channel) {
                return '<button data-id="'.$channel->id.'" class="btn btn-xs btn-danger btn-channel-del"><i class="glyphicon glyphicon-delete"></i> 删除</a>';
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