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


        //hàng ngày
        // php81 artisan heatmap:generate --from=2025-05-20 --to=2025-05-25
        //php artisan heatmap:analyze --date=2025-05-26

        //Check 5 phút 1 cầu
        // $schedule->command('lottery:check-formulas', ['--days' => 7500, '--start-date' => '2005-10-01', '--max-formula-batch' => 1])->everyTwoMinutes();

        //$schedule->job(new \App\Jobs\CampaignRunJobAll())->dailyAt('07:00');

        // Phase 2: Real-time system processing
        // Process real-time campaigns every 15 minutes during trading hours (9 AM - 6 PM)
        $schedule->command('system:process-realtime --campaigns')
                 ->weekdays()
                 ->between('9:00', '18:00')
                 ->everyFifteenMinutes();

        // Check risk management every 30 minutes during trading hours
        $schedule->command('system:process-realtime --risk')
                 ->weekdays()
                 ->between('9:00', '18:00')
                 ->everyThirtyMinutes();

        // Full risk management check once daily at 6 AM
        $schedule->command('system:process-realtime --risk')
                 ->dailyAt('06:00');

        // Process real-time campaigns once every hour outside trading hours
        $schedule->command('system:process-realtime --campaigns')
                 ->hourly()
                 ->when(function () {
                     $hour = now()->hour;
                     return $hour < 9 || $hour > 18;
                 });
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
