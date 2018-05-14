<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;

use App\Models\Order as Order2;
use App\Models\Recharge;
use App\Models\Energy;

use App\Http\Requests;
use App\Http\Controllers\Api\BaseController;

class WechatController extends BaseController
{
    private $app;
    public function __construct()
    {
        $options = [
            // 前面的appid什么的也得保留哦
            'app_id' => 'wxac31b5ac3e65915a',
            'payment' => [
                'merchant_id'        => '1497457342',
                'key'                => 'c2bbdff6e5f82751c3da0f4bd6618f1d',
                'cert_path'          => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
                'key_path'           => 'path/to/your/key',      // XXX: 绝对路径！！！！
                'notify_url'         => 'http://drip.growu.me/wechat/notify',       // 你也可以在下单时单独设置来想覆盖它
                // 'device_info'     => '013467007045764',
                // 'sub_app_id'      => '',
                // 'sub_merchant_id' => '',
                // ...
            ],
        ];

        $this->app = new Application($options);
    }

    public function recharges()
    {
        $recharges = Recharge::all();

        return response()->json($recharges);
    }

    //
    public function pay(Request $request) {

        $recharge_id = $request->input('id');

        $recharge = Recharge::find($recharge_id);

        if(!$recharge) {
            $this->response->error("计费点不存在",500);
        }

        $payment = $this->app->payment;

        $out_trade_no = date('YmdHis').rand(1000,9999);
        $user = $this->auth->user();

        $order2 = new Order2();
        $order2->user_id = $user->id;
        $order2->recharge_id = $recharge_id;
        $order2->total_fee = $recharge->price;
        $order2->out_trade_no = $out_trade_no;
        $order2->save();

        $attributes = [
            'trade_type'       => 'APP', // JSAPI，NATIVE，APP...
            'body'             => $recharge->name,
            'detail'           => $recharge->name,
            'out_trade_no'     => $out_trade_no,
            'total_fee'        => ($recharge->price)*100, // 单位：分
//            'notify_url'       => 'http://drip.growu.me/api/wechat/notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
        ];

        $order = new Order($attributes);

        $result = $payment->prepare($order);
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            $config = $payment->configForAppPayment($result["prepay_id"]);
            return $config;
        } else {
            $this->response->error("下单错误".$result->result_msg,500);
        }
    }

}
