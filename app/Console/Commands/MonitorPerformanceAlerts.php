<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\PerformanceAlertService;
use App\Jobs\ProcessPerformanceAlertsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Micro-task 2.3.1.3: Performance alerts (3h)
 * Command to monitor and process performance alerts for campaigns
 */
class MonitorPerformanceAlerts extends Command
{
    /**
     * The name and signature of the console command.
     */
        protected $signature = 'alerts:monitor-performance
                           {--campaign-id=* : Specific campaign IDs to monitor}
                           {--force : Force monitoring even if disabled}
                           {--async : Process alerts asynchronously via jobs}
                           {--detail : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor campaign performance and trigger alerts for poor performing campaigns';

    protected PerformanceAlertService $alertService;

    public function __construct(PerformanceAlertService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        $this->info('🚨 Bắt đầu kiểm tra Performance Alerts...');
        $this->newLine();

        // Check if alerts are enabled
        if (!config('xsmb.performance_alerts.enabled', true) && !$this->option('force')) {
            $this->warn('⚠️ Performance alerts đã bị tắt. Sử dụng --force để bỏ qua.');
            return Command::FAILURE;
        }

        try {
            $campaigns = $this->getCampaignsToMonitor();

            if ($campaigns->isEmpty()) {
                $this->info('✅ Không có campaigns nào cần giám sát.');
                return Command::SUCCESS;
            }

            $this->info("📊 Tìm thấy {$campaigns->count()} campaigns cần giám sát:");
            $this->newLine();

            $results = [
                'campaigns_processed' => 0,
                'total_alerts' => 0,
                'critical_alerts' => 0,
                'warning_alerts' => 0,
                'auto_stopped_campaigns' => 0,
                'flagged_for_review' => 0,
                'errors' => []
            ];

            // Process each campaign
            foreach ($campaigns as $campaign) {
                try {
                    $this->processCampaignAlerts($campaign, $results);
                } catch (\Exception $e) {
                    $results['errors'][] = "Campaign {$campaign->id}: {$e->getMessage()}";
                    $this->error("❌ Lỗi xử lý campaign {$campaign->id}: {$e->getMessage()}");
                }
            }

            // Display summary
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->displaySummary($results, $executionTime);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Lỗi trong quá trình giám sát: {$e->getMessage()}");
            Log::error("Performance alerts monitoring failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Get campaigns to monitor
     */
    protected function getCampaignsToMonitor()
    {
        $query = Campaign::whereIn('status', ['active', 'running']);

        // Filter by specific campaign IDs if provided
        if ($campaignIds = $this->option('campaign-id')) {
            $query->whereIn('id', $campaignIds);
        }

        // Only monitor campaigns with recent activity (last 24 hours)
        $query->where(function ($q) {
            $q->where('updated_at', '>=', now()->subHours(24))
              ->orWhereHas('bets', function ($betQuery) {
                  $betQuery->where('created_at', '>=', now()->subHours(24));
              });
        });

        return $query->with(['user', 'bets' => function($query) {
            $query->latest()->limit(50);
        }])->get();
    }

    /**
     * Process alerts for a single campaign
     */
    protected function processCampaignAlerts(Campaign $campaign, array &$results): void
    {
        $results['campaigns_processed']++;

        if ($this->option('detail')) {
            $this->line("🔍 Kiểm tra campaign: {$campaign->name} (ID: {$campaign->id})");
        }

        if ($this->option('async')) {
            // Process via job queue
            ProcessPerformanceAlertsJob::dispatch($campaign, [
                'source' => 'command',
                'auto_stop_enabled' => true
            ]);

            if ($this->option('detail')) {
                $this->line("  📤 Đã gửi job xử lý alerts");
            }

            return;
        }

        // Process synchronously
        $alerts = $this->alertService->monitorCampaignPerformance($campaign);

        if (empty($alerts)) {
            if ($this->option('detail')) {
                $this->line("  ✅ Không có alerts");
            }
            return;
        }

        // Count alerts by type
        $criticalAlerts = array_filter($alerts, fn($alert) => $alert['type'] === 'critical');
        $warningAlerts = array_filter($alerts, fn($alert) => $alert['type'] === 'warning');

        $results['total_alerts'] += count($alerts);
        $results['critical_alerts'] += count($criticalAlerts);
        $results['warning_alerts'] += count($warningAlerts);

        // Display alerts
        $this->displayCampaignAlerts($campaign, $alerts);

        // Handle automatic actions
        $this->handleAutomaticActions($campaign, $criticalAlerts, $results);
    }

    /**
     * Display alerts for a campaign
     */
    protected function displayCampaignAlerts(Campaign $campaign, array $alerts): void
    {
        $userName = $campaign->user ? $campaign->user->name : 'Unknown User';
        $this->warn("⚠️ Campaign: {$campaign->name} - {$userName}");

        foreach ($alerts as $alert) {
            $icon = $this->getAlertIcon($alert['type']);
            $priority = isset($alert['priority']) ? strtoupper($alert['priority']) : 'MEDIUM';

            $this->line("  {$icon} [{$alert['type']}] {$alert['title']} - {$alert['message']}");

            if (isset($alert['value']) && isset($alert['threshold'])) {
                $this->line("    📊 Giá trị: {$alert['value']} (Ngưỡng: {$alert['threshold']})");
            }

            $actionRequired = isset($alert['action_required']) ? $alert['action_required'] : 'none';
            if ($actionRequired !== 'none') {
                $this->line("    🎯 Action: {$actionRequired}");
            }
        }
        $this->newLine();
    }

    /**
     * Handle automatic actions for critical alerts
     */
    protected function handleAutomaticActions(Campaign $campaign, array $criticalAlerts, array &$results): void
    {
        foreach ($criticalAlerts as $alert) {
            $action = isset($alert['action_required']) ? $alert['action_required'] : null;

            switch ($action) {
                case 'auto_stop':
                    if ($this->shouldAutoStop($campaign, $alert)) {
                        $this->autoStopCampaign($campaign, $alert);
                        $results['auto_stopped_campaigns']++;
                    }
                    break;

                case 'urgent_review':
                    $this->flagForUrgentReview($campaign, $alert);
                    $results['flagged_for_review']++;
                    break;
            }
        }
    }

    /**
     * Check if campaign should be auto-stopped
     */
    protected function shouldAutoStop(Campaign $campaign, array $alert): bool
    {
        // Add additional safety checks here

        // Don't auto-stop if campaign was created recently (less than 1 hour)
        if ($campaign->created_at->diffInHours(now()) < 1) {
            $this->line("    ⏱️ Campaign quá mới, không auto-stop");
            return false;
        }

        // Don't auto-stop if campaign has very few bets
        $totalBets = $campaign->bets()->count();
        if ($totalBets < 5) {
            $this->line("    📊 Quá ít bets ({$totalBets}), không auto-stop");
            return false;
        }

        return true;
    }

    /**
     * Auto-stop campaign
     */
    protected function autoStopCampaign(Campaign $campaign, array $alert): void
    {
        try {
            $campaign->update([
                'status' => 'stopped',
                'stopped_reason' => 'auto_stop_alert',
                'stopped_at' => now()
            ]);

            // Stop sub-campaigns
            $campaign->subCampaigns()
                ->where('status', 'active')
                ->update([
                    'status' => 'stopped',
                    'stopped_reason' => 'parent_auto_stop',
                    'stopped_at' => now()
                ]);

            $this->error("    🛑 AUTO-STOPPED: {$alert['message']}");

            Log::warning("Campaign auto-stopped by monitoring command", [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'alert' => $alert
            ]);

        } catch (\Exception $e) {
            $this->error("    ❌ Lỗi auto-stop: {$e->getMessage()}");
        }
    }

    /**
     * Flag campaign for urgent review
     */
    protected function flagForUrgentReview(Campaign $campaign, array $alert): void
    {
        try {
            $campaign->update([
                'needs_urgent_review' => true,
                'urgent_review_reason' => $alert['message'],
                'urgent_review_at' => now()
            ]);

            $this->warn("    ⚡ FLAGGED FOR URGENT REVIEW: {$alert['message']}");

        } catch (\Exception $e) {
            $this->error("    ❌ Lỗi flag urgent review: {$e->getMessage()}");
        }
    }

    /**
     * Display summary of monitoring results
     */
    protected function displaySummary(array $results, float $executionTime): void
    {
        $this->newLine();
        $this->info('📈 KẾT QUẢ GIÁM SÁT PERFORMANCE ALERTS:');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->table([
            'Metric', 'Value'
        ], [
            ['Campaigns processed', $results['campaigns_processed']],
            ['Total alerts', $results['total_alerts']],
            ['Critical alerts', $results['critical_alerts']],
            ['Warning alerts', $results['warning_alerts']],
            ['Auto-stopped campaigns', $results['auto_stopped_campaigns']],
            ['Flagged for review', $results['flagged_for_review']],
            ['Errors', count($results['errors'])],
            ['Execution time', $executionTime . ' ms']
        ]);

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error('❌ ERRORS:');
            foreach ($results['errors'] as $error) {
                $this->error("  • {$error}");
            }
        }

        // Summary status
        if ($results['critical_alerts'] > 0) {
            $this->warn("⚠️ Phát hiện {$results['critical_alerts']} critical alerts cần xử lý ngay");
        } elseif ($results['warning_alerts'] > 0) {
            $this->info("ℹ️ Phát hiện {$results['warning_alerts']} warning alerts cần theo dõi");
        } else {
            $this->info('✅ Tất cả campaigns đang hoạt động bình thường');
        }
    }

    /**
     * Get alert icon based on type
     */
    protected function getAlertIcon(string $type): string
    {
        return match($type) {
            'critical' => '🔴',
            'warning' => '🟡',
            'info' => '🔵',
            default => '⚪'
        };
    }
}
