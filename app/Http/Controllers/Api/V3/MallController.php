<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/4/17
 */

namespace App\Http\Controllers\Api\V3;

use App\Models\Comment;
use Auth;
use Validator;
use API;
use DB;

use App\Models\Good;
use App\Models\Exchange;
use App\Models\Energy;


use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Api\V3\Transformers\UserTransformer;
use League\Fractal\Serializer\ArraySerializer;

class MallController extends BaseController
{
    public function getGoods()
    {
        $goods = Good::all();

        return response()->json($goods);
    }

    public function getExchanges()
    {
        $user = $this->auth->user();

        $exchanges = Exchange::where('user_id','=',$user->id)->get();

        return response()->json($exchanges);
    }

    public function getGoodDetail($good_id)
    {
        $good = Good::find($good_id);

        return response()->json($good);
    }

    public function doExchangeGood($good_id)
    {
        $user = $this->auth->user();

        $good = Good::find($good_id);

        if($user->energy_count < $good->price) {
            $this->response->error("水滴币数量不足",500);
        }

        if($good->type == 100) {

            $params = json_decode($good->params);

            if($user->vip_type > 0) {

                if($user->vip_type != $params->type ) {
                    $this->response->error("暂不支持会员类型转换操作",500);
                }

                $user->vip_end_date = date('Y-m-d', strtotime($user->vip_end_date . ' + ' . $params->value . ' days'));

            } else {
                $user->vip_type = $params->type;
                $user->vip_begin_date = date('Y-m-d');
                $user->vip_end_date = date('Y-m-d',strtotime("+ " . $params->value . " days"));
            }

            $user->energy_count -= $good->price;
            $user->save();

            // 记录到兑换记录
            $exchange = new Exchange();
            $exchange->user_id = $user->id;
            $exchange->good_id = $good->id;
            $exchange->good_num = 1;
            $exchange->good_name = $good->name;
            $exchange->save();

            // 记录日志
            $energy = new Energy();
            $energy->user_id = $user->id;
            $energy->change = ($good->price)*-1;
            $energy->obj_type = 'exchange';
            $energy->obj_id = $exchange->id;
            $energy->create_time = time();
            $energy->save();

        }

        return $this->response->item($user, new UserTransformer(), [], function ($resource, $fractal) {
            $fractal->setSerializer(new ArraySerializer());
        });
    }
}