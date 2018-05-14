<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/4/13
 */

namespace App\Http\Controllers\Api\V2;

use Auth;
use Validator;
use API;
use DB;

use App\User;
use App\Checkin;


use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class UpdateController extends BaseController
{
    public function audit(Request $request) {
        $app_version = $request->input('app_version');
        $web_version = $request->input('app_version');
        $platform = $request->input('platform');

        if($app_version == '1.6.0') {
            return $this->response->array(['is_audit'=>true]);
        }

        return $this->response->array(['is_audit'=>false]);
    }


    public function check() {
        return $this->response->array(['type'=>1,'version'=>'','message'=>'1.6.1更新日志<br><br>1、修复返回键处理;<br>2、增加搜索好友功能。']);
    }
}