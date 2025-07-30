<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Scheduling\Event;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('notify:upcoming-games')->everyTenMinutes();
        $schedule->command('update:winner-amounts')->dailyAt('23:59');
        
        // Add macro for everyFiveSeconds
        // Event::macro('everyFiveSeconds', function () {
        //     return $this->spliceIntoPosition(1, '/5 * * * * *'); // Every 5 seconds
        // });

        // Now schedule your custom command
        $schedule->command('push:numbers');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
