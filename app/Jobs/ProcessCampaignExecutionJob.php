<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Campaign;
use App\Services\CampaignExecutionService;
use App\Services\CampaignMonitoringService;
use Illuminate\Support\Facades\Log;

/**
 * Job for processing campaign execution in background
 * Part of micro-task 2.2.2: Campaign execution engine
 */
class ProcessCampaignExecutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Campaign $campaign;
    protected array $options;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance
     */
    public function __construct(Campaign $campaign, array $options = [])
    {
        $this->campaign = $campaign;
        $this->options = $options;
    }

    /**
     * Execute the job
     */
    public function handle(
        CampaignExecutionService $executionService,
        CampaignMonitoringService $monitoringService
    ): void {
        try {
            Log::info("Processing campaign execution", [
                'campaign_id' => $this->campaign->id,
                'campaign_name' => $this->campaign->name,
                'options' => $this->options
            ]);

            // Check if campaign is still active
            if (!$this->isCampaignExecutable()) {
                Log::info("Campaign not executable, skipping", [
                    'campaign_id' => $this->campaign->id,
                    'status' => $this->campaign->status
                ]);
                return;
            }

            // Execute campaign
            $results = $executionService->executeCampaign($this->campaign);

            // Log execution results
            Log::info("Campaign execution completed", [
                'campaign_id' => $this->campaign->id,
                'results' => $results
            ]);

            // Monitor performance and trigger alerts
            $alerts = $monitoringService->monitorCampaignPerformance($this->campaign);

            if (!empty($alerts)) {
                Log::info("Campaign alerts generated", [
                    'campaign_id' => $this->campaign->id,
                    'alerts_count' => count($alerts),
                    'critical_alerts' => $this->countCriticalAlerts($alerts)
                ]);

                // Handle critical alerts
                $this->handleCriticalAlerts($alerts, $executionService);
            }

            // Schedule next execution if needed
            $this->scheduleNextExecution();

        } catch (\Exception $e) {
            Log::error("Campaign execution job failed", [
                'campaign_id' => $this->campaign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Check if campaign is executable
     */
    protected function isCampaignExecutable(): bool
    {
        // Refresh campaign data
        $this->campaign->refresh();

        // Check campaign status
        if (!in_array($this->campaign->status, ['active', 'running'])) {
            return false;
        }

        // Check if campaign has balance
        if ($this->campaign->current_balance <= 0) {
            return false;
        }

        // Check if campaign is within execution time
        if ($this->campaign->days) {
            $endDate = $this->campaign->start_date->addDays($this->campaign->days);
            if (now() >= $endDate) {
                return false;
            }
        }

        return true;
    }

    /**
     * Count critical alerts
     */
    protected function countCriticalAlerts(array $alerts): int
    {
        return count(array_filter($alerts, function($alert) {
            return $alert['type'] === 'critical';
        }));
    }

    /**
     * Handle critical alerts
     */
    protected function handleCriticalAlerts(array $alerts, CampaignExecutionService $executionService): void
    {
        foreach ($alerts as $alert) {
            if ($alert['type'] === 'critical' && $alert['action_required'] === 'auto_stop') {
                Log::warning("Auto-stopping campaign due to critical alert", [
                    'campaign_id' => $this->campaign->id,
                    'alert' => $alert
                ]);

                // Stop campaign
                $this->campaign->update(['status' => 'stopped']);

                // Don't schedule next execution
                return;
            }
        }
    }

    /**
     * Schedule next execution
     */
    protected function scheduleNextExecution(): void
    {
        // Get execution frequency from options or campaign settings
        $frequency = $this->options['frequency'] ?? 'hourly';

        $delay = match($frequency) {
            'realtime' => now()->addMinutes(5),
            'frequent' => now()->addMinutes(15),
            'hourly' => now()->addHour(),
            'daily' => now()->addDay(),
            default => now()->addHour()
        };

        // Only schedule if campaign is still active
        if ($this->isCampaignExecutable()) {
            ProcessCampaignExecutionJob::dispatch($this->campaign, $this->options)
                ->delay($delay);

            Log::info("Next campaign execution scheduled", [
                'campaign_id' => $this->campaign->id,
                'next_execution' => $delay->toISOString(),
                'frequency' => $frequency
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Exception $exception): void
    {
        Log::error("Campaign execution job failed permanently", [
            'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->name,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Optionally pause campaign on repeated failures
        if ($this->attempts() >= $this->tries) {
            Log::warning("Pausing campaign due to repeated execution failures", [
                'campaign_id' => $this->campaign->id
            ]);

            $this->campaign->update([
                'status' => 'paused',
                'pause_reason' => 'execution_failures'
            ]);
        }
    }

    /**
     * Get unique job ID for deduplication
     */
    public function uniqueId(): string
    {
        return "campaign_execution_{$this->campaign->id}";
    }

    /**
     * Determine if the job should be unique
     */
    public function unique(): bool
    {
        return true;
    }

    /**
     * The number of seconds after which the job's unique lock will be released
     */
    public int $uniqueFor = 3600; // 1 hour
}
