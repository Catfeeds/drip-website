<?php
/**
 * 用户控制器
 */
namespace App\Http\Controllers\Api\V1;

use Auth;
use Validator;
use API;
use JWTAuth;
use Log;
use DB;

use App\User;
use App\Models\Device as Device;
use App\Libs\MyEmail as MyEmail;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;


class AuthController extends BaseController {

    /**
     * 登录接口
     */
    public function login(Request $request) {

		$messages = [
			'email.required' => '请输入邮箱地址',
			'email.email'    => '请输入一个合法的邮箱地址',
			'password.required' => '请输入密码',
			'password.between' => '密码长度需:min到:max位',
		];

		$validation = Validator::make(Input::all(), [
			'email'		=> 	'required|email',		// 邮箱
			'password' 	=> 	'required|between:6,12',		// 密码
			'device' 	=> 	'', 	// 设备
		],$messages);

		if($validation->fails()){
			return API::response()->array(['status' => false, 'message' => $validation->errors()->all('</br>:message')])->statusCode(200);
		}

        $credentials = $request->only('email', 'password');

        try {
            // attempt to verify the credentials and create a token for the user
            if (!$token = JWTAuth::attempt($credentials)) {
                // return response()->json(['error' => 'invalid_credentials'], 401);
                return $this->response->array(['status'=>false,'message' => '邮箱或密码不正确']);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return $this->response->array(['status'=>false,'message' => 'token获取失败']);
        }

       $user = Auth::user();

		$this->_insert_device($user->user_id,$request->input('device'),$request);

       // 处理设备信息
       return $this->response->array(array('status'=>true,'message'=>'登录成功','token'=>$token,'user'=>$user));

    }

	/**
	 * 第三方登录
	 * @param Request $request
	 * @return mixed
	 */
	public function oauth(Request $request) {

		Log::debug('第三方登录请求');
		Log::debug($request);

		$validation = Validator::make(Input::all(), [
			'provider'		=> 	'required',		// 邮箱
			'device' 	=> 	'', 	// 设备
		]);

		if($validation->fails()){
			return API::response()->array(['status' => false, 'message' => "参数非法"])->statusCode(200);
		}

		$providers = array('qq','weibo','xiaomi','weapp');

		if(in_array($provider = $request->input('provider'),$providers)) {
			// 整理参数
			$method = '_parse_'.$provider;
			$params = self::$method($request);

			// 查询openid 是否存在
			$provider = DB::table('users_bind')
					->where('openid',$params['openid'])
					->where('provider',$params['provider'])
					->first();

			if($provider) {
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
					'user_id'=>$user->user_id,
					'openid'=>$params['openid'],
					'access_token'=>$params['access_token'],
					'expire_in'=>$params['expire_in'],
					'avatar'=>$params['avatar'],
					'sex'=>$params['sex'],
					'province'=>$params['province'],
					'city'=>$params['city'],
					'nickname'=>$params['nickname'],
					'provider'=>$params['provider'],
				]);

			}

			// 插入设备
			$this->_insert_device($user->user_id,$request->input('device'),$request);

			// token
			$token = $user['email']?JWTAuth::fromUser($user):'';


			return $this->response->array(array('status'=>true,'message'=>'登录成功','user'=>$user,'token'=>$token));

		} else {
			return API::response()->array(['status' => false, 'message' => "不支持的登录方式"])->statusCode(200);
		}

	}

	public function bind(Request $request){
		Log::info('第三方登录请求');
		Log::info($request);

		$validation = Validator::make(Input::all(), [
			'email'			=>  'required',     // 邮箱
			'password'      =>  'required',     // 密码
			'user_id'		=> 	'required',		// 绑定Id
			'is_register'		=> 	'required',		// 绑定Id
		]);

		if($validation->fails()){
			return API::response()->array(['status' => false, 'message' => "参数非法"])->statusCode(200);
		}

		$user = User::find($request->user_id);

		if($user) {

			if($user->email) {
				return API::response()->array(['status' => false, 'message' => "你的账号已经绑定过邮箱,请勿重复绑定"])->statusCode(200);
			} else {

				if($request->is_register) {
					// 验证邮箱和密码是否正确
					$new_user =  DB::table('users')
						->where('email',$request->email)
						->first();

					if($new_user) {
						// 验证邮箱和密码是否正确
						$password = md5($request->password.$new_user->salt);

						if($password != $new_user->passwd) {
							return API::response()->array(['status' => false, 'message' => "密码错误"])->statusCode(200);
						} else {
							// 修改绑定信息
							DB::table('users_bind')
								->where('user_id', $user->user_id)
								->update(['user_id' => $new_user->user_id]);

							DB::table('devices')
								->where('user_id', $user->user_id)
								->update(['user_id' => $new_user->user_id]);

							$token = JWTAuth::fromUser($new_user);
							return $this->response->array(array('status'=> true,'message'=>'绑定成功','token'=>$token,'user'=>$new_user));
						}
					} else {
						return API::response()->array(['status' => false, 'message' => "邮箱地址未注册"])->statusCode(200);
					}
				} else {
					// 查找邮箱是否存在
					$is_exist = DB::table('users')
						->where('email',$request->email)
						->first();

					if($is_exist) {
						return API::response()->array(['status' => false, 'message' => "邮箱地址已注册"])->statusCode(200);
					} else {
						$salt = rand(100000, 999999);

						$user->email = $request->email;
						$user->passwd = md5($request->password.$salt);
						$user->salt = $salt;

						$user->save();

						$token = JWTAuth::fromUser($user);

						// 发送注册邮件
						$this->_send_register_email($request->email);

						return $this->response->array(array('status'=> true,'message'=>'绑定成功','token'=>$token,'user'=>$user));
					}
				}
			}
		} else {
			return API::response()->array(['status' => false, 'message' => "用户不存在"])->statusCode(200);
		}

	}

    public function register(Request $request) {

		$messages = [
			'email.required' => '请输入邮箱地址',
			'email.email'    => '请输入一个合法的邮箱地址',
			'password.required' => '请输入密码',
			'password.between' => '密码长度需:min到:max位',
		];

	    $validation = Validator::make(Input::all(), [
      		'email'		=> 	'required|email',		// 邮箱
	        'password' 	=> 	'required|between:6,12',		// 密码
	        'device' 	=> 	'', 	// 设备
    	],$messages);

	    if($validation->fails()){
	      return API::response()->array(['status' => false, 'message' => $validation->errors()->all('</br>:message')])->statusCode(200);
	    }

	    $email  = Input::get('email');
	    $password = Input::get('password');

	    // 查询用户是否存在
	    $user = User::where('email','=',$email)->first();

	    if($user) {
	    	 return API::response()->array(['status' => false, 'message' =>'邮箱已注册'])->statusCode(200);
	    }

	    $salt = rand(100000, 999999);

	    $user  = new User();
	    $user->passwd = md5($password.$salt);
	    $user->email = $email;
	    $user->salt = $salt;
		$user->reg_time = time();
		$user->reg_ip = $request->ip();

		$user->save();

		$this->_insert_device($user->user_id,$request->input('device'),$request);

	   	$token = JWTAuth::fromUser($user);

		// 发送注册邮件
		$this->_send_register_email($email);

 		// all good so return the token
        return $this->response->array(array('status'=> true,'message'=>'注册成功','token'=>$token,'user'=>$user));
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
	private function _send_register_email($email)
	{
		$myEmail = new MyEmail();
		$myEmail->sendToSingleUser($email,'欢迎来到水滴打卡','app_register_template');
	}

	/**
	 * 插入设备信息
	 * @param $user_id  用户ID
	 * @param $device_info 设备信息
	 */
    private function _insert_device($user_id,$device_info,$request)
    {
//    	Log::info('设备信息');
//    	Log::info($device_info);
		if($device_info) {
			if(isset($device_info['platform'])) {
				if($device_info['platform'] != 'Android' && $device_info['platform'] != 'iOS') {
					return;
				}
			}

			$device = Device::where('uuid','=',$device_info['uuid'])->first();

			if(!$device) {
				$device = new Device();
				$device->create_time = time();
			}

			$device->user_id = $user_id;
			$device->uuid = $device_info['uuid'];
			$device->device_version = $device_info['version'];
			$device->device_platform = $device_info['platform'];
			$device->device_model = $device_info['model'];
			$device->device_cordova = $device_info['cordova'];
			$device->push_id = isset($device_info['push_id'])?$device_info['push_id']:'';

			// 修改最后登录时间
			$device->update_time = time();
			$device->save();
		}

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
		$sex = 0;
		if($request->gender=='男'){
			$sex = 1;
		} else if($request->gender=='女') {
			$sex = 2;
		}

		return [
			'openid'=>$request->userid,
			'access_token'=>$request->access_token,
			'expire_in'=>$request->expires_time,
			'avatar'=>$request->figureurl_2,
			'sex'=>$sex,
			'province'=>$request->province,
			'city'=>$request->city,
			'nickname'=>$request->nickname,
			'provider'=>'qq',
			'device'=>$request->device
		];
	}

	private function _parse_weapp($request)
	{
		$sex = 0;
		if($request->gender=='男'){
			$sex = 1;
		} else if($request->gender=='女') {
			$sex = 2;
		}

		return [
			'openid'=>$request->userid,
			'access_token'=>$request->access_token,
			'expire_in'=>$request->expires_time,
			'avatar'=>$request->avatarUrl,
			'sex'=>$request->gender,
			'province'=>$request->province,
			'city'=>$request->city,
			'nickname'=>$request->nickName,
			'provider'=>'weapp',
			'device'=>$request->device
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
			'openid'=>$request->userId,
			'access_token'=>$request->access_token,
			'expire_in'=>$request->expires_in,
			'avatar'=>$request->miliaoIcon,
			'sex'=>$sex,
			'province'=>'',
			'city'=>'',
			'nickname'=>$request->miliaoNick,
			'provider'=>'xiaomi',
			'device'=>$request->device
		];
	}

	/**
	 * 解析微博参数
	 * @see http://open.weibo.com/wiki/2/users/show
	 */
	private function _parse_weibo($request)
	{
		$sex = 0;
		if($request->gender=='m'){
			$sex = 1;
		} else if($request->gender=='f') {
			$sex = 2;
		}
		return [
			'openid'=>$request->userid,
			'access_token'=>$request->access_token,
			'expire_in'=>$request->expires_time,
			'avatar'=>$request->avatar_hd,
			'sex'=>$sex,
			'province'=>$request->province,
			'city'=>$request->city,
			'nickname'=>$request->screen_name,
			'provider'=>'weibo',
			'device'=>$request->device
		];
	}

	/**
	 * 获取验证码
	 */
	public function get_verify_code(Request $request)
	{
		$messages = [
			'required' => '缺少参数 :attribute',
		];

		$validation = Validator::make(Input::all(), [
			'send_type'		=> 	'required',		// 发送方式
			'send_object'   =>  'required',     // 发送对象
			'type'          =>  'required',     // 用途
		],$messages);

		if($validation->fails()){
			return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
		}
		$type = $request->type;
		$send_type = $request->send_type;
		$send_object = $request->send_object;

		// 检查获取频率 60s 一次

		// 如果是找回密码

		if($type == 'find') {
			// 判断是否注册
			$user = DB::table('users')->where($send_type,$send_object)->first();

			if($user) {
				$code = rand(100000,999999);
				DB::table('verify_code')->insert([
					'type'=>'find',
					'send_type'=>$send_type,
					'send_object'=>$send_object,
					'code'=> $code,
					'create_time'=>time(),
					'expire_time'=>time()+600,
				]);

				$email = new MyEmail();
				$email->sendToSingleUser($send_object,'邮箱验证','app_verify_template',['%code%'=>[$code]]);

				return API::response()->array(['status' => true, 'message' => '发送成功'])->statusCode(200);

			} else {
				return API::response()->array(['status' => false, 'message' => '用户未注册'])->statusCode(200);
			}
		}
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
			'send_type'    =>  'required',     //  对象类型
			'send_object'    =>  'required',     //  对象
			'code'		=> 	'required',		// 验证码
			'password'   =>  'required',     // 新密码
		],$messages);

		if($validation->fails()){
			return API::response()->array(['status' => false, 'message' => $validation->errors()])->statusCode(200);
		}

		// 根据验证码和邮箱查找
		$record = DB::table('verify_code')
			->where('send_object',$request->send_object)
			->where('send_type',$request->send_type)
			->where('code',$request->code)
			->orderBy('create_time', 'desc')
			->first();

		// 判断

		if($record) {
			if($record->status==1) {
				return API::response()->array(['status' => false, 'message' => '验证码已使用'])->statusCode(200);
			}

			if($record->expire_time<time()) {
				return API::response()->array(['status' => false, 'message' => '验证码已过期'])->statusCode(200);
			}

			// 进入修改密码阶段
			$user = DB::table('users')->where($request->send_type,$request->send_object)->first();

			if($user) {

				$new_password = md5($request->password.$user->salt);

				DB::table('users')
					->where('user_id', $user->user_id)
					->update(['passwd' => $new_password]);

				// 修改验证码状态
				DB::table('verify_code')
					->where('id', $record->id)
					->update(['status' => 1,'validate_time'=>time()]);

				return API::response()->array(['status' => true, 'message' => '修改成功'])->statusCode(200);

			} else {
				return API::response()->array(['status' => false, 'message' => '用户未注册'])->statusCode(200);
			}

		} else {
			return API::response()->array(['status' => false, 'message' => '无效的验证码'])->statusCode(200);
		}

	}
}