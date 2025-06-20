<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CampaignSchedulerService;

/**
 * Micro-task 2.2.1.3: Status transition logic (3h)
 * Console command to process campaign scheduling and lifecycle management
 */
class ProcessCampaignScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:schedule
                          {--dry-run : Run without making actual changes}
                          {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process campaign scheduling, auto-start, auto-stop, and lifecycle management';

    protected CampaignSchedulerService $schedulerService;

    /**
     * Create a new command instance.
     */
    public function __construct(CampaignSchedulerService $schedulerService)
    {
        parent::__construct();
        $this->schedulerService = $schedulerService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isVerbose = $this->option('verbose');

        $this->info('🚀 Starting Campaign Scheduler...');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $startTime = microtime(true);

        try {
            // Get scheduling status first
            $status = $this->schedulerService->getSchedulingStatus();

            if ($isVerbose) {
                $this->displaySchedulingStatus($status);
            }

            if (!$isDryRun) {
                // Process scheduled campaigns
                $results = $this->schedulerService->processScheduledCampaigns();

                // Process campaigns needing attention
                $attentionProcessed = $this->schedulerService->processCampaignsNeedingAttention();
                $results['attention_processed'] = $attentionProcessed;

                // Display results
                $this->displayResults($results);

                // Show any errors
                if (!empty($results['errors'])) {
                    $this->error('❌ Errors encountered:');
                    foreach ($results['errors'] as $error) {
                        $this->line("   • {$error}");
                    }
                }
            } else {
                $this->info('📊 Dry run completed - would have processed:');
                $this->line("   • {$status['pending_starts']} campaigns ready to start");
                $this->line("   • {$status['pending_stops']} campaigns ready to stop");
                $this->line("   • {$status['needing_attention']} campaigns needing attention");
            }

            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);

            $this->info("✅ Campaign Scheduler completed in {$executionTime}ms");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Campaign Scheduler failed: {$e->getMessage()}");

            if ($isVerbose) {
                $this->error("Stack trace:");
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * Display current scheduling status
     */
    protected function displaySchedulingStatus(array $status): void
    {
        $this->info('📊 Current Scheduling Status:');
        $this->table(
            ['Category', 'Count', 'Next Action'],
            [
                [
                    'Pending Starts',
                    $status['pending_starts'],
                    $status['next_scheduled_start'] ?
                        $status['next_scheduled_start']->format('Y-m-d H:i:s') :
                        'None scheduled'
                ],
                [
                    'Pending Stops',
                    $status['pending_stops'],
                    $status['next_scheduled_stop'] ?
                        $status['next_scheduled_stop']->format('Y-m-d H:i:s') :
                        'None scheduled'
                ],
                [
                    'Needing Attention',
                    $status['needing_attention'],
                    $status['needing_attention'] > 0 ? 'Process immediately' : 'None'
                ]
            ]
        );
    }

    /**
     * Display processing results
     */
    protected function displayResults(array $results): void
    {
        $this->info('📈 Processing Results:');

        $totalProcessed = $results['campaigns_started'] +
                         $results['campaigns_stopped'] +
                         $results['sub_campaigns_started'] +
                         $results['sub_campaigns_stopped'] +
                         ($results['attention_processed'] ?? 0);

        if ($totalProcessed === 0) {
            $this->line('   • No campaigns required processing');
            return;
        }

        $this->table(
            ['Action', 'Count', 'Type'],
            [
                ['Started', $results['campaigns_started'], 'Campaigns'],
                ['Stopped', $results['campaigns_stopped'], 'Campaigns'],
                ['Started', $results['sub_campaigns_started'], 'Sub-Campaigns'],
                ['Stopped', $results['sub_campaigns_stopped'], 'Sub-Campaigns'],
                ['Attention Processed', $results['attention_processed'] ?? 0, 'Campaigns']
            ]
        );

        // Highlight significant actions
        if ($results['campaigns_started'] > 0) {
            $this->line("🚀 <info>{$results['campaigns_started']} campaigns automatically started</info>");
        }

        if ($results['campaigns_stopped'] > 0) {
            $this->line("🛑 <comment>{$results['campaigns_stopped']} campaigns automatically stopped</comment>");
        }

        if (($results['attention_processed'] ?? 0) > 0) {
            $this->line("⚠️  <comment>{$results['attention_processed']} campaigns processed for attention</comment>");
        }
    }
}
