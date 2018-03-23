<?php
/**
 * Created by PhpStorm.
 * User: Jason.z
 * Date: 16/10/24
 * Time: 上午10:24
 */

namespace App\Console\Commands;

use App\Libs\MyJpush;
use Illuminate\Console\Command;
use DB;
use Jpush;

use App\Goal;
use App\Models\Device as Device;


class GoalRemind2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goal:remind2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '目标提醒(根据系统设置)';

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
        // DB::connection()->enableQueryLog();

        // 获取所有需要推送的目标
        // 7 天 14天 30天各推送一次
        $users = DB::table('user_goal')
            ->join('users', 'users.user_id', '=', 'user_goal.user_id')
            ->where('user_goal.is_del','=',0)
            ->where('user_goal.status','=',0)
            ->where('user_goal.last_checkin_time','<',strtotime('-6 days'))
            ->groupBy('user_goal.user_id')
            ->get();

        // $queries = DB::getQueryLog();
        // $last_query = end($queries);
        // var_dump($last_query);

        $bar = $this->output->createProgressBar(count($users));

        foreach ($users as $goal) {

            $content = "新的一天又开始了,你有几个目标已经超过7天未打卡,赶快安排一下吧~";

            $jpush = new MyJpush();
            $jpush->pushToSingleUser($goal->user_id,$content);

            $bar->advance();

        }

        $bar->finish();



    }
}