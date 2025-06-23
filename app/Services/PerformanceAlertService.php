<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\SubCampaign;
use App\Models\CampaignBet;
use App\Events\CampaignMetricsUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;

/**
 * Micro-task 2.3.1.3: Performance alerts (3h)
 * Service for monitoring campaign performance and triggering alerts
 */
class PerformanceAlertService
{
    protected array $alertConfig;
    protected CampaignMonitoringService $monitoringService;

    public function __construct(CampaignMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
        $this->alertConfig = config('xsmb.performance_alerts', $this->getDefaultAlertConfig());
    }

    /**
     * Monitor campaign performance and generate alerts
     */
    public function monitorCampaignPerformance(Campaign $campaign): array
    {
        $alerts = [];

        // Skip monitoring for inactive campaigns
        if (!in_array($campaign->status, ['active', 'running'])) {
            return $alerts;
        }

        // Get current performance metrics
        $metrics = $this->monitoringService->getCampaignMetrics($campaign);

        // Check various alert conditions
        $alerts = array_merge($alerts, $this->checkPerformanceAlerts($campaign, $metrics));
        $alerts = array_merge($alerts, $this->checkBalanceAlerts($campaign, $metrics));
        $alerts = array_merge($alerts, $this->checkBettingAlerts($campaign, $metrics));
        $alerts = array_merge($alerts, $this->checkTrendAlerts($campaign, $metrics));
        $alerts = array_merge($alerts, $this->checkSystemAlerts($campaign, $metrics));

        // Cache alert history
        $this->cacheAlertHistory($campaign, $alerts);

        // Send notifications for critical alerts
        $this->processAlertNotifications($campaign, $alerts);

        return $alerts;
    }

    /**
     * Check performance-related alerts
     */
    protected function checkPerformanceAlerts(Campaign $campaign, array $metrics): array
    {
        $alerts = [];
        $performance = $metrics['performance'] ?? [];

        // Win rate alert
        if (isset($performance['win_rate'])) {
            $winRate = $performance['win_rate'];
            $threshold = $this->alertConfig['win_rate_threshold'];

            if ($winRate < $threshold['critical'] && $performance['total_bets'] >= 10) {
                $alerts[] = [
                    'type' => 'critical',
                    'category' => 'performance',
                    'title' => 'Tỷ lệ thắng cực thấp',
                    'message' => "Tỷ lệ thắng chỉ {$winRate}% (dưới ngưỡng {$threshold['critical']}%)",
                    'value' => $winRate,
                    'threshold' => $threshold['critical'],
                    'action_required' => 'auto_stop',
                    'priority' => 'high',
                    'timestamp' => now()
                ];
            } elseif ($winRate < $threshold['warning'] && $performance['total_bets'] >= 5) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'performance',
                    'title' => 'Tỷ lệ thắng thấp',
                    'message' => "Tỷ lệ thắng {$winRate}% cần được theo dõi",
                    'value' => $winRate,
                    'threshold' => $threshold['warning'],
                    'action_required' => 'review',
                    'priority' => 'medium',
                    'timestamp' => now()
                ];
            }
        }

        // ROI alert
        if (isset($performance['roi'])) {
            $roi = $performance['roi'];
            $threshold = $this->alertConfig['roi_threshold'];

            if ($roi < $threshold['critical']) {
                $alerts[] = [
                    'type' => 'critical',
                    'category' => 'performance',
                    'title' => 'ROI âm nghiêm trọng',
                    'message' => "ROI {$roi}% (dưới ngưỡng {$threshold['critical']}%)",
                    'value' => $roi,
                    'threshold' => $threshold['critical'],
                    'action_required' => 'urgent_review',
                    'priority' => 'high',
                    'timestamp' => now()
                ];
            } elseif ($roi < $threshold['warning']) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'performance',
                    'title' => 'ROI thấp',
                    'message' => "ROI {$roi}% cần được cải thiện",
                    'value' => $roi,
                    'threshold' => $threshold['warning'],
                    'action_required' => 'review',
                    'priority' => 'medium',
                    'timestamp' => now()
                ];
            }
        }

        // Consecutive losses alert
        if (isset($performance['consecutive_losses'])) {
            $losses = $performance['consecutive_losses'];
            $threshold = $this->alertConfig['consecutive_losses_threshold'];

            if ($losses >= $threshold['critical']) {
                $alerts[] = [
                    'type' => 'critical',
                    'category' => 'performance',
                    'title' => 'Thua liên tiếp quá nhiều',
                    'message' => "Đã thua {$losses} lần liên tiếp",
                    'value' => $losses,
                    'threshold' => $threshold['critical'],
                    'action_required' => 'auto_stop',
                    'priority' => 'high',
                    'timestamp' => now()
                ];
            } elseif ($losses >= $threshold['warning']) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'performance',
                    'title' => 'Thua liên tiếp nhiều',
                    'message' => "Đã thua {$losses} lần liên tiếp",
                    'value' => $losses,
                    'threshold' => $threshold['warning'],
                    'action_required' => 'review',
                    'priority' => 'medium',
                    'timestamp' => now()
                ];
            }
        }

        return $alerts;
    }

    /**
     * Check balance-related alerts
     */
    protected function checkBalanceAlerts(Campaign $campaign, array $metrics): array
    {
        $alerts = [];
        $balance = $metrics['balance'] ?? [];

        // Low balance alert
        if (isset($balance['current']) && isset($balance['initial'])) {
            $currentBalance = $balance['current'];
            $initialBalance = $balance['initial'];
            $threshold = $this->alertConfig['balance_threshold'];

            $balancePercentage = ($currentBalance / $initialBalance) * 100;

            if ($balancePercentage < $threshold['critical']) {
                $alerts[] = [
                    'type' => 'critical',
                    'category' => 'balance',
                    'title' => 'Số dư cực thấp',
                    'message' => "Số dư còn {$balancePercentage}% so với ban đầu",
                    'value' => $balancePercentage,
                    'threshold' => $threshold['critical'],
                    'action_required' => 'auto_stop',
                    'priority' => 'high',
                    'timestamp' => now()
                ];
            } elseif ($balancePercentage < $threshold['warning']) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'balance',
                    'title' => 'Số dư thấp',
                    'message' => "Số dư còn {$balancePercentage}% so với ban đầu",
                    'value' => $balancePercentage,
                    'threshold' => $threshold['warning'],
                    'action_required' => 'review',
                    'priority' => 'medium',
                    'timestamp' => now()
                ];
            }
        }

        // Balance depletion rate alert
        if (isset($metrics['trends']['balance_trend'])) {
            $trend = $metrics['trends']['balance_trend'];
            $threshold = $this->alertConfig['balance_depletion_rate'];

            if (isset($trend['daily_change']) && $trend['daily_change'] < -$threshold['critical']) {
                $alerts[] = [
                    'type' => 'critical',
                    'category' => 'balance',
                    'title' => 'Tốc độ mất tiền quá nhanh',
                    'message' => "Mất " . number_format(abs($trend['daily_change'])) . " VND/ngày",
                    'value' => $trend['daily_change'],
                    'threshold' => -$threshold['critical'],
                    'action_required' => 'urgent_review',
                    'priority' => 'high',
                    'timestamp' => now()
                ];
            }
        }

        return $alerts;
    }

    /**
     * Check betting pattern alerts
     */
    protected function checkBettingAlerts(Campaign $campaign, array $metrics): array
    {
        $alerts = [];
        $betting = $metrics['betting'] ?? [];

        // High frequency betting alert
        if (isset($betting['bets_per_hour'])) {
            $betsPerHour = $betting['bets_per_hour'];
            $threshold = $this->alertConfig['betting_frequency_threshold'];

            if ($betsPerHour > $threshold['critical']) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'betting',
                    'title' => 'Tần suất đặt cược cao',
                    'message' => "Đặt cược {$betsPerHour} lần/giờ",
                    'value' => $betsPerHour,
                    'threshold' => $threshold['critical'],
                    'action_required' => 'review',
                    'priority' => 'medium',
                    'timestamp' => now()
                ];
            }
        }

        // Large bet amount alert
        if (isset($betting['max_bet_amount'])) {
            $maxBetAmount = $betting['max_bet_amount'];
            $threshold = $this->alertConfig['large_bet_threshold'];

            if ($maxBetAmount > $threshold['critical']) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'betting',
                    'title' => 'Cược lớn bất thường',
                    'message' => "Cược tối đa " . number_format($maxBetAmount) . " VND",
                    'value' => $maxBetAmount,
                    'threshold' => $threshold['critical'],
                    'action_required' => 'review',
                    'priority' => 'medium',
                    'timestamp' => now()
                ];
            }
        }

        return $alerts;
    }

    /**
     * Check trend-based alerts
     */
    protected function checkTrendAlerts(Campaign $campaign, array $metrics): array
    {
        $alerts = [];
        $trends = $metrics['trends'] ?? [];

        // Declining performance trend
        if (isset($trends['performance_trend'])) {
            $trend = $trends['performance_trend'];

            if (isset($trend['direction']) && $trend['direction'] === 'declining' && $trend['severity'] === 'high') {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'trend',
                    'title' => 'Xu hướng hiệu suất giảm',
                    'message' => "Hiệu suất đang giảm mạnh trong {$trend['period']} gần đây",
                    'value' => $trend['change_percentage'],
                    'action_required' => 'review',
                    'priority' => 'medium',
                    'timestamp' => now()
                ];
            }
        }

        return $alerts;
    }

    /**
     * Check system-related alerts
     */
    protected function checkSystemAlerts(Campaign $campaign, array $metrics): array
    {
        $alerts = [];

        // No recent activity alert
        $lastBetTime = $campaign->bets()->latest()->first()?->created_at;
        if ($lastBetTime && $lastBetTime->diffInHours(now()) > 24 && $campaign->status === 'active') {
            $alerts[] = [
                'type' => 'info',
                'category' => 'system',
                'title' => 'Không có hoạt động gần đây',
                'message' => "Không có cược nào trong 24h qua",
                'action_required' => 'check_system',
                'priority' => 'low',
                'timestamp' => now()
            ];
        }

        // Sub-campaign alerts
        $subCampaignAlerts = $this->checkSubCampaignAlerts($campaign);
        $alerts = array_merge($alerts, $subCampaignAlerts);

        return $alerts;
    }

    /**
     * Check sub-campaign specific alerts
     */
    protected function checkSubCampaignAlerts(Campaign $campaign): array
    {
        $alerts = [];

        $subCampaigns = $campaign->subCampaigns()->where('status', 'active')->get();

        foreach ($subCampaigns as $subCampaign) {
            // Low balance sub-campaign
            $balancePercentage = $subCampaign->allocated_balance > 0 ?
                ($subCampaign->current_balance / $subCampaign->allocated_balance) * 100 : 0;

            if ($balancePercentage < 10) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'sub_campaign',
                    'title' => 'Sub-campaign số dư thấp',
                    'message' => "Sub-campaign '{$subCampaign->name}' còn {$balancePercentage}%",
                    'sub_campaign_id' => $subCampaign->id,
                    'action_required' => 'rebalance',
                    'priority' => 'medium',
                    'timestamp' => now()
                ];
            }

            // Poor performing sub-campaign
            if ($subCampaign->total_bets > 10 && $subCampaign->win_rate < 25) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'sub_campaign',
                    'title' => 'Sub-campaign hiệu suất kém',
                    'message' => "Sub-campaign '{$subCampaign->name}' tỷ lệ thắng {$subCampaign->win_rate}%",
                    'sub_campaign_id' => $subCampaign->id,
                    'action_required' => 'review',
                    'priority' => 'medium',
                    'timestamp' => now()
                ];
            }
        }

        return $alerts;
    }

    /**
     * Process alert notifications
     */
    protected function processAlertNotifications(Campaign $campaign, array $alerts): void
    {
        $criticalAlerts = array_filter($alerts, fn($alert) => $alert['type'] === 'critical');
        $warningAlerts = array_filter($alerts, fn($alert) => $alert['type'] === 'warning');

        // Send immediate notifications for critical alerts
        if (!empty($criticalAlerts)) {
            $this->sendCriticalAlertNotification($campaign, $criticalAlerts);
        }

        // Batch warning notifications
        if (!empty($warningAlerts)) {
            $this->scheduleWarningNotification($campaign, $warningAlerts);
        }

        // Log all alerts
        Log::info("Performance alerts generated for campaign {$campaign->id}", [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'total_alerts' => count($alerts),
            'critical_alerts' => count($criticalAlerts),
            'warning_alerts' => count($warningAlerts),
            'alerts' => $alerts
        ]);
    }

    /**
     * Send critical alert notification
     */
    protected function sendCriticalAlertNotification(Campaign $campaign, array $alerts): void
    {
        try {
            $user = $campaign->user;

            // Create notification data
            $notificationData = [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'alerts' => $alerts,
                'alert_count' => count($alerts),
                'severity' => 'critical'
            ];

            // Send notification (integrate with your notification system)
            // Notification::send($user, new CriticalPerformanceAlert($notificationData));

            Log::warning("Critical performance alerts sent for campaign {$campaign->id}", $notificationData);

        } catch (\Exception $e) {
            Log::error("Failed to send critical alert notification", [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Schedule warning notification
     */
    protected function scheduleWarningNotification(Campaign $campaign, array $alerts): void
    {
        // Throttle warning notifications to avoid spam
        $cacheKey = "warning_notification_sent_{$campaign->id}";

        if (!Cache::has($cacheKey)) {
            Cache::put($cacheKey, true, now()->addMinutes(30));

            try {
                $user = $campaign->user;

                $notificationData = [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'alerts' => $alerts,
                    'alert_count' => count($alerts),
                    'severity' => 'warning'
                ];

                // Send notification
                // Notification::send($user, new PerformanceWarningAlert($notificationData));

                Log::info("Warning performance alerts sent for campaign {$campaign->id}", $notificationData);

            } catch (\Exception $e) {
                Log::error("Failed to send warning alert notification", [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Cache alert history for trend analysis
     */
    protected function cacheAlertHistory(Campaign $campaign, array $alerts): void
    {
        if (empty($alerts)) {
            return;
        }

        $cacheKey = "campaign_alert_history_{$campaign->id}";
        $history = Cache::get($cacheKey, []);

        // Add current alerts to history
        $history[] = [
            'timestamp' => now(),
            'alerts' => $alerts,
            'alert_count' => count($alerts)
        ];

        // Keep only last 100 entries
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }

        Cache::put($cacheKey, $history, now()->addDays(7));
    }

    /**
     * Get alert history for campaign
     */
    public function getAlertHistory(Campaign $campaign): array
    {
        $cacheKey = "campaign_alert_history_{$campaign->id}";
        return Cache::get($cacheKey, []);
    }

    /**
     * Get alert summary for campaign
     */
    public function getAlertSummary(Campaign $campaign): array
    {
        $alerts = $this->monitorCampaignPerformance($campaign);

        $summary = [
            'total_alerts' => count($alerts),
            'critical_alerts' => 0,
            'warning_alerts' => 0,
            'info_alerts' => 0,
            'categories' => [],
            'latest_critical' => null,
            'action_required' => false
        ];

        foreach ($alerts as $alert) {
            // Count by type
            switch ($alert['type']) {
                case 'critical':
                    $summary['critical_alerts']++;
                    if (!$summary['latest_critical'] || $alert['timestamp'] > $summary['latest_critical']['timestamp']) {
                        $summary['latest_critical'] = $alert;
                    }
                    break;
                case 'warning':
                    $summary['warning_alerts']++;
                    break;
                case 'info':
                    $summary['info_alerts']++;
                    break;
            }

            // Count by category
            $category = $alert['category'] ?? 'other';
            $summary['categories'][$category] = ($summary['categories'][$category] ?? 0) + 1;

            // Check if action is required
            if (in_array($alert['action_required'] ?? '', ['auto_stop', 'urgent_review'])) {
                $summary['action_required'] = true;
            }
        }

        return $summary;
    }

    /**
     * Get default alert configuration
     */
    protected function getDefaultAlertConfig(): array
    {
        return [
            'win_rate_threshold' => [
                'critical' => 15,  // 15%
                'warning' => 30    // 30%
            ],
            'roi_threshold' => [
                'critical' => -50, // -50%
                'warning' => -20   // -20%
            ],
            'balance_threshold' => [
                'critical' => 10,  // 10% of initial
                'warning' => 25    // 25% of initial
            ],
            'consecutive_losses_threshold' => [
                'critical' => 15,  // 15 losses
                'warning' => 10    // 10 losses
            ],
            'balance_depletion_rate' => [
                'critical' => 100000, // 100k VND per day
                'warning' => 50000     // 50k VND per day
            ],
            'betting_frequency_threshold' => [
                'critical' => 20,  // 20 bets per hour
                'warning' => 15    // 15 bets per hour
            ],
            'large_bet_threshold' => [
                'critical' => 500000, // 500k VND
                'warning' => 200000   // 200k VND
            ]
        ];
    }
}
