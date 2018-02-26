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
namespace App\Http\Controllers\Api\V1;

use Auth;

use App\User;
use App\Checkin;
use App\Event;
use App\Models\Attach as Attach;

use Validator;
use API;
use DB;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class UploadController extends BaseController
{
    // 文件上传
    public function image(Request $request) {
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            if ($file->isValid()) {
                $allowed_extensions = ["png", "jpg", "gif"];

                if ($file->getClientOriginalExtension() && !in_array($file->getClientOriginalExtension(), $allowed_extensions)) {
                    return API::response()->array(['status' => false, 'message'=>'图片类型不合法','data'=>''])->statusCode(200);
                }

                $destinationPath = 'uploads/images/'.date('Y-m-d').'/';
                $extension = $file->getClientOriginalExtension();
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

                return API::response()->array(['status' => true, 'message'=>'上传成功','data'=>$data])->statusCode(200);


            } else {
                return API::response()->array(['status' => false, 'message'=>'无效的图片','data'=>''])->statusCode(200);
            }
        } else {
            return API::response()->array(['status' => false, 'message'=>'请选择需要上传的图片','data'=>''])->statusCode(200);
        }
    }
}