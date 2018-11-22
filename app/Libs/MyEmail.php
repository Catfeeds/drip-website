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
use AWS;


/**
 * 主要对 email 封装
 * User: Jason.z
 * Date: 16/7/15
 * Time: 下午1:43
 */
class MyEmail
{
    private $API_URL = 'http://api.sendcloud.net/apiv2/mail/sendtemplate';
    private $API_USER = 'growuu';
    private $API_KEY = 'KvQkknDKC47TyEVG';
    private $SEND_TYPE = 'sendcloud';

    public function __construct($send_type='sendcloud')
    {
        $this->SEND_TYPE = $send_type;
    }

    public function sendToSingleUser($email,$subject,$template_name,$params=[])
    {
        $emails = [$email];

        return $this->_send($emails,$subject,$template_name,$params);
    }

    public function sendToSingleUser2($email,$subject,$content) {
        $emails = [$email];


        $client = AWS::createClient('ses');
        $client->sendEmail([
            'Destination' => [ // REQUIRED
                'ToAddresses' => $emails,
            ],
            'Message' => [ // REQUIRED
                'Body' => [
                    'Html' => [
                        'Data' => $content, // REQUIRED
                    ],
                    'Text' => [
                        'Data' => $content, // REQUIRED
                    ],
                ],
                'Subject' => [ // REQUIRED
                    'Data' => $subject, // REQUIRED
                ],
            ],
            'Source' => utf8_encode('水滴打卡').' <drip@growu.me>',
        ]);

    }

    public function sendToUserList()
    {

    }

    private function _send($emails,$subject,$template_name,$params)
    {
            $vars = [];

            $vars['to'] = $emails;

            if($params) {
                $vars['sub'] = $params;
            }

            $vars = json_encode($vars);

            $param = array(
                'apiUser' => $this->API_USER, # 使用api_user和api_key进行验证
                'apiKey' => $this->API_KEY,
                'from' => 'drip@growu.me', # 发信人，用正确邮件地址替代
                'fromName' => '水滴打卡',
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


    public function sendWithContent($emails,$subject,$content) {
        $vars = [];

        $vars['to'] = $emails;

        $vars = json_encode($vars);

        $param = array(
            'apiUser' => $this->API_USER, # 使用api_user和api_key进行验证
            'apiKey' => $this->API_KEY,
            'from' => 'drip@growu.me', # 发信人，用正确邮件地址替代
            'fromName' => '水滴打卡',
            'html'=>  $content,
            'xsmtpapi' => $vars,
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
        $result = file_get_contents('http://api.sendcloud.net/apiv2/mail/send', FILE_TEXT, $context);
//        echo $result;
        return $result;
    }
}