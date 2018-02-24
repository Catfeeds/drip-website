<?php
/**
 * 用户控制器
 */

namespace App\Http\Controllers\Api\V2;

use Auth;
use Dompdf\Exception;
use Validator;
use API;
use JWTAuth;
use Log;
use DB;
use App;
use Overtrue\EasySms\EasySms;
use GuzzleHttp\Client;


use App\User;
use App\Models\Event;
use App\Models\Message;
use App\Models\Device as Device;
use App\Libs\MyEmail as MyEmail;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Api\V2\Transformers\UserTransformer;
use League\Fractal\Serializer\ArraySerializer;


class AuthController extends BaseController
{

    /**
     * 登录接口
     */
    public function login(Request $request)
    {
        Log::debug('邮箱／手机号登录请求');
        Log::debug($request);

        $messages = [
            'account.required' => '请输入邮箱地址',
            'password.required' => '请输入密码',
            'password.between' => '密码长度需:min到:max位',
        ];

        $validation = Validator::make(Input::all(), [
            'account' => 'required',        // 邮箱
            'password' => 'required|between:6,16',        // 密码
            'device' => '',    // 设备
        ], $messages);

        if ($validation->fails()) {
            return $this->response->error(implode(',', $validation->errors()->all()), 500);
        }

        $login_type = '';

        if (preg_match("/^1[34578]\d{9}$/", $request->input('account'))) {
            $login_type = 'phone';
        } else if (filter_var($request->input('account'), FILTER_VALIDATE_EMAIL)) {
            $login_type = 'email';
        }

        $credentials = [
            $login_type => $request->input('account'),
            'password' => $request->input('password')
        ];

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return $this->response->error("账号或密码不正确", 500);
            }
        } catch (JWTException $e) {
            return $this->response->error("创建token失败", 500);
        }

        $user = Auth::user();

        $this->_insert_device($user->id, $request->input('device'), $request);

        $token = JWTAuth::fromUser($user);

        return $this->response->item($user, new UserTransformer(array('token' => $token)), [], function ($resource, $fractal) {
            $fractal->setSerializer(new ArraySerializer());
        });
    }

    /**
     * 第三方登录
     * @param Request $request
     * @return mixed
     */
    public function thirdLogin(Request $request)
    {

        Log::debug('第三方登录请求');
        Log::debug($request);

        $validation = Validator::make(Input::all(), [
            'provider' => 'required',        // 邮箱
            'device' => '',    // 设备
        ]);

        if ($validation->fails()) {
            return $this->response->error("参数非法", 500);
        }

        $providers = array('qq', 'weibo', 'xiaomi', 'weapp', 'wechat');

        $provider = $request->input('provider');

        if (!in_array($provider, $providers)) {
            $this->response->error('未知的登录方式', 500);

        }

        // 整理参数
        $method = '_parse_' . $provider;
        $params = self::$method($request);

        // 查询openid 是否存在
        $provider = DB::table('users_bind')
            ->where('openid', $params['openid'])
            ->where('provider', $params['provider'])
            ->first();

        if ($provider) {
            $user = User::find($provider->user_id);
        } else {
            // 创建临时用户
            $user = new User();
            $user->user_avatar = $params['avatar'];
            $user->province = $params['province'];
            $user->nickname = $params['nickname'];
            $user->sex = $params['sex'];
            $user->city = $params['city'];
            $user->reg_time = time();
            $user->reg_ip = $request->ip();

            $user->save();

            // 插入绑定信息
            DB::table('users_bind')->insert([
                'user_id' => $user->id,
                'openid' => $params['openid'],
                'access_token' => $params['access_token'],
                'expire_in' => $params['expire_in'],
                'avatar' => $params['avatar'],
                'sex' => $params['sex'],
                'province' => $params['province'],
                'city' => $params['city'],
                'nickname' => $params['nickname'],
                'provider' => $params['provider'],
                'unionid' => isset($params['unionid']) ? $params['unionid'] : '',
            ]);

        }

        // 插入设备
        $this->_insert_device($user->id, $request->input('device'), $request);

        $token = JWTAuth::fromUser($user);

        return $this->response->item($user, new UserTransformer(array('token' => $token)), [], function ($resource, $fractal) {
            $fractal->setSerializer(new ArraySerializer());
        });

    }

    public function register(Request $request)
    {

        $messages = [
            'account.required' => '请输入邮箱或手机号',
            'password.required' => '请输入密码',
            'password.between' => '密码长度需:min到:max位',
        ];

        $validation = Validator::make(Input::all(), [
            'account' => 'required',        // 邮箱
            'code' => 'required|digits:4',
            'password' => 'required|between:6,16',        // 密码
            'device' => '',    // 设备
        ], $messages);

        if ($validation->fails()) {
            return $this->response->error(implode(',', $validation->errors()->all()), 500);
        }


        $login_type = '';

        if (preg_match("/^1[34578]\d{9}$/", $request->input('account'))) {
            $login_type = 'phone';
        } else if (filter_var($request->input('account'), FILTER_VALIDATE_EMAIL)) {
            $login_type = 'email';
        }

        if (empty($login_type)) {
            return $this->response->error('未知的登录方式', 500);
        }

        // 根据验证码和邮箱查找
        $code = DB::table('verify_code')
            ->where('send_type', $login_type)
            ->where('send_object', $request->account)
            ->where('type', 'register')
            ->where('code', $request->code)
            ->orderBy('create_time', 'desc')
            ->first();

        // 检验验证码是否有效

        if ($code) {
            if ($code->status == 1) {
                return $this->response->error('验证码已使用', 500);
            }

            if ($code->expire_time < time()) {
                return $this->response->error('验证码已过期', 500);
            }
        } else {
            return $this->response->error('验证码不存在', 500);
        }

        $user = User::where($login_type, '=', $request->input('account'))->first();
        if ($user) {
            return $this->response->error('邮箱/手机号已注册', 500);
        }

        $password = $request->input('password');

        // 查询用户是否存在

        $salt = rand(1000, 9999);

        $user = new User();
        $user->passwd = md5($password . $salt);
        if ($login_type == 'email') {
            $user->email = $request->input('account');
        } else if ($login_type == 'phone') {
            $user->phone = $request->input('account');
        }
        $user->salt = $salt;
        $user->reg_time = time();
        $user->reg_ip = $request->ip();
        $user->save();

        $this->_insert_device($user->id, $request->input('device'), $request);

        // 发送注册邮件
        if ($login_type == 'email') {
            $this->_send_register_email($user);
        }

        $token = JWTAuth::fromUser($user);

        return $this->response->item($user, new UserTransformer(array('token' => $token)), [], function ($resource, $fractal) {
            $fractal->setSerializer(new ArraySerializer());
        });
    }

    public function refreshToken()
    {
        $newToken = JWTAuth::parseToken()->refresh();
        return $this->response->array(['token' => $newToken]);
    }

    /**
     * 发送注册欢迎邮件
     * @param $email 邮箱地址
     */
    private function _send_register_email($user)
    {
        $this->_send_register_message($user);
        $myEmail = new MyEmail();
        $myEmail->sendToSingleUser($user->email, '欢迎来到水滴打卡', 'app_register_template');

    }

    /**
     * 发送注册站内信
     * @param $email 邮箱地址
     */
    private function _send_register_message($user)
    {
        $content = '<strong>Hi:</strong><br />
<br />
&nbsp; &nbsp;欢迎来到水滴打卡。<br />
<br />
&nbsp; &nbsp;水滴打卡一直致力于成为一款专业和全面的<strong>习惯养成</strong>和<strong>目标管理</strong>工具。<br />
<br />
&nbsp; &nbsp;希望在接下来的时间，水滴打卡能陪伴你一同实现你的目标或者梦想。<br />
<br />
&nbsp; &nbsp;我们深知还有许多不完善的地方，但我们一直都在积极地改进。<br />
<br />
&nbsp; &nbsp;我们更乐意倾听到你的声音和想法，这对我们很重要。<br />
<br />
&nbsp; &nbsp;如果你有任何问题，也可以通过下面的方式联系到我们。<br />
<br />
&nbsp; 微信公众号：<strong>格吾社区</strong><br />
&nbsp; 新浪微博：<strong>格吾社区</strong><br />
&nbsp; 客服微信号：<strong>growu001</strong><br />
  邮箱：<strong>drip@growu.me</strong><br />
&nbsp;
<div style="text-align:right">水滴打卡团队</div>';

        $message = new Message();
        $message->from_user = 0;
        $message->to_user = $user->id;
        $message->type = 6 ;
        $message->title = '欢迎来到水滴打卡' ;
        $message->content = $content;
        $message->msgable_id = $user->id;
        $message->msgable_type = 'welcome';
        $message->create_time  = time();
        $message->save();
    }


    /**
     * 插入设备信息
     * @param $user_id  用户ID
     * @param $device_info 设备信息
     */
    private function _insert_device($user_id, $device_info, $request)
    {
//    	Log::info('设备信息');
//    	Log::info($device_info);
        if ($device_info) {
            // 非移动设备暂不记录
            if (isset($device_info['platform'])) {
                if ($device_info['platform'] != 'Android' && $device_info['platform'] != 'iOS') {
                    return;
                }
            }

            // 根据UUID查找设备
            $device = Device::where('uuid', '=', $device_info['uuid'])->first();

            if (!$device) {
                $device = new Device();
            }

            // 更新或新增设备信息
            $device->user_id = $user_id;
            $device->uuid = $device_info['uuid'];
            $device->version = $device_info['version'];
            $device->platform = $device_info['platform'];
            $device->model = $device_info['model'];
            $device->cordova = $device_info['cordova'];
            if (isset($device_info['push_id']) && !empty($device_info['push_id'])) {
                $device->push_id = $device_info['push_id'];
            }
            $device->save();
        }

        // 更新用户信息
        // TODO 此处更新水滴币判断
        $user = User::find($user_id);
        $user->last_login_ip = $request->ip();
        $user->last_login_time = time();
        $user->save();

    }

    /**
     * 解析QQ参数
     * @see http://wiki.open.qq.com/wiki/website/get_user_info
     */
    private function _parse_qq($request)
    {
        // 获取
        $client = new Client();

        $app_id = 1106248902;

        $device = $request->device;

        if (isset($device['platform']) && $device['platform'] == 'iOS') {
            $app_id = 1106192747;
        }

        $res = $client->request('GET', 'https://graph.qq.com/user/get_user_info?access_token=' . $request->access_token . '&oauth_consumer_key=' . $app_id . '&openid=' . $request->userid, []);

        if ($res->getStatusCode() != 200) {
            $this->response->error("获取用户信息失败", 500);
        }

        $ret = json_decode($res->getBody());

        if (isset($ret->ret) && $ret->ret != 0) {
            $this->response->error($ret->msg, 500);
        }

        $sex = 0;

        if ($ret->gender == '男') {
            $sex = 1;
        } else if ($ret->gender == '女') {
            $sex = 2;
        }

        return [
            'openid' => $request->userid,
            'access_token' => $request->access_token,
            'expire_in' => $request->expires_time,
            'avatar' => $ret->figureurl_2,
            'sex' => $sex,
            'province' => $ret->province,
            'city' => $ret->city,
            'nickname' => $ret->nickname,
            'provider' => 'qq',
            'device' => $request->device
        ];
    }

    private function _parse_weapp($request)
    {
        $sex = 0;
        if ($request->gender == '男') {
            $sex = 1;
        } else if ($request->gender == '女') {
            $sex = 2;
        }

        return [
            'openid' => $request->userid,
            'access_token' => $request->access_token,
            'expire_in' => $request->expires_time,
            'avatar' => $request->avatarUrl,
            'sex' => $request->gender,
            'province' => $request->province,
            'city' => $request->city,
            'nickname' => $request->nickName,
            'provider' => 'weapp',
            'device' => $request->device
        ];
    }


    /**
     * 解析QQ参数
     * @see http://wiki.open.qq.com/wiki/website/get_user_info
     */
    private function _parse_xiaomi($request)
    {

        $sex = 0;

        return [
            'openid' => $request->userId,
            'access_token' => $request->access_token,
            'expire_in' => $request->expires_in,
            'avatar' => $request->miliaoIcon,
            'sex' => $sex,
            'province' => '',
            'city' => '',
            'nickname' => $request->miliaoNick,
            'provider' => 'xiaomi',
            'device' => $request->device
        ];
    }

    /**
     * 解析微博参数
     * @see http://open.weibo.com/wiki/2/users/show
     */
    private function _parse_weibo($request)
    {
        $client = new Client();

        $res = $client->request('GET', 'https://api.weibo.com/2/users/show.json?uid=' . $request->userId . '&access_token=' . $request->access_token, []);

        if ($res->getStatusCode() != 200) {
            $this->response->error("获取用户信息失败", 500);
        }

        $ret = json_decode($res->getBody());

        if (isset($ret->error_code)) {
            $this->response->error($ret->error, 500);
        }

        $sex = 0;

        if ($ret->gender == 'm') {
            $sex = 1;
        } else if ($ret->gender == 'f') {
            $sex = 2;
        }
        return [
            'openid' => $request->userId,
            'access_token' => $request->access_token,
            'expire_in' => $request->expires_time,
            'avatar' => $ret->avatar_hd,
            'sex' => $sex,
            'province' => $ret->province,
            'city' => $ret->city,
            'nickname' => $ret->screen_name,
            'provider' => 'weibo',
            'device' => $request->device
        ];
    }


    private function _parse_wechat($request)
    {
        // 获取
        $client = new Client();

        $res = $client->request('GET', 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=wxac31b5ac3e65915a&secret=f8b8aac88586192c2b60bfbbf807ef7d&code=' . $request->code . '&grant_type=authorization_code', []);

        if ($res->getStatusCode() != 200) {
            $this->response->error("请求access_token失败", 500);
        }

        $ret = json_decode($res->getBody());

        if (isset($ret->errcode)) {
            $this->response->error($ret->errmsg, 500);
        }

        $res2 = $client->request('GET', 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $ret->access_token . '&openid=' . $ret->openid);

        if ($res2->getStatusCode() != 200) {
            $this->response->error("请求用户信息失败", 500);
        }

        $ret2 = json_decode($res2->getBody());

        if (isset($ret2->errcode)) {
            $this->response->error($ret2->errmsg, 500);
        }

        return [
            'openid' => $ret2->openid,
            'access_token' => $ret->access_token,
            'expire_in' => time() + 7200,
            'avatar' => $ret2->headimgurl,
            'sex' => $ret2->sex,
            'province' => $ret2->province,
            'city' => $ret2->city,
            'country' => $ret2->country,
            'nickname' => $ret2->nickname,
            'provider' => 'wechat',
            'unionid' => $ret2->unionid,
            'device' => $request->device
        ];
    }


    /**
     * 获取验证码
     */
    public function getCode(Request $request)
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

        $validation = Validator::make(Input::all(), [
            'account' => 'required',     // 发送对象
            'type' => 'required',     // 用途
        ], $messages);

        if ($validation->fails()) {
            return $this->response->error(implode(',', $validation->errors()->all()), 500);
        }

        $account = $request->account;

        $account_type = '';

        if (preg_match("/^1[34578]\d{9}$/", $account)) {
            $account_type = 'phone';
        } else if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            $account_type = 'email';
        }

        $type = $request->type;

        // 检查获取频率 60s 一次

        // 如果是找回密码

        $user = DB::table('users')->where($account_type, $account)->first();

        if ($type == 'find') {
            if (!$user) {
                return $this->response->error('用户未注册', 500);
            }
        } else if ($type == 'register' || $type == 'bind') {
            if ($user) {
                return $this->response->error('该手机号或邮箱已注册', 500);
            }
        }

        $code = rand(1000, 9999);
        DB::table('verify_code')->insert([
            'type' => $type,
            'send_type' => $account_type,
            'send_object' => $account,
            'code' => $code,
            'create_time' => time(),
            'expire_time' => time() + 600,
        ]);

        if ($account_type == 'email') {
            $email = new MyEmail();
            $email->sendToSingleUser($account, '邮箱验证', 'app_verify_template', ['%code%' => [$code]]);
        } else {

            $config = [
                // HTTP 请求的超时时间（秒）
                'timeout' => 5.0,

                // 默认发送配置
                'default' => [
                    // 网关调用策略，默认：顺序调用
                    'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

                    // 默认可用的发送网关
                    'gateways' => [
                        'aliyun',
                    ],
                ],
                // 可用的网关配置
                'gateways' => [
                    'errorlog' => [
                        'file' => '/tmp/easy-sms.log',
                    ],
                    'aliyun' => [
                        'access_key_id' => 'LTAI6heBxZmwiSKp',
                        'access_key_secret' => 'YPYhxaddcNCNAWiyKsF57J78N5GqG3',
                        'sign_name' => '水滴打卡',
                    ],
                ],
            ];

            $easySms = new EasySms($config);


            try {

                $easySms->send($account, [
                    'content' => '您的验证码为: ' . $code,
                    'template' => 'SMS_96460074',
                    'data' => [
                        'code' => $code
                    ],
                ]);

            } catch (Exception $e) {
                Log::debug($e->result);
            }


        }

        return $this->response->noContent();
    }

    /**
     * 找回密码
     */
    public function find(Request $request)
    {
        $messages = [
            'required' => '缺少参数 :attribute',
        ];

        $validation = Validator::make(Input::all(), [
            'account' => 'required',     //  对象类型
            'code' => 'required',        // 验证码
            'password' => 'required',     // 新密码
        ], $messages);

        if ($validation->fails()) {
            return $this->response->error(implode($validation->errors(), ','), 500);
        }

        $account = $request->account;

        $account_type = '';

        if (preg_match("/^1[34578]\d{9}$/", $account)) {
            $account_type = 'phone';
        } else if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            $account_type = 'email';
        }

        // 根据验证码和邮箱查找
        $record = DB::table('verify_code')
            ->where('send_object', $account)
            ->where('send_type', $account_type)
            ->where('type', 'find')
            ->where('code', $request->code)
            ->orderBy('create_time', 'desc')
            ->first();

        // 判断

        if (!$record) {
            return $this->response->error('无效的验证码', 500);
        }

        if ($record->status == 1) {
            return $this->response->error('验证码已使用', 500);
        }

        if ($record->expire_time < time()) {
            return $this->response->error('验证码已过期', 500);
        }

        // 进入修改密码阶段
        $user = DB::table('users')->where($account_type, $account)->first();

        if (!$user) {
            return $this->response->error('手机号／邮箱未注册', 500);
        }

        $new_password = md5($request->password . $user->salt);

        DB::table('users')
            ->where('user_id', $user->id)
            ->update(['passwd' => $new_password]);

        // 修改验证码状态
        DB::table('verify_code')
            ->where('id', $record->id)
            ->update(['status' => 1, 'validate_time' => time()]);

        $this->response->noContent();
    }


}