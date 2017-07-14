<?php namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountController extends Controller {

    public function index()
    {
        return view('login');
    }
    /**
     * 显示所给定的用户个人数据。
     *
     * @param  int  $id
     * @return Response
     */
    public function login()
    {
        return view('login');
    }


    /**
     * 忘记密码
     */
    public function forget()
    {
        return view('forget');
    }

    /**
     * 发送找回密码的邮件
     */
    public function send_findpwd_email(Request $request)
    {
        $validation = Validator::make(Input::all(), [
            'email'		=>	'required',     // 邮箱
        ]);

        if($validation->fails()){
            return ['status'=>false,'message'=>$validation->errors()];
        }

        $email = $request->input('email');
    }

    /**
     * 重置密码
     */
    public function reset(Request $request)
    {
        $code = $request->code;

        // 查询code

        return view('reset',$data);
    }

}