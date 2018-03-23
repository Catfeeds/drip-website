<?php
namespace App\Console\Commands;

use App\Libs\MyJpush;
use Illuminate\Console\Command;
use DB;
use Jpush;
use App\Models\UserGoal;

use App\Goal;
use App\Models\Device as Device;


class DailyStat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stat:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每日数据汇总';

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

        $bar = $this->output->createProgressBar(8);

        $day = date('Y-m-d');

        DB::table('daily_stat')->where('day','=',$day)->delete();

        // 获取总注册人数
        $all_reg_num = DB::table('users')->count();
        $bar->advance();

        // 获取总目标数
        $all_goal_num = DB::table('user_goals')->count();
        $bar->advance();

        // 获取总打卡次数
        $all_checkin_count = DB::table('checkins')->count();
        $bar->advance();

        // 获取新增注册人数
        $new_reg_num = DB::table('users')
            ->whereDate('created_at','=',$day)
            ->count();
        $bar->advance();

        // 获取登录人数
        $new_login_num = DB::table('users')
            ->whereDate('last_login_at','=',$day)
            ->count();
        $bar->advance();

        // 获取新增目标数
        $new_goal_count = DB::table('user_goals')
            ->whereDate('created_at','=',$day)
            ->count();
        $bar->advance();


        // 获取当日打卡人数
//        DB::connection()->enableQueryLog();

        $new_checkin_num = DB::table('checkins')
            ->distinct('user_id')
            ->whereDate('created_at','=',$day)
            ->count('user_id');
//       print_r(DB::getQueryLog());

        $bar->advance();

        // 获取当日打卡次数
        $new_checkin_count = DB::table('checkins')
            ->whereDate('created_at','=',$day)
            ->count();
        $bar->advance();

        $data = [
            'day'=>$day,
            'all_reg_num'=>$all_reg_num,
            'all_goal_num'=>$all_goal_num,
            'all_checkin_count'=>$all_checkin_count,
            'new_reg_num'=>$new_reg_num,
            'new_login_num'=>$new_login_num,
            'new_goal_count'=>$new_goal_count,
            'new_checkin_num'=>$new_checkin_num,
            'new_checkin_count'=>$new_checkin_count,
        ];

        DB::table('daily_stat')->insert($data);

        $bar->finish();

    }
}