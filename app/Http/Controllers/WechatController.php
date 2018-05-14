<?php
/**
 * @author: Jason.z
 * @email: ccnuzxg@163.com
 * @website: http://www.jason-z.com
 * @version: 1.0
 * @date: 2018/4/18
 */

namespace App\Http\Controllers;

use App\Goal;
use App\Event;
use App\Models\Order;
use EasyWeChat\Foundation\Application;
use App\User;
use App\Models\Energy;
use App\Models\Recharge;

use Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WechatController extends Controller{

    private $app;
    public function __construct()
    {
        $options = [
            // 前面的appid什么的也得保留哦
            'app_id' => 'wxac31b5ac3e65915a',
            // ...

            // payment
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

    public function notify(Request $request)
    {
        Log::info('微信支付通知');
        Log::info(file_get_contents("php://input"));

        $response = $this->app->payment->handleNotify(function($notify, $successful){
            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
            $order = Order::where('out_trade_no',$notify->out_trade_no)->first();

            if (!$order) { // 如果订单不存在
                return 'Order not exist.'; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }

            // 如果订单存在
            // 检查订单是否已经更新过支付状态
            if ($order->status > 0) { // 假设订单字段“支付时间”不为空代表已经支付
                return true; // 已经支付成功了就不再更新了
            }

            // 用户是否支付成功
            if ($successful) {
                // 不是已经支付状态则修改为已经支付状态
                $order->paid_at = date('Y-m-d H:i:s'); // 更新支付时间为当前时间
                $order->transaction_id = $notify->transaction_id;
                $order->status = 1;

                // 发货
                $user = User::find($order->user_id);
                $recharge = Recharge::find($order->recharge_id);

                if($recharge) {
                    $user->energy_count += ($recharge->price)*100;
                    $user->save();

                    // 记录日志
                    $energy = new Energy();
                    $energy->user_id = $user->id;
                    $energy->change = ($recharge->price)*100;
                    $energy->obj_type = 'buy';
                    $energy->obj_id = $order->id;
                    $energy->create_time = time();
                    $energy->save();

                    $order->dealed_at = date('Y-m-d H:i:s');
                    $order->status = 2;
                }
            } else { // 用户支付失败
                $order->status = -1;
            }

            $order->save(); // 保存订单

            return true; // 返回处理完成
        });

        return $response;
    }

}