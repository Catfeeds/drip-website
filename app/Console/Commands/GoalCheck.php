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

use App\Goal;
use App\Libs\MyJpush;
use App\Models\Message;
use App\Models\UserGoal;


class GoalCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goal:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查目标状态';

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
        $yesterday = date('Y-m-d',strtotime('-1 day'));

        // 修改到期目标的状态
        UserGoal::where('end_date','<=',$yesterday)
            ->where('status','=',1)
            ->whereNotNull('end_date')
            ->update(['status'=>2]);

        // 修改开始目标的状态
        UserGoal::where('start_date','=',date('Y-m-d'))
            ->where('status','=',0)
            ->update(['status'=>1]);

        // TODO 关闭30天未打卡的目标
//        UserGoal::whereDate('last_checkin_at','<',date('Y-m-d',strtotime('-1 month')))
//            ->where('status','=',1)
//            ->whereNotNull('last_checkin_at')
//            ->update(['status'=>2]);

//        UserGoal::where('start_date','<',date('Y-m-d',strtotime('-1 month')))
//            ->where('status','=',1)
//            ->whereNull('last_checkin_at')
//            ->update(['status'=>2]);

        // 给7天，14天，21天未打卡的用户发送推送
//        $user_goals = UserGoal::whereDate('last_checkin_at','=',date('Y-m-d',strtotime('-14 days')))
//            ->where('status','=',1)
//            ->whereNotNull('last_checkin_at')
//            ->get();
//
//        foreach ($user_goals as $user_goal) {
//            $this->_send_messages($user_goal,'目标打卡提醒','目标 '.$user_goal->goal_name.' 已经7天没有打卡了。');
//        }
//
//        $user_goals = UserGoal::where('start_date','<',date('Y-m-d',strtotime('-1 month')))
//            ->where('status','=',1)
//            ->whereNull('last_checkin_at')
//            ->get();

        // 给结束日期只有7天，3天，1天提醒
    }

    private function _send_messages($user_goal)
    {

//            $goal = Goal::find($user_goal->goal_id);
//
//            // 给用户发送message
//            $message = new Message();
//            $message->from_user = $user_goal->user_id;
//            $message->to_user = $user_goal->user_id;
//            $message->type = 6;
//            $message->msgable_id = $user_goal->goal_id;
//            $message->msgable_type = 'goal_expire';
//            $message->title = '目标过期通知' ;
//            $message->content = '你制定的目标"'+$goal->goal_name+'"由于30天未进行打卡，由系统自动关闭，你可以删除或重新制定该目标~<a href="#/goal/'.$user_goal->goal_id.'">点击查看详情</a>' ;
//            $message->status = 0;
//            $message->create_time = time();
//            $message->update_time = time();
//            $message->save();
//
//            $content = "目标过期通知";
//            $jpush = new MyJpush();
//            $jpush->pushToSingleUser($user_goal->user_id,$content);
    }
}