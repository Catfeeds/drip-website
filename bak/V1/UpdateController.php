<?php
/**
 * Created by PhpStorm.
 * User: Jason.z
 * Date: 16/9/27
 * Time: 上午9:12
 */

namespace App\Http\Controllers\Api\V1;

use Auth;

use App\User;
use App\Checkin;
use App\Event;
use App\Models\Attach as Attach;
use App\Models\Version as Version;


use Validator;
use API;
use DB;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class UpdateController extends BaseController
{
    /**
 * 检查更新接口
 */
    public function check(Request $request)
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

        $validation = Validator::make(Input::all(), [
            'platform'		=> 	'required',		// 平台
            'version'       =>  'required',     // 版本号
        ],$messages);

        if($validation->fails()){
            return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
        }

        // 获取最新版本号
        $latest_version = Version::orderBy('no','desc')->first();

        $app_version = $request->input('version');
        
        $server_version_arr = explode('.',$latest_version['no']);
        $app_version_arr = explode('.',$app_version);

        $content = $latest_version['content'];

        // 无更新
        $type = 0;


        if($server_version_arr[0].'.'.$server_version_arr[1]>$app_version_arr[0].'.'.$app_version_arr[1]) {
            // apk 更新
            $type = 2;
            // 获取最新的APK版本
            $latest_version = Version::where('type','=',2)->orderBy('no','desc')->first();

        } else if(($server_version_arr[0].'.'.$server_version_arr[1]==$app_version_arr[0].'.'.$app_version_arr[1])&&($server_version_arr[2]>$app_version_arr[2])){
            // 资源更新
            $type = 1;
        } else {
            $content = '';
        }
        // TODO 删除掉除version外的字段
        return API::response()->array(['status' => true, 'message' =>'','version'=>$latest_version,'latest_version'=>$latest_version['no'],'type'=>$type,'content'=>$content])->statusCode(200);

    }


}