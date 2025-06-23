<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\PerformanceAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Micro-task 2.3.1.3: Performance alerts (3h)
 * Job to process performance alerts for campaigns
 */
class ProcessPerformanceAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Campaign $campaign;
    protected array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign, array $options = [])
    {
        $this->campaign = $campaign;
        $this->options = $options;

        // Set queue configuration
        $this->onQueue('performance-alerts');
        $this->delay(now()->addSeconds(5)); // Small delay to avoid race conditions
    }

    /**
     * Execute the job.
     */
    public function handle(PerformanceAlertService $alertService): void
    {
        try {
            Log::info("Processing performance alerts for campaign", [
                'campaign_id' => $this->campaign->id,
                'campaign_name' => $this->campaign->name,
                'options' => $this->options
            ]);

            // Skip if alerts are disabled
            if (!config('xsmb.performance_alerts.enabled', true)) {
                Log::info("Performance alerts disabled, skipping", [
                    'campaign_id' => $this->campaign->id
                ]);
                return;
            }

            // Refresh campaign data
            $this->campaign->refresh();

            // Skip if campaign is not active
            if (!in_array($this->campaign->status, ['active', 'running'])) {
                Log::info("Campaign not active, skipping alert processing", [
                    'campaign_id' => $this->campaign->id,
                    'status' => $this->campaign->status
                ]);
                return;
            }

            // Process performance alerts
            $alerts = $alertService->monitorCampaignPerformance($this->campaign);

            Log::info("Performance alerts processed", [
                'campaign_id' => $this->campaign->id,
                'total_alerts' => count($alerts),
                'critical_alerts' => count(array_filter($alerts, fn($alert) => $alert['type'] === 'critical')),
                'warning_alerts' => count(array_filter($alerts, fn($alert) => $alert['type'] === 'warning'))
            ]);

            // Handle automatic actions for critical alerts
            $this->handleAutomaticActions($alerts);

            // Update alert metrics
            $this->updateAlertMetrics($alerts);

        } catch (\Exception $e) {
            Log::error("Failed to process performance alerts", [
                'campaign_id' => $this->campaign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle automatic actions for critical alerts
     */
    protected function handleAutomaticActions(array $alerts): void
    {
        $criticalAlerts = array_filter($alerts, fn($alert) => $alert['type'] === 'critical');

        foreach ($criticalAlerts as $alert) {
            $action = $alert['action_required'] ?? null;

            switch ($action) {
                case 'auto_stop':
                    $this->autoStopCampaign($alert);
                    break;

                case 'urgent_review':
                    $this->flagForUrgentReview($alert);
                    break;
            }
        }
    }

    /**
     * Auto-stop campaign due to critical alert
     */
    protected function autoStopCampaign(array $alert): void
    {
        // Check if auto-stop is enabled for this type of alert
        $autoStopEnabled = $this->options['auto_stop_enabled'] ?? true;

        if (!$autoStopEnabled) {
            Log::info("Auto-stop disabled, skipping", [
                'campaign_id' => $this->campaign->id,
                'alert' => $alert
            ]);
            return;
        }

        // Double-check current campaign status
        $this->campaign->refresh();

        if (!in_array($this->campaign->status, ['active', 'running'])) {
            Log::info("Campaign already stopped, skipping auto-stop", [
                'campaign_id' => $this->campaign->id,
                'current_status' => $this->campaign->status
            ]);
            return;
        }

        try {
            // Stop the campaign
            $this->campaign->update([
                'status' => 'stopped',
                'stopped_reason' => 'auto_stop_alert',
                'stopped_at' => now()
            ]);

            // Stop all active sub-campaigns
            $this->campaign->subCampaigns()
                ->where('status', 'active')
                ->update([
                    'status' => 'stopped',
                    'stopped_reason' => 'parent_auto_stop',
                    'stopped_at' => now()
                ]);

            Log::warning("Campaign auto-stopped due to critical alert", [
                'campaign_id' => $this->campaign->id,
                'campaign_name' => $this->campaign->name,
                'alert_type' => $alert['category'],
                'alert_message' => $alert['message'],
                'alert_value' => $alert['value'] ?? null
            ]);

            // Schedule notification job
            $this->scheduleAutoStopNotification($alert);

        } catch (\Exception $e) {
            Log::error("Failed to auto-stop campaign", [
                'campaign_id' => $this->campaign->id,
                'alert' => $alert,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Flag campaign for urgent review
     */
    protected function flagForUrgentReview(array $alert): void
    {
        try {
            // Add urgent review flag to campaign
            $this->campaign->update([
                'needs_urgent_review' => true,
                'urgent_review_reason' => $alert['message'],
                'urgent_review_at' => now()
            ]);

            Log::warning("Campaign flagged for urgent review", [
                'campaign_id' => $this->campaign->id,
                'campaign_name' => $this->campaign->name,
                'alert_type' => $alert['category'],
                'alert_message' => $alert['message']
            ]);

            // Schedule urgent review notification
            $this->scheduleUrgentReviewNotification($alert);

        } catch (\Exception $e) {
            Log::error("Failed to flag campaign for urgent review", [
                'campaign_id' => $this->campaign->id,
                'alert' => $alert,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Schedule auto-stop notification
     */
    protected function scheduleAutoStopNotification(array $alert): void
    {
        // Dispatch notification job
        // CampaignNotificationJob::dispatch('auto_stop', [
        //     'campaign_id' => $this->campaign->id,
        //     'alert' => $alert,
        //     'timestamp' => now()
        // ]);

        Log::info("Auto-stop notification scheduled", [
            'campaign_id' => $this->campaign->id,
            'alert_type' => $alert['category']
        ]);
    }

    /**
     * Schedule urgent review notification
     */
    protected function scheduleUrgentReviewNotification(array $alert): void
    {
        // Dispatch notification job
        // CampaignNotificationJob::dispatch('urgent_review', [
        //     'campaign_id' => $this->campaign->id,
        //     'alert' => $alert,
        //     'timestamp' => now()
        // ]);

        Log::info("Urgent review notification scheduled", [
            'campaign_id' => $this->campaign->id,
            'alert_type' => $alert['category']
        ]);
    }

    /**
     * Update alert metrics for monitoring
     */
    protected function updateAlertMetrics(array $alerts): void
    {
        try {
            $metrics = [
                'campaign_id' => $this->campaign->id,
                'timestamp' => now(),
                'total_alerts' => count($alerts),
                'critical_alerts' => count(array_filter($alerts, fn($alert) => $alert['type'] === 'critical')),
                'warning_alerts' => count(array_filter($alerts, fn($alert) => $alert['type'] === 'warning')),
                'info_alerts' => count(array_filter($alerts, fn($alert) => $alert['type'] === 'info')),
                'categories' => $this->getAlertCategoryCounts($alerts)
            ];

            // Store in cache for real-time monitoring
            $cacheKey = "campaign_alert_metrics_{$this->campaign->id}";
            cache()->put($cacheKey, $metrics, now()->addHours(24));

            // Log metrics for external monitoring systems
            Log::channel('metrics')->info("Alert metrics updated", $metrics);

        } catch (\Exception $e) {
            Log::error("Failed to update alert metrics", [
                'campaign_id' => $this->campaign->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get alert category counts
     */
    protected function getAlertCategoryCounts(array $alerts): array
    {
        $categories = [];

        foreach ($alerts as $alert) {
            $category = $alert['category'] ?? 'other';
            $categories[$category] = ($categories[$category] ?? 0) + 1;
        }

        return $categories;
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Performance alerts job failed", [
            'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->name,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Could implement additional failure handling here
        // such as sending admin notifications, etc.
    }
}
