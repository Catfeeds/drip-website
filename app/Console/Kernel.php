<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
        Commands\GoalRemind::class,
        Commands\GoalRemind2::class,
        Commands\GoalCheck::class,
        Commands\EmailSend::class,  
        Commands\EventLike::class,  
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
//        $schedule->command('email:send')->everyMinute();
        $schedule->command('goal:remind')->everyMinute();
//        $schedule->command('event:like')->everyMinute();
//        $schedule->command('email:send')->dailyAt('23:59');
        $schedule->command('goal:check')->dailyAt('00:01');
        $schedule->command('goal:remind2')->dailyAt('22:01');
    }
}
