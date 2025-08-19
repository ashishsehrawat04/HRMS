<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
     protected $commands =[
         
           Commands\Attendance::class, 
           Commands\CheckLeaveStatus::class,
           Commands\Addleave::class,
           Commands\CheckAbsentStatus::class,  
           Commands\CheckProhibitionEndDate::class, 
           
    
     

     ];
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('attendance:command')->timezone('Asia/Kolkata')->cron('* * * * *');
        // $schedule->command('check_leave_status:command')->timezone('Asia/Kolkata')->cron('* * * * *');
        // $schedule->command('addleave:command')->timezone('Asia/Kolkata')->cron('0 0 1 * *');
        // $schedule->command('check-absent-status:command')->timezone('Asia/Kolkata')->cron('30 14 * * *');
        // $schedule->command('check-absent-status:command')->timezone('Asia/Kolkata')->cron('30 15 * * *');
        // $schedule->command('check-prohibition-end-date:command')->timezone('Asia/Kolkata')->dailyAt('19:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
