<?php

namespace App\Console\Commands;

use App\Jobs\ProcessRealTimeCampaignsJob;
use App\Jobs\CheckRiskManagementJob;
use Illuminate\Console\Command;

class ProcessRealTimeSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:process-realtime
                          {--campaigns : Process real-time campaigns only}
                          {--risk : Check risk management only}
                          {--user= : Check specific user ID for risk management}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process real-time campaigns and risk management';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting real-time system processing...');

        $campaigns = $this->option('campaigns');
        $risk = $this->option('risk');
        $userId = $this->option('user');

        // If no specific option, run both
        if (!$campaigns && !$risk) {
            $campaigns = true;
            $risk = true;
        }

        if ($campaigns) {
            $this->info('Dispatching real-time campaigns job...');
            ProcessRealTimeCampaignsJob::dispatch();
        }

        if ($risk) {
            if ($userId) {
                $this->info("Dispatching risk management job for user {$userId}...");
                CheckRiskManagementJob::dispatch((int) $userId);
            } else {
                $this->info('Dispatching risk management job for all users...');
                CheckRiskManagementJob::dispatch();
            }
        }

        $this->info('Real-time system processing jobs dispatched successfully!');

        return Command::SUCCESS;
    }
}
