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
        /**
         * $schedule->command('campaigns:process-results')->dailyAt('19:00');
         *
         */
        //Check 5 phút 1 cầu

        $schedule->command('lottery:check-formulas', ['--days' => 7500, '--start-date' => '2005-10-01', '--max-formula-batch' => 1])->everyTwoMinutes();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {

        $this->load(__DIR__ . '/Commands');
        /**
        $this->commands([
            Commands\ImportLotteryFromApi::class,
            Commands\RunCampaignSimulation::class, // Added this line
        ]);
         * */
        require base_path('routes/console.php');
    }
}
