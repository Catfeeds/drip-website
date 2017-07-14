<?php
namespace App\Console\Commands;

use App\Libs\MyJpush;
use Illuminate\Console\Command;
use DB;
use Jpush;

use App\Goal;
use App\Models\Device as Device;


class GoalRemind extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goal:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '目标提醒(根据用户设置)';

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
        $goals = DB::table('user_goal')
            ->join('users', 'users.user_id', '=', 'user_goal.user_id')
            ->join('goal', 'goal.goal_id', '=', 'user_goal.goal_id')
            ->where('user_goal.is_del','=',0)
            ->where('remind_time','=',date('H:i').':00')
            ->select('users.*', 'goal.goal_name','user_goal.*')
            ->get();

        // $queries = DB::getQueryLog();
        // $last_query = end($queries);
        // var_dump($last_query);

        $bar = $this->output->createProgressBar(count($goals));


        foreach ($goals as $goal) {

            $diff_days = ceil((time()-$goal->start_time)/86400);
            $content = $goal->goal_name."第".$diff_days."天";

            $jpush = new MyJpush();
            $jpush->pushToSingleUser($goal->user_id,$content);

            $bar->advance();

        }

        $bar->finish();

        
                
    }
}