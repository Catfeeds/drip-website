<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/4/13
 */

namespace App\Http\Controllers\Api\V3;

use Auth;
use Validator;
use API;
use DB;

use App\User;
use App\Checkin;

use App\Models\Channel;
use App\Models\Version;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class UpdateController extends BaseController
{
    public function audit(Request $request) {

        $app_version = $request->input('app_version');

        if($request->has('channel')) {
            $channel = Channel::Where('name','=',$request->input['channel'])
                ->get();

            if($channel) {
                if($channel['audit_version'] == $app_version) {
                    return $this->response->array(['is_audit'=>true]);
                }
            }
        }

        return $this->response->array(['is_audit'=>false]);
    }


    public function check(Request $request) {
        $app_version = $request->input('app_version');
        $web_version = $request->input('web_version');

        // 是否在审核模式下
        if($request->has('channel')) {
            $channel = Channel::Where('name','=',$request->input['channel'])
                ->first();

            if($channel) {
                if($channel->audit_version == $app_version) {
                    return $this->response->array(['type'=>0]);
                }
            }
        }

        // 检查是否要整包更新
        $app_version = Version::Where('app_version','>',$app_version)
            ->Where('type','=',2)
            ->OrderBy('app_version','desc')
            ->first();

        if($app_version) {
            return $this->response->array(['type'=>2,'version'=>$app_version->app_version,'message'=>$app_version->content]);
        }

        // 检查是否要资源更新
        $web_version = Version::Where('web_version','>',$web_version)
            ->Where('type','=',1)
            ->OrderBy('web_version','desc')
            ->first();

        if($web_version) {
            return $this->response->array(['type'=>1,'version'=>$web_version->web_version,'message'=>$web_version->content]);
        }

        return $this->response->array(['type'=>0]);

    }
}