<?php
namespace  App\Libs;

use App\User;
use App\Models\Device as Device;
use JPush;
use JPushExcepition;
/**
 * 主要对 jpush 封装
 * User: Jason.z
 * Date: 16/7/15
 * Time: 下午1:43
 */
class MyJpush
{
    private $appKey = '46becc0d96be0a46d601867f';
    private $masterSecret = 'dbcdf2429fcca86bfffc5b12';

    private $client;

    public function __construct($appKey = NULL, $masterSecret = NULL)
    {
        if($appKey) $this->appKey = $appKey;
        if($masterSecret) $this->masterSecret = $masterSecret;
        $this->client = new JPush($this->appKey,$this->masterSecret,storage_path().'/logs/jpush.log');
    }

    /**
     * 对单个用户推送
     * $user_id 用户ID
     * $content 消息内容
     */
    public function pushToSingleUser($user_id,$content)
    {
        if($this->client) {
            // 查找用户的设备

            $device = Device::where('user_id',$user_id)
                ->whereNotNull('push_id')
                ->orderBy('update_time','desc')
                ->first();

//            if($devices) {
//                foreach($devices as $device) {
                    if($device->push_id) {
                        try {
                            $result = $this->client->push()
                                ->setPlatform(strtolower($device->device_platform))
                                ->addRegistrationId($device->push_id)
                                ->setNotificationAlert($content)
                                ->setOptions($sendno=null, $time_to_live=null, $override_msg_id=null, $apns_production=true, $big_push_duration=null)
                                ->send();
                        } catch (\JPush\Exceptions\JPushException $exception) {

                        }

                    }
//                     echo '推送结果:' . json_encode($result) . PHP_EOL;
//                }
//            } else {
////                echo "无推送设备";
//            }


        }
    }
}