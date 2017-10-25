<?php
/**
 * Created by PhpStorm.
 * User: Jason.z
 * Date: 16/8/1
 * Time: 下午6:35
 */

/**
 * 附件控制器
 */
namespace App\Http\Controllers\Api\V2;

use Auth;

use App\User;
use App\Checkin;
use App\Event;
use App\Models\Attach as Attach;

use Validator;
use API;
use DB;
use Log;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class UploadController extends BaseController
{
    // 文件上传
    public function image(Request $request) {

        Log::info('文件上传');
        Log::info($request);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            if ($file->isValid()) {
                $allowed_extensions = ["png","jpg","jpeg","bmp","gif"];

                $extension = $file->getClientOriginalExtension();
                if(strstr($extension,'?')) {
                    $extension = substr($extension,0,strpos($extension,'?'));
                }

                if ($extension && !in_array($extension, $allowed_extensions)) {
                    return $this->response->error('图片类型不合法',500);
                }

                $destinationPath = 'uploads/images/'.date('Y-m-d').'/';

                $fileName = uniqid().'.'.$extension;
                $mineType = $file->getMimeType();
                $size = $file->getClientSize();
                $hash = hash_file('md5',$file);
                $file->move($destinationPath, $fileName);

                $attach = new Attach();
                $attach->attach_name = $fileName;
                $attach->attach_type = $mineType;
                $attach->attach_size = $size;
                $attach->attach_hash = $hash;
                $attach->attach_extension = $extension;
                $attach->attach_path = date('Y-m-d');
                $attach->create_time = time();
                $attach->create_user = $this->auth->user()->user_id;

                $attach->save();

                $data = ['url'=> url('/'.$destinationPath).'/'.$fileName,'id'=>$attach->attach_id];

                return $data;

            } else {
                return $this->response->error('无效的图片',500);
            }
        } else {
            return $this->response->error('请选择需要上传的图片',500);
        }
    }
}