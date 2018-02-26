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
use App\Models\Event;
use App\Models\Attach as Attach;

use Validator;
use API;
use DB;
use Log;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;

use Qiniu\Auth as QiniuAuth;
use Qiniu\Storage\UploadManager;

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
                $attach->name = $fileName;
                $attach->mine_type = $mineType;
                $attach->size = $size;
                $attach->hash = $hash;
                $attach->extension = $extension;
                $attach->path = date('Y-m-d');
                $attach->user_id = $this->auth->user()->id;
                $attach->save();

                // 同步到七牛
                $accessKey = 'Gp_kwMCtSa1jdalGbgv4h8Xk1JMA2vDqPyVIVVu5';
                $secretKey = 'DmjVDP_FxJuFccMRUpomHou-nmNw6QzDDLmyqC0D';
                $auth = new QiniuAuth($accessKey, $secretKey);
                $bucket = 'drip';
                $token = $auth->uploadToken($bucket);

                $uploadMgr = new UploadManager();

                list($ret, $err) = $uploadMgr->putFile($token, $fileName, $destinationPath.'/'.$fileName);
                if ($err !== null) {
                    return $this->response->error('图片类型不合法',500);
                }

//                $data = ['url'=> url('/'.$destinationPath).'/'.$fileName,'id'=>$attach->id];

                $data = ['url'=> 'http://file.growu.me/'.$ret['key'],'id'=>$attach->id];

                return $data;

            } else {
                return $this->response->error('无效的图片',500);
            }
        } else {
            return $this->response->error('请选择需要上传的图片',500);
        }
    }
}