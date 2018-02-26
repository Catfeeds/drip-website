<?php

namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use JWTAuth;

use App\Models\Energy as Energy;


class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
//        'tymon.jwt.valid' => [
//            'App\Listeners\JwtValidListener',
//        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        $events->listen('tymon.jwt.valid', function () {
            $user = JWTAuth::parseToken()->toUser();

            // 判断是否是今日首次登录
            if(date('Y-m-d',strtotime($user->last_login_at))<date('Y-m-d')) {
                // 发送首次登录奖励
                $energy = new Energy();
                $energy->user_id = $user->user_id;
                $energy->change = 10;
                $energy->obj_type = 'login';
                $energy->obj_id = 0;
                $energy->create_time = time();
                $energy->save();

                $user->increment('energy_count',10);
            }

            $user->last_login_at = date('Y-m-d H:i:s');
            $user->save();

        });
    }
}
