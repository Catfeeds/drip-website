<?php
/**
 * Created by PhpStorm.
 * User: Jason.z
 * Date: 2016/12/9
 * Time: 下午2:10
 */

namespace App\Http\Controllers\Api\V1;

use Auth;
use Validator;
use API;
use DB;

use App\Event;
use App\Checkin;
use App\Models\Good as Good;


use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class GoodController extends BaseController
{
    public function info(Request $request){
        $good = Good::find($request->good_id);
        return API::response()->array(['status' => true, 'message' =>'','data'=>$good]);
    }

    public function hot(){

        $goods = Good::take(10)->get();

        return API::response()->array(['status' => true, 'message' =>'','data'=>$goods])->statusCode(200);

    }

}

