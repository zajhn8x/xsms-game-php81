<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\SubCampaign;
use App\Models\CampaignBet;
use App\Events\CampaignStatusUpdated;
use App\Events\CampaignMetricsUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Micro-task 2.3.1.2: Real-time status updates (3h)
 * Service for managing real-time campaign status updates and broadcasting
 */
class CampaignStatusUpdateService
{
    protected CampaignMetricsService $metricsService;

    public function __construct(CampaignMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Update campaign status and broadcast changes
     */
    public function updateCampaignStatus(Campaign $campaign, string $newStatus, string $reason = null): bool
    {
        try {
            $oldStatus = $campaign->status;

            if ($oldStatus === $newStatus) {
                return true; // No change needed
            }

            // Validate status transition
            if (!$this->isValidStatusTransition($oldStatus, $newStatus)) {
                Log::warning("Invalid status transition attempted", [
                    'campaign_id' => $campaign->id,
                    'from' => $oldStatus,
                    'to' => $newStatus
                ]);
                return false;
            }

            // Update campaign status
            $campaign->update([
                'status' => $newStatus,
                'last_status_change' => now(),
                'status_change_reason' => $reason ?? 'status_update',
                'status_history' => $this->updateStatusHistory($campaign, $oldStatus, $newStatus, $reason)
            ]);

            // Clear metrics cache
            $this->metricsService->clearCache($campaign);

            // Get updated metrics for broadcasting
            $metrics = $this->metricsService->getCampaignMetrics($campaign, false);

            // Broadcast status change
            $this->broadcastStatusUpdate($campaign, $oldStatus, $newStatus, $metrics);

            // Handle status-specific actions
            $this->handleStatusChangeActions($campaign, $oldStatus, $newStatus);

            Log::info("Campaign status updated", [
                'campaign_id' => $campaign->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to update campaign status", [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update campaign metrics and broadcast if significant changes
     */
    public function updateCampaignMetrics(Campaign $campaign, bool $forceUpdate = false): void
    {
        try {
            // Get previous metrics from cache
            $previousMetrics = Cache::get("campaign_metrics_{$campaign->id}");

            // Get current metrics
            $currentMetrics = $this->metricsService->getCampaignMetrics($campaign, false);

            // Check if update is significant enough to broadcast
            if ($forceUpdate || $this->isSignificantMetricsChange($previousMetrics, $currentMetrics)) {
                $this->broadcastMetricsUpdate($campaign, $currentMetrics, $previousMetrics);

                // Update real-time dashboard data
                $this->updateDashboardCache($campaign, $currentMetrics);

                Log::debug("Campaign metrics updated and broadcasted", [
                    'campaign_id' => $campaign->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to update campaign metrics", [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process bet result and update campaign status/metrics
     */
    public function processBetResult(CampaignBet $bet): void
    {
        try {
            $campaign = $bet->campaign;

            // Update campaign balance and statistics
            $this->updateCampaignFromBet($campaign, $bet);

            // Update metrics
            $this->updateCampaignMetrics($campaign, true);

            // Check for auto-stop conditions
            $this->checkAutoStopConditions($campaign);

            // Update sub-campaign if applicable
            if ($bet->sub_campaign_id) {
                $this->updateSubCampaignFromBet($bet->subCampaign, $bet);
            }

        } catch (\Exception $e) {
            Log::error("Failed to process bet result", [
                'bet_id' => $bet->id,
                'campaign_id' => $bet->campaign_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Broadcast real-time update to all connected clients
     */
    public function broadcastRealTimeUpdate(Campaign $campaign, array $updateData): void
    {
        try {
            $broadcastData = [
                'campaign_id' => $campaign->id,
                'timestamp' => now()->toISOString(),
                'update_type' => $updateData['type'] ?? 'general',
                'data' => $updateData
            ];

            // Broadcast to campaign-specific channel
            event(new CampaignStatusUpdated($campaign, $broadcastData));

            // Broadcast to user's campaigns channel
            event(new CampaignMetricsUpdated($campaign->user_id, $campaign->id, $updateData));

            Log::debug("Real-time update broadcasted", [
                'campaign_id' => $campaign->id,
                'update_type' => $updateData['type'] ?? 'general'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to broadcast real-time update", [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate status transition
     */
    protected function isValidStatusTransition(string $from, string $to): bool
    {
        $validTransitions = [
            'pending' => ['active', 'cancelled'],
            'active' => ['running', 'paused', 'completed', 'cancelled'],
            'running' => ['active', 'paused', 'completed', 'cancelled'],
            'paused' => ['active', 'running', 'completed', 'cancelled'],
            'completed' => ['cancelled'], // Can only be cancelled from completed
            'cancelled' => [] // Cannot transition from cancelled
        ];

        return isset($validTransitions[$from]) && in_array($to, $validTransitions[$from]);
    }

    /**
     * Update status history
     */
    protected function updateStatusHistory(Campaign $campaign, string $oldStatus, string $newStatus, ?string $reason): array
    {
        $history = $campaign->status_history ?? [];

        $history[] = [
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'reason' => $reason,
            'timestamp' => now()->toISOString(),
            'automated' => app()->runningInConsole()
        ];

        // Keep only last 50 entries
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        return $history;
    }

    /**
     * Broadcast status update
     */
    protected function broadcastStatusUpdate(Campaign $campaign, string $oldStatus, string $newStatus, array $metrics): void
    {
        $updateData = [
            'type' => 'status_change',
            'campaign_id' => $campaign->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'metrics' => $metrics['basic_metrics'],
            'timestamp' => now()->toISOString()
        ];

        $this->broadcastRealTimeUpdate($campaign, $updateData);
    }

    /**
     * Broadcast metrics update
     */
    protected function broadcastMetricsUpdate(Campaign $campaign, array $currentMetrics, ?array $previousMetrics): void
    {
        $updateData = [
            'type' => 'metrics_update',
            'campaign_id' => $campaign->id,
            'metrics' => $currentMetrics,
            'changes' => $this->calculateMetricsChanges($previousMetrics, $currentMetrics),
            'timestamp' => now()->toISOString()
        ];

        $this->broadcastRealTimeUpdate($campaign, $updateData);
    }

    /**
     * Check if metrics change is significant enough to broadcast
     */
    protected function isSignificantMetricsChange(?array $previous, array $current): bool
    {
        if (!$previous) {
            return true; // First time, always significant
        }

        $significantThresholds = [
            'profit_loss' => 10000, // 10k VND change
            'roi_percentage' => 1.0, // 1% ROI change
            'win_rate' => 5.0, // 5% win rate change
            'total_bets' => 1, // Any new bet
            'current_balance' => 5000 // 5k VND balance change
        ];

        foreach ($significantThresholds as $key => $threshold) {
            $oldValue = $this->getNestedValue($previous, $key);
            $newValue = $this->getNestedValue($current, $key);

            if ($oldValue !== null && $newValue !== null) {
                $change = abs($newValue - $oldValue);
                if ($change >= $threshold) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Calculate metrics changes
     */
    protected function calculateMetricsChanges(?array $previous, array $current): array
    {
        if (!$previous) {
            return ['first_update' => true];
        }

        $changes = [];

        $trackingKeys = [
            'financial_metrics.profit_loss',
            'financial_metrics.roi_percentage',
            'performance_metrics.win_rate',
            'performance_metrics.total_bets',
            'basic_metrics.current_balance'
        ];

        foreach ($trackingKeys as $key) {
            $oldValue = $this->getNestedValue($previous, $key);
            $newValue = $this->getNestedValue($current, $key);

            if ($oldValue !== null && $newValue !== null) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                    'change' => $newValue - $oldValue,
                    'change_percentage' => $oldValue != 0 ? (($newValue - $oldValue) / $oldValue) * 100 : 0
                ];
            }
        }

        return $changes;
    }

    /**
     * Get nested array value using dot notation
     */
    protected function getNestedValue(array $array, string $key)
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Handle status change specific actions
     */
    protected function handleStatusChangeActions(Campaign $campaign, string $oldStatus, string $newStatus): void
    {
        switch ($newStatus) {
            case 'active':
                if ($oldStatus === 'pending') {
                    $this->handleCampaignStart($campaign);
                }
                break;

            case 'completed':
                $this->handleCampaignCompletion($campaign);
                break;

            case 'cancelled':
                $this->handleCampaignCancellation($campaign);
                break;
        }
    }

    /**
     * Handle campaign start actions
     */
    protected function handleCampaignStart(Campaign $campaign): void
    {
        // Log campaign start
        Log::info("Campaign started", ['campaign_id' => $campaign->id]);

        // Auto-start eligible sub-campaigns
        $campaign->subCampaigns()
            ->where('status', 'pending')
            ->where('auto_start', true)
            ->each(function ($subCampaign) {
                $subCampaign->start();
            });
    }

    /**
     * Handle campaign completion
     */
    protected function handleCampaignCompletion(Campaign $campaign): void
    {
        // Stop all active sub-campaigns
        $campaign->subCampaigns()
            ->whereIn('status', ['active', 'running'])
            ->each(function ($subCampaign) {
                $subCampaign->stop('parent_completed');
            });

        Log::info("Campaign completed", ['campaign_id' => $campaign->id]);
    }

    /**
     * Handle campaign cancellation
     */
    protected function handleCampaignCancellation(Campaign $campaign): void
    {
        // Cancel all active sub-campaigns
        $campaign->subCampaigns()
            ->whereIn('status', ['active', 'running', 'paused'])
            ->each(function ($subCampaign) {
                $subCampaign->update(['status' => 'cancelled']);
            });

        Log::info("Campaign cancelled", ['campaign_id' => $campaign->id]);
    }

    /**
     * Update dashboard cache
     */
    protected function updateDashboardCache(Campaign $campaign, array $metrics): void
    {
        $dashboardData = [
            'campaign_id' => $campaign->id,
            'name' => $campaign->name,
            'status' => $campaign->status,
            'current_balance' => $metrics['financial_metrics']['current_balance'],
            'profit_loss' => $metrics['financial_metrics']['profit_loss'],
            'roi_percentage' => $metrics['financial_metrics']['roi_percentage'],
            'win_rate' => $metrics['performance_metrics']['win_rate'],
            'total_bets' => $metrics['performance_metrics']['total_bets'],
            'health_score' => $metrics['real_time_metrics']['health_score'] ?? 100,
            'last_updated' => now()->toISOString()
        ];

        Cache::put("campaign_dashboard_{$campaign->id}", $dashboardData, 300); // 5 minutes
    }

    /**
     * Update campaign from bet result
     */
    protected function updateCampaignFromBet(Campaign $campaign, CampaignBet $bet): void
    {
        if ($bet->status === 'won') {
            $campaign->increment('current_balance', $bet->win_amount);
        }

        // Always decrement the bet amount
        $campaign->decrement('current_balance', $bet->bet_amount);
    }

    /**
     * Update sub-campaign from bet result
     */
    protected function updateSubCampaignFromBet(SubCampaign $subCampaign, CampaignBet $bet): void
    {
        if ($bet->status === 'won') {
            $subCampaign->increment('current_balance', $bet->win_amount);
        }

        $subCampaign->decrement('current_balance', $bet->bet_amount);
    }

    /**
     * Check auto-stop conditions
     */
    protected function checkAutoStopConditions(Campaign $campaign): void
    {
        // Check stop loss
        if ($campaign->auto_stop_loss && $campaign->stop_loss_amount) {
            $metrics = $this->metricsService->getCampaignMetrics($campaign, false);
            $profitLoss = $metrics['financial_metrics']['profit_loss'];

            if ($profitLoss <= -$campaign->stop_loss_amount) {
                $this->updateCampaignStatus($campaign, 'cancelled', 'stop_loss_triggered');
                return;
            }
        }

        // Check take profit
        if ($campaign->auto_take_profit && $campaign->take_profit_amount) {
            $metrics = $this->metricsService->getCampaignMetrics($campaign, false);
            $profitLoss = $metrics['financial_metrics']['profit_loss'];

            if ($profitLoss >= $campaign->take_profit_amount) {
                $this->updateCampaignStatus($campaign, 'completed', 'take_profit_reached');
                return;
            }
        }

        // Check balance depletion
        if ($campaign->current_balance <= 0) {
            $this->updateCampaignStatus($campaign, 'completed', 'balance_depleted');
        }
    }
}
