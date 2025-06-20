<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\SubCampaign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Micro-task 2.2.1.2: Campaign scheduler service (4h)
 * Service for managing campaign lifecycle scheduling and automation
 */
class CampaignSchedulerService
{
    /**
     * Process all scheduled campaigns
     */
    public function processScheduledCampaigns(): array
    {
        $results = [
            'campaigns_started' => 0,
            'campaigns_stopped' => 0,
            'sub_campaigns_started' => 0,
            'sub_campaigns_stopped' => 0,
            'errors' => []
        ];

        try {
            // Process campaign starts
            $results['campaigns_started'] = $this->processScheduledStarts();

            // Process campaign stops
            $results['campaigns_stopped'] = $this->processScheduledStops();

            // Process sub-campaign automation
            $subCampaignResults = $this->processSubCampaignAutomation();
            $results['sub_campaigns_started'] = $subCampaignResults['started'];
            $results['sub_campaigns_stopped'] = $subCampaignResults['stopped'];

            Log::info('Campaign scheduler completed', $results);

        } catch (\Exception $e) {
            Log::error('Campaign scheduler failed', ['error' => $e->getMessage()]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Process campaigns scheduled to start
     */
    public function processScheduledStarts(): int
    {
        $campaignsToStart = Campaign::where('status', 'pending')
            ->where('auto_start', true)
            ->where(function ($query) {
                $query->where('scheduled_start_at', '<=', now())
                      ->orWhere(function ($q) {
                          $q->whereNull('scheduled_start_at')
                            ->where('start_date', '<=', now()->toDateString());
                      });
            })
            ->get();

        $started = 0;
        foreach ($campaignsToStart as $campaign) {
            if ($this->startCampaign($campaign)) {
                $started++;
            }
        }

        return $started;
    }

    /**
     * Process campaigns scheduled to stop
     */
    public function processScheduledStops(): int
    {
        $campaignsToStop = Campaign::whereIn('status', ['active', 'running'])
            ->where('auto_stop', true)
            ->where(function ($query) {
                $query->where('scheduled_stop_at', '<=', now())
                      ->orWhere(function ($q) {
                          $q->whereNull('scheduled_stop_at')
                            ->whereRaw('DATE_ADD(start_date, INTERVAL days DAY) <= CURDATE()');
                      });
            })
            ->get();

        $stopped = 0;
        foreach ($campaignsToStop as $campaign) {
            if ($this->stopCampaign($campaign, 'scheduled')) {
                $stopped++;
            }
        }

        return $stopped;
    }

    /**
     * Process sub-campaign automation
     */
    public function processSubCampaignAutomation(): array
    {
        $started = 0;
        $stopped = 0;

        // Get active parent campaigns
        $activeCampaigns = Campaign::whereIn('status', ['active', 'running'])->get();

        foreach ($activeCampaigns as $campaign) {
            // Auto-start sub-campaigns
            $subCampaignsToStart = $campaign->subCampaigns()
                ->where('status', 'pending')
                ->where('auto_start', true)
                ->where('start_date', '<=', now()->toDateString())
                ->get();

            foreach ($subCampaignsToStart as $subCampaign) {
                if ($subCampaign->start()) {
                    $started++;
                }
            }

            // Auto-stop sub-campaigns
            $activeSubCampaigns = $campaign->subCampaigns()
                ->where('status', 'active')
                ->get();

            foreach ($activeSubCampaigns as $subCampaign) {
                if ($subCampaign->shouldAutoStop() && $subCampaign->stop('auto_stop')) {
                    $stopped++;
                }
            }
        }

        return ['started' => $started, 'stopped' => $stopped];
    }

    /**
     * Start a campaign
     */
    public function startCampaign(Campaign $campaign): bool
    {
        try {
            if (!$this->canStartCampaign($campaign)) {
                Log::warning("Cannot start campaign {$campaign->id}: conditions not met");
                return false;
            }

            $oldStatus = $campaign->status;
            $campaign->update([
                'status' => 'active',
                'last_status_change' => now(),
                'status_change_reason' => 'scheduled_start',
                'status_history' => $this->updateStatusHistory($campaign, $oldStatus, 'active', 'scheduled_start')
            ]);

            $this->logCampaignActivity($campaign, 'started', 'Campaign started automatically by scheduler');

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to start campaign {$campaign->id}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Stop a campaign
     */
    public function stopCampaign(Campaign $campaign, string $reason = 'scheduled'): bool
    {
        try {
            $oldStatus = $campaign->status;
            $newStatus = $this->determineStopStatus($campaign, $reason);

            // Stop all active sub-campaigns first
            $campaign->subCampaigns()
                ->where('status', 'active')
                ->each(function ($subCampaign) use ($reason) {
                    $subCampaign->stop($reason);
                });

            $campaign->update([
                'status' => $newStatus,
                'last_status_change' => now(),
                'status_change_reason' => $reason,
                'status_history' => $this->updateStatusHistory($campaign, $oldStatus, $newStatus, $reason)
            ]);

            $this->logCampaignActivity($campaign, 'stopped', "Campaign stopped: {$reason}");

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to stop campaign {$campaign->id}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Pause a campaign
     */
    public function pauseCampaign(Campaign $campaign, string $reason = 'manual'): bool
    {
        try {
            if (!in_array($campaign->status, ['active', 'running'])) {
                return false;
            }

            $oldStatus = $campaign->status;

            // Pause all active sub-campaigns
            $campaign->subCampaigns()
                ->where('status', 'active')
                ->each(function ($subCampaign) {
                    $subCampaign->pause();
                });

            $campaign->update([
                'status' => 'paused',
                'last_status_change' => now(),
                'status_change_reason' => $reason,
                'status_history' => $this->updateStatusHistory($campaign, $oldStatus, 'paused', $reason)
            ]);

            $this->logCampaignActivity($campaign, 'paused', "Campaign paused: {$reason}");

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to pause campaign {$campaign->id}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Resume a campaign
     */
    public function resumeCampaign(Campaign $campaign, string $reason = 'manual'): bool
    {
        try {
            if ($campaign->status !== 'paused') {
                return false;
            }

            $oldStatus = $campaign->status;

            // Resume all paused sub-campaigns
            $campaign->subCampaigns()
                ->where('status', 'paused')
                ->each(function ($subCampaign) {
                    $subCampaign->resume();
                });

            $campaign->update([
                'status' => 'active',
                'last_status_change' => now(),
                'status_change_reason' => $reason,
                'status_history' => $this->updateStatusHistory($campaign, $oldStatus, 'active', $reason)
            ]);

            $this->logCampaignActivity($campaign, 'resumed', "Campaign resumed: {$reason}");

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to resume campaign {$campaign->id}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Schedule campaign start
     */
    public function scheduleCampaignStart(Campaign $campaign, \DateTime $startTime): bool
    {
        try {
            $campaign->update([
                'scheduled_start_at' => $startTime,
                'auto_start' => true
            ]);

            $this->logCampaignActivity($campaign, 'scheduled', "Campaign scheduled to start at {$startTime->format('Y-m-d H:i:s')}");

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to schedule campaign {$campaign->id}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Schedule campaign stop
     */
    public function scheduleCampaignStop(Campaign $campaign, \DateTime $stopTime): bool
    {
        try {
            $campaign->update([
                'scheduled_stop_at' => $stopTime,
                'auto_stop' => true
            ]);

            $this->logCampaignActivity($campaign, 'scheduled', "Campaign scheduled to stop at {$stopTime->format('Y-m-d H:i:s')}");

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to schedule stop for campaign {$campaign->id}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check if campaign can be started
     */
    protected function canStartCampaign(Campaign $campaign): bool
    {
        // Check basic conditions
        if ($campaign->status !== 'pending') {
            return false;
        }

        if ($campaign->initial_balance <= 0) {
            return false;
        }

        // Check user limits
        $user = $campaign->user;
        $activeCampaigns = $user->campaigns()->whereIn('status', ['active', 'running'])->count();

        // Basic users: max 2 active campaigns, Premium: unlimited
        $maxActiveCampaigns = $user->isPremium() ? 100 : 2;

        if ($activeCampaigns >= $maxActiveCampaigns) {
            return false;
        }

        // Check if start date has arrived
        if ($campaign->start_date > now()->toDateString()) {
            return false;
        }

        return true;
    }

    /**
     * Determine stop status based on reason
     */
    protected function determineStopStatus(Campaign $campaign, string $reason): string
    {
        switch ($reason) {
            case 'completed':
            case 'target_reached':
                return 'completed';
            case 'stop_loss':
            case 'force_stop':
                return 'cancelled';
            case 'scheduled':
            case 'time_limit':
                return 'completed';
            default:
                return 'cancelled';
        }
    }

    /**
     * Update status history
     */
    protected function updateStatusHistory(Campaign $campaign, string $oldStatus, string $newStatus, string $reason): array
    {
        $history = $campaign->status_history ?? [];

        $history[] = [
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'reason' => $reason,
            'timestamp' => now()->toISOString(),
            'automated' => true
        ];

        // Keep only last 50 entries
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        return $history;
    }

    /**
     * Log campaign activity
     */
    protected function logCampaignActivity(Campaign $campaign, string $action, string $description): void
    {
        if (class_exists(\App\Models\ActivityLog::class)) {
            \App\Models\ActivityLog::create([
                'user_id' => $campaign->user_id,
                'activity_type' => 'campaign_lifecycle',
                'activity_action' => $action,
                'description' => $description,
                'metadata' => [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'automated' => true
                ],
                'ip_address' => '127.0.0.1', // System action
                'user_agent' => 'Campaign Scheduler'
            ]);
        }
    }

    /**
     * Get campaigns needing attention
     */
    public function getCampaignsNeedingAttention(): Collection
    {
        return Campaign::whereIn('status', ['active', 'running'])
            ->get()
            ->filter(function ($campaign) {
                return $this->needsAttention($campaign);
            });
    }

    /**
     * Check if campaign needs attention
     */
    protected function needsAttention(Campaign $campaign): bool
    {
        // Check stop loss conditions
        if ($campaign->auto_stop_loss && $campaign->stop_loss_amount) {
            $currentLoss = $campaign->initial_balance - $campaign->current_balance;
            if ($currentLoss >= $campaign->stop_loss_amount) {
                return true;
            }
        }

        // Check take profit conditions
        if ($campaign->auto_take_profit && $campaign->take_profit_amount) {
            $currentProfit = $campaign->current_balance - $campaign->initial_balance;
            if ($currentProfit >= $campaign->take_profit_amount) {
                return true;
            }
        }

        // Check if campaign should end based on time
        if ($campaign->days) {
            $endDate = $campaign->start_date->addDays($campaign->days);
            if (now()->toDateString() >= $endDate->toDateString()) {
                return true;
            }
        }

        // Check low balance
        if ($campaign->current_balance <= 0) {
            return true;
        }

        return false;
    }

    /**
     * Process campaigns needing attention
     */
    public function processCampaignsNeedingAttention(): int
    {
        $campaigns = $this->getCampaignsNeedingAttention();
        $processed = 0;

        foreach ($campaigns as $campaign) {
            $reason = $this->determineAttentionReason($campaign);
            if ($this->stopCampaign($campaign, $reason)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Determine why campaign needs attention
     */
    protected function determineAttentionReason(Campaign $campaign): string
    {
        // Check stop loss first
        if ($campaign->auto_stop_loss && $campaign->stop_loss_amount) {
            $currentLoss = $campaign->initial_balance - $campaign->current_balance;
            if ($currentLoss >= $campaign->stop_loss_amount) {
                return 'stop_loss';
            }
        }

        // Check take profit
        if ($campaign->auto_take_profit && $campaign->take_profit_amount) {
            $currentProfit = $campaign->current_balance - $campaign->initial_balance;
            if ($currentProfit >= $campaign->take_profit_amount) {
                return 'target_reached';
            }
        }

        // Check time limit
        if ($campaign->days) {
            $endDate = $campaign->start_date->addDays($campaign->days);
            if (now()->toDateString() >= $endDate->toDateString()) {
                return 'time_limit';
            }
        }

        // Check balance depletion
        if ($campaign->current_balance <= 0) {
            return 'balance_depleted';
        }

        return 'unknown';
    }

    /**
     * Get scheduling status for campaigns
     */
    public function getSchedulingStatus(): array
    {
        return [
            'pending_starts' => Campaign::where('status', 'pending')
                ->where('auto_start', true)
                ->where(function ($query) {
                    $query->where('scheduled_start_at', '>', now())
                          ->orWhere(function ($q) {
                              $q->whereNull('scheduled_start_at')
                                ->where('start_date', '>', now()->toDateString());
                          });
                })
                ->count(),

            'pending_stops' => Campaign::whereIn('status', ['active', 'running'])
                ->where('auto_stop', true)
                ->where(function ($query) {
                    $query->where('scheduled_stop_at', '>', now())
                          ->orWhere(function ($q) {
                              $q->whereNull('scheduled_stop_at')
                                ->whereRaw('DATE_ADD(start_date, INTERVAL days DAY) > CURDATE()');
                          });
                })
                ->count(),

            'needing_attention' => $this->getCampaignsNeedingAttention()->count(),

            'next_scheduled_start' => Campaign::where('status', 'pending')
                ->where('auto_start', true)
                ->whereNotNull('scheduled_start_at')
                ->orderBy('scheduled_start_at')
                ->first()?->scheduled_start_at,

            'next_scheduled_stop' => Campaign::whereIn('status', ['active', 'running'])
                ->where('auto_stop', true)
                ->whereNotNull('scheduled_stop_at')
                ->orderBy('scheduled_stop_at')
                ->first()?->scheduled_stop_at
        ];
    }
}
