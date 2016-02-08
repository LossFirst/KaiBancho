<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Cache;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\Inspire::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            if(Cache::has('currentLogin'))
            {
                $currentUsers = cache::get('currentLogin');
                foreach($currentUsers as $key => $token)
                {
                    if(!cache::has($token))
                    {
                        unset($currentUsers[$key]);
                    }
                }
                cache::put('currentLogin', $currentUsers, 999);
            }
        })->everyMinute();
    }
}
