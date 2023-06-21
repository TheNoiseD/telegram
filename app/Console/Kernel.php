<?php

namespace App\Console;

use DateTimeZone;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{

    protected function schedule(Schedule $schedule):void
    {
         $schedule->command('check:absences')->dailyAt('0:00');
        $schedule->call(function () use ($schedule) {
            $currentDate = Carbon::now('America/New_York');
            $nextMonth = $currentDate->copy()->addMonth();
            $firstDayNextMonth = $nextMonth->firstOfMonth();
            $twoDaysBefore = $firstDayNextMonth->copy()->subDays(2);

            if (($currentDate->day == 14 || $currentDate->equalTo($twoDaysBefore)) && $currentDate->hour == 0 && $currentDate->minute == 0) {
                Artisan::call('payroll:run');
            }
        })
        ->daily()
        ->at('22:00')
        ->timezone('America/New_York');
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
