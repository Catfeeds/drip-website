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
        $user_goals = UserGoal::where('end_date','<=',$yesterday)
            ->whereNotNull('end_date')
            ->get();

        foreach($user_goals as $user_goal) {
            $user_goal->status = 2;
            $user_goal->save();
        }

        // 修改开始目标的状态
        $user_goals = UserGoal::where('start_date','=',date('Y-m-d'))
            ->get();

        foreach($user_goals as $user_goal) {
            $user_goal->status = 1;
            $user_goal->save();
        }

        // TODO 关闭30天未打卡的目标
//        $user_goals = UserGoal::where('end_date','<=',$yesterday)
//            ->whereNotNull('end_date')
//            ->get();
        // 给7天，14天，30天未打卡的用户发送推送

    }

    private function _send_messages()
    {
        //        foreach($user_goals as $user_goal) {
//            $user_goal->status = 1;
//            $user_goal->end_date = date('Y-m-d');
//            $user_goal->save();
//
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