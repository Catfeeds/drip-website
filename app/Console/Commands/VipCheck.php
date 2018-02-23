<?php
/**
 * Created by PhpStorm.
 * User: Jason.z
 * Date: 16/10/24
 * Time: 上午10:01
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

use App\User;
use App\Libs\MyJpush;
use App\Models\Message as Message;


class VipCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vip:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查会员状态';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 更改过期的VIP
        User::where('is_vip','=','1')
            ->where('vip_end_date','<',date('Y-m-d'))
            ->update(['is_vip' => 0]);

        // 获取还有3天过期的用户
        $users = User::where('is_vip','=','1')
            ->where('vip_end_date','=',date('Y-m-d',strtotime('-2 day')))
            ->get();
        $this->_send_messages($users,3);

        // 获取还有7天过期的用户
        $users = User::where('is_vip','=','1')
            ->where('vip_end_date','=',date('Y-m-d',strtotime('-6 day')))
            ->get();
        $this->_send_messages($users,7);
    }

    private function _send_messages($users,$day) {
        $bar = $this->output->createProgressBar(count($users));

        foreach($users as $user) {
            $bar->advance();
            // 给用户发送message
            $message = new Message();
            $message->from_user = 0;
            $message->to_user = $user->id;
            $message->type = 6;
            $message->msgable_id = $user->id;
            $message->msgable_type = 'vip_expire';
            $message->title = '会员到期提醒' ;
            $message->content = '你的PRO会员即将于'.$day.'天后到期，请及时续费以免影响你的使用。';
            $message->status = 0;
            $message->save();

            $content = "会员到期提醒";
            $jpush = new MyJpush();
            $jpush->pushToSingleUser($user->id,$content);
        }

        $bar->finish();
    }
}