<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

use App\Libs\MyJpush;

use App\Goal;
use App\Like;
use App\Event;
use App\User;
use App\Models\Device as Device;
use App\Models\Message as Message;


class EventLike extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:like';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'auto give like for event';

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
//         DB::connection()->enableQueryLog();

        // 获取需要点赞的动态
        // 5分钟以内的
        $events = DB::table('events')
            ->where('create_time','<=',time())
            ->where('create_time','>=',time()-300)
            // ->whereRaw("from_unixtime(create_time,'%Y-%m-%d')='".date('Y-m-d')."'")
            ->orderByRaw("RAND()")
            ->take(rand(1, 5))
            ->get();

//         $queries = DB::getQueryLog();
//         $last_query = end($queries);
//         var_dump($last_query);
        $bar = $this->output->createProgressBar(count($events));

        foreach ($events as $key => $event) {
            // 随机取出一些最近未登陆过的用户
            $users =  DB::table('users')
                        ->where('user_id','<>',$event->user_id)
                        ->whereNotNull('nickname')
                        ->orderByRaw("RAND()")
                        ->take(rand(1, 5))
                        ->get();

            $bar2 = $this->output->createProgressBar(count($users));

            foreach ($users as $key => $user) {

                // 插入到点赞列表
                $like = new Like();
                $like->user_id = $user->user_id;
                $like->event_id = $event->event_id;
                $like->create_time = time();
                $like->save();

                // 更新event 表
                $event2 = Event::find($event->event_id);
                $event2->like_count += 1;
                $event2->save();

                // 更新User表
                $user2 = User::find($user->user_id);
                $user2->like_count += 1;
                $user2->save();

                 // 给用户发送message
                $message = new Message();
                $message->from_user = $user->user_id;
                $message->to_user = $event->user_id;
                $message->type = 2;
                $message->msgable_id = $like->like_id;
                $message->msgable_type = 'App\Like';
                $message->status = 0;
                $message->create_time = time();
                $message->update_time = time();
                $message->save();

                $content = $user->nickname." 鼓励了你";
                $jpush = new MyJpush();
                $jpush->pushToSingleUser($event->user_id,$content);

                $bar2->advance();
            }
            $bar2->finish();

            $bar->advance();
        }

        $bar->finish();

    }
}