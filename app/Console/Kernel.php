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
        /** Cap nhat ket qua moi cho cac ngay moiw */
        //$schedule->command('lottery:import-api',[3])->dailyAt('19:00');
        //lottery:check-formulas --partial --max-formula-batch=800 --start-date=2025-04-10 --days=4

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
