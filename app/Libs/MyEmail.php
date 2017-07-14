<?php
/**
 * Created by PhpStorm.
 * User: Jason.z
 * Date: 16/7/21
 * Time: 下午11:04
 */

namespace  App\Libs;

use App\User;
use App\Models\Device as Device;
use JPush;
/**
 * 主要对 email 封装
 * User: Jason.z
 * Date: 16/7/15
 * Time: 下午1:43
 */
class MyEmail
{
    private $API_URL = 'http://api.sendcloud.net/apiv2/mail/sendtemplate';
    private $API_USER = 'keepdays';
    private $API_KEY = 'zDIdjyKEUSqdyESA';

    public function sendToSingleUser($email,$subject,$template_name,$params=[])
    {
        $vars['to'] = [$email];

        if($params) {
            $vars['sub'] = $params;
        }

        $vars = json_encode($vars);

        return $this->_send($vars,$subject,$template_name);
    }

    public function sendToUserList()
    {
        
    }


    private function _send($vars,$subject,$template_name)
    {
        $param = array(
            'apiUser' => $this->API_USER, # 使用api_user和api_key进行验证
            'apiKey' => $this->API_KEY,
            'from' => 'hi@keepdays.com', # 发信人，用正确邮件地址替代
            'fromName' => '坚持每一天',
            'xsmtpapi' => $vars,
            'templateInvokeName' => $template_name,
            'subject' => $subject,
            'respEmailId' => 'true'
        );


        $data = http_build_query($param);

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $data
            ));
        $context  = stream_context_create($options);
        $result = file_get_contents($this->API_URL, FILE_TEXT, $context);
//        echo $result;
        return $result;
    }

}