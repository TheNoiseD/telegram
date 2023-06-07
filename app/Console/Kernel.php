<?php

namespace App\Console;

use DateTimeZone;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    protected function schedule(Schedule $schedule):void
    {
         $schedule->command('check:absences')->dailyAt('00:00');
    }

    protected function commands():void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }

    protected function scheduleTimezone(): DateTimeZone|string|null
    {
        return 'America/New_York';
    }
}
