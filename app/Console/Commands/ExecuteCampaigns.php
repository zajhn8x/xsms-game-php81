<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CampaignExecutionService;
use App\Services\CampaignMonitoringService;
use App\Models\Campaign;
use Illuminate\Support\Facades\Log;

/**
 * Command to execute active campaigns
 * Part of micro-task 2.2.2: Campaign execution engine
 */
class ExecuteCampaigns extends Command
{
    protected $signature = 'campaigns:execute {--campaign=* : Specific campaign IDs to execute} {--dry-run : Run without placing actual bets}';
    protected $description = 'Execute active campaigns and place bets based on their strategies';

    protected CampaignExecutionService $executionService;
    protected CampaignMonitoringService $monitoringService;

    public function __construct(
        CampaignExecutionService $executionService,
        CampaignMonitoringService $monitoringService
    ) {
        parent::__construct();
        $this->executionService = $executionService;
        $this->monitoringService = $monitoringService;
    }

    public function handle(): int
    {
        $this->info('🚀 Bắt đầu thực thi campaigns...');

        $campaignIds = $this->option('campaign');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('⚠️ Chế độ dry-run: Không thực hiện đặt cược thật');
        }

        try {
            if (!empty($campaignIds)) {
                $results = $this->executeSpecificCampaigns($campaignIds, $isDryRun);
            } else {
                $results = $this->executeAllActiveCampaigns($isDryRun);
            }

            $this->displayResults($results);
            $this->monitorPerformance();

            $this->info('✅ Hoàn thành thực thi campaigns');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Lỗi thực thi campaigns: {$e->getMessage()}");
            Log::error('Campaign execution command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Execute specific campaigns
     */
    protected function executeSpecificCampaigns(array $campaignIds, bool $isDryRun): array
    {
        $this->info("🎯 Thực thi " . count($campaignIds) . " campaigns cụ thể...");

        $results = [
            'campaigns_processed' => 0,
            'sub_campaigns_processed' => 0,
            'bets_placed' => 0,
            'total_bet_amount' => 0,
            'errors' => []
        ];

        foreach ($campaignIds as $campaignId) {
            $campaign = Campaign::find($campaignId);

            if (!$campaign) {
                $this->warn("⚠️ Không tìm thấy campaign ID: {$campaignId}");
                continue;
            }

            $this->info("📊 Thực thi campaign: {$campaign->name} (ID: {$campaign->id})");

            if ($isDryRun) {
                $campaignResults = $this->simulateCampaignExecution($campaign);
            } else {
                $campaignResults = $this->executionService->executeCampaign($campaign);
            }

            $this->mergeCampaignResults($results, $campaignResults);
            $this->displayCampaignResults($campaign, $campaignResults);
        }

        return $results;
    }

    /**
     * Execute all active campaigns
     */
    protected function executeAllActiveCampaigns(bool $isDryRun): array
    {
        $this->info("🌟 Thực thi tất cả campaigns đang hoạt động...");

        if ($isDryRun) {
            return $this->simulateAllCampaignsExecution();
        } else {
            return $this->executionService->executeActiveCampaigns();
        }
    }

    /**
     * Simulate campaign execution for dry-run
     */
    protected function simulateCampaignExecution(Campaign $campaign): array
    {
        // Simulate execution without actual betting
        $subCampaignsCount = $campaign->subCampaigns()->where('status', 'active')->count();

        return [
            'sub_campaigns_processed' => $subCampaignsCount,
            'bets_placed' => rand(1, 5), // Simulate bet count
            'total_bet_amount' => rand(10000, 50000), // Simulate bet amount
            'errors' => []
        ];
    }

    /**
     * Simulate all campaigns execution
     */
    protected function simulateAllCampaignsExecution(): array
    {
        $activeCampaigns = Campaign::whereIn('status', ['active', 'running'])->get();

        $results = [
            'campaigns_processed' => $activeCampaigns->count(),
            'sub_campaigns_processed' => 0,
            'bets_placed' => 0,
            'total_bet_amount' => 0,
            'errors' => []
        ];

        foreach ($activeCampaigns as $campaign) {
            $campaignResults = $this->simulateCampaignExecution($campaign);
            $this->mergeCampaignResults($results, $campaignResults);
        }

        return $results;
    }

    /**
     * Merge campaign results into overall results
     */
    protected function mergeCampaignResults(array &$results, array $campaignResults): void
    {
        $results['campaigns_processed']++;
        $results['sub_campaigns_processed'] += $campaignResults['sub_campaigns_processed'];
        $results['bets_placed'] += $campaignResults['bets_placed'];
        $results['total_bet_amount'] += $campaignResults['total_bet_amount'];
        $results['errors'] = array_merge($results['errors'], $campaignResults['errors']);
    }

    /**
     * Display campaign execution results
     */
    protected function displayCampaignResults(Campaign $campaign, array $results): void
    {
        $this->line("  └─ Sub-campaigns: {$results['sub_campaigns_processed']}");
        $this->line("  └─ Bets placed: {$results['bets_placed']}");
        $this->line("  └─ Total bet amount: " . number_format($results['total_bet_amount']) . " VNĐ");

        if (!empty($results['errors'])) {
            $this->warn("  └─ Errors: " . count($results['errors']));
            foreach ($results['errors'] as $error) {
                $this->warn("    • {$error}");
            }
        }
        $this->newLine();
    }

    /**
     * Display overall results
     */
    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('📈 KẾT QUẢ TỔNG KẾT:');
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $this->table([
            'Metric', 'Value'
        ], [
            ['Campaigns processed', $results['campaigns_processed']],
            ['Sub-campaigns processed', $results['sub_campaigns_processed']],
            ['Bets placed', $results['bets_placed']],
            ['Total bet amount', number_format($results['total_bet_amount']) . ' VNĐ'],
            ['Errors', count($results['errors'])],
            ['Execution time', ($results['execution_time'] ?? 0) . ' ms']
        ]);

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error('❌ ERRORS:');
            foreach ($results['errors'] as $error) {
                $this->error("  • {$error}");
            }
        }
    }

    /**
     * Monitor campaign performance after execution
     */
    protected function monitorPerformance(): void
    {
        $this->info('🔍 Kiểm tra hiệu suất campaigns...');

        $activeCampaigns = Campaign::whereIn('status', ['active', 'running'])->get();
        $alertsCount = 0;

        foreach ($activeCampaigns as $campaign) {
            $alerts = $this->monitoringService->monitorCampaignPerformance($campaign);

            if (!empty($alerts)) {
                $alertsCount += count($alerts);
                $this->displayCampaignAlerts($campaign, $alerts);
            }
        }

        if ($alertsCount === 0) {
            $this->info('✅ Không có cảnh báo nào được phát hiện');
        } else {
            $this->warn("⚠️ Phát hiện {$alertsCount} cảnh báo");
        }
    }

    /**
     * Display campaign alerts
     */
    protected function displayCampaignAlerts(Campaign $campaign, array $alerts): void
    {
        $this->warn("⚠️ Alerts for campaign: {$campaign->name}");

        foreach ($alerts as $alert) {
            $icon = $this->getAlertIcon($alert['type']);
            $this->line("  {$icon} [{$alert['type']}] {$alert['message']}");

            if ($alert['action_required'] === 'auto_stop') {
                $this->error("    🛑 Action required: AUTO STOP");
            } elseif ($alert['action_required'] === 'urgent_review') {
                $this->warn("    ⚡ Action required: URGENT REVIEW");
            }
        }
        $this->newLine();
    }

    /**
     * Get alert icon based on type
     */
    protected function getAlertIcon(string $type): string
    {
        return match($type) {
            'critical' => '🔴',
            'warning' => '🟡',
            'success' => '🟢',
            'info' => '🔵',
            default => '⚪'
        };
    }
}
