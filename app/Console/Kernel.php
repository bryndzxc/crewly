<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('crewly:generate-notifications')->dailyAt('08:05');
        $schedule->command('demo:cleanup')->daily()->withoutOverlapping();

        if (config('crewly.demo.purge_enabled', false)) {
            $schedule->command('demo:cleanup --purge --force')->daily()->withoutOverlapping();
        }
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
