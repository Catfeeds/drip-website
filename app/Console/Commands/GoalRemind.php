<?php
namespace App\Console\Commands;

use App\Libs\MyJpush;
use Illuminate\Console\Command;
use DB;
use Jpush;
use App\Models\UserGoal;

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
         DB::connection()->enableQueryLog();

         $user_goals = UserGoal::whereRaw('FIND_IN_SET(?,remind_time)', [date('H:m')])
                        ->get();

        $bar = $this->output->createProgressBar(count($user_goals));

        foreach ($user_goals as $user_goal) {

            $content = "打卡提醒: ".$user_goal->name;

            $jpush = new MyJpush();
            $jpush->pushToSingleUser($user_goal->user_id,$content);

            $bar->advance();

        }

        $bar->finish();

        
                
    }
}