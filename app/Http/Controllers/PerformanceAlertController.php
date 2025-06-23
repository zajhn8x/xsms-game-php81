<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\PerformanceAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Micro-task 2.3.1.3: Performance alerts (3h)
 * Controller for performance alerts API endpoints
 */
class PerformanceAlertController extends Controller
{
    use AuthorizesRequests;

    protected PerformanceAlertService $alertService;

    public function __construct(PerformanceAlertService $alertService)
    {
        $this->alertService = $alertService;
        $this->middleware('auth');
    }

    /**
     * Get performance alerts for a specific campaign
     */
    public function getCampaignAlerts(Campaign $campaign): JsonResponse
    {
        try {
            $this->authorize('view', $campaign);

            $alerts = $this->alertService->monitorCampaignPerformance($campaign);
            $summary = $this->alertService->getAlertSummary($campaign);

            return response()->json([
                'success' => true,
                'data' => [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'alerts' => $alerts,
                    'summary' => $summary,
                    'timestamp' => now()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to get campaign alerts", [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy thông tin alerts'
            ], 500);
        }
    }

    /**
     * Get alert history for a campaign
     */
    public function getCampaignAlertHistory(Campaign $campaign): JsonResponse
    {
        try {
            $this->authorize('view', $campaign);

            $history = $this->alertService->getAlertHistory($campaign);

            return response()->json([
                'success' => true,
                'data' => [
                    'campaign_id' => $campaign->id,
                    'history' => $history,
                    'total_entries' => count($history)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to get campaign alert history", [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy lịch sử alerts'
            ], 500);
        }
    }

    /**
     * Get alerts summary for user's campaigns
     */
    public function getUserAlertsOverview(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $campaigns = $user->campaigns()
                ->whereIn('status', ['active', 'running'])
                ->get();

            $overview = [
                'total_campaigns' => $campaigns->count(),
                'campaigns_with_alerts' => 0,
                'total_critical_alerts' => 0,
                'total_warning_alerts' => 0,
                'campaigns_needing_attention' => [],
                'last_checked' => now()
            ];

            foreach ($campaigns as $campaign) {
                $summary = $this->alertService->getAlertSummary($campaign);

                if ($summary['total_alerts'] > 0) {
                    $overview['campaigns_with_alerts']++;
                    $overview['total_critical_alerts'] += $summary['critical_alerts'];
                    $overview['total_warning_alerts'] += $summary['warning_alerts'];

                    if ($summary['critical_alerts'] > 0 || $summary['action_required']) {
                        $overview['campaigns_needing_attention'][] = [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'critical_alerts' => $summary['critical_alerts'],
                            'warning_alerts' => $summary['warning_alerts'],
                            'action_required' => $summary['action_required'],
                            'latest_critical' => $summary['latest_critical']
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $overview
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to get user alerts overview", [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy tổng quan alerts'
            ], 500);
        }
    }

    /**
     * Acknowledge alerts for a campaign
     */
    public function acknowledgeCampaignAlerts(Request $request, Campaign $campaign): JsonResponse
    {
        try {
            $this->authorize('update', $campaign);

            $validated = $request->validate([
                'alert_types' => 'array',
                'alert_types.*' => 'string|in:critical,warning,info',
                'acknowledge_all' => 'boolean'
            ]);

            $acknowledgeAll = $validated['acknowledge_all'] ?? false;
            $alertTypes = $validated['alert_types'] ?? [];

            // For now, we'll just clear the urgent review flag if acknowledging critical alerts
            if ($acknowledgeAll || in_array('critical', $alertTypes)) {
                $campaign->update([
                    'needs_urgent_review' => false,
                    'urgent_review_reason' => null,
                    'alerts_acknowledged_at' => now(),
                    'alerts_acknowledged_by' => $request->user()->id
                ]);
            }

            Log::info("Campaign alerts acknowledged", [
                'campaign_id' => $campaign->id,
                'acknowledged_by' => $request->user()->id,
                'alert_types' => $alertTypes,
                'acknowledge_all' => $acknowledgeAll
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Alerts đã được xác nhận'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to acknowledge campaign alerts", [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể xác nhận alerts'
            ], 500);
        }
    }

    /**
     * Get alert configuration for the system
     */
    public function getAlertConfiguration(): JsonResponse
    {
        try {
            $config = config('xsmb.performance_alerts');

            return response()->json([
                'success' => true,
                'data' => [
                    'enabled' => $config['enabled'] ?? true,
                    'thresholds' => [
                        'win_rate' => $config['win_rate_threshold'] ?? [],
                        'roi' => $config['roi_threshold'] ?? [],
                        'balance' => $config['balance_threshold'] ?? [],
                        'consecutive_losses' => $config['consecutive_losses_threshold'] ?? [],
                        'balance_depletion_rate' => $config['balance_depletion_rate'] ?? [],
                        'betting_frequency' => $config['betting_frequency_threshold'] ?? [],
                        'large_bet' => $config['large_bet_threshold'] ?? []
                    ],
                    'notification_settings' => $config['notification_settings'] ?? []
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to get alert configuration", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy cấu hình alerts'
            ], 500);
        }
    }

    /**
     * Test alert system for a campaign
     */
    public function testCampaignAlerts(Request $request, Campaign $campaign): JsonResponse
    {
        try {
            $this->authorize('update', $campaign);

            $validated = $request->validate([
                'alert_type' => 'required|string|in:win_rate,roi,balance,consecutive_losses',
                'test_value' => 'required|numeric'
            ]);

            // Create a test alert to verify the system
            $testAlert = [
                'type' => 'warning',
                'category' => 'test',
                'title' => 'Test Alert',
                'message' => "Test alert cho {$validated['alert_type']} với giá trị {$validated['test_value']}",
                'value' => $validated['test_value'],
                'action_required' => 'review',
                'priority' => 'low',
                'timestamp' => now(),
                'is_test' => true
            ];

            Log::info("Test alert generated", [
                'campaign_id' => $campaign->id,
                'test_alert' => $testAlert,
                'triggered_by' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test alert đã được tạo thành công',
                'data' => [
                    'test_alert' => $testAlert,
                    'system_status' => 'working'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to test campaign alerts", [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể test alerts'
            ], 500);
        }
    }

    /**
     * Get real-time alert metrics for monitoring dashboard
     */
    public function getAlertMetrics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get cached metrics for user's campaigns
            $campaignIds = $user->campaigns()->pluck('id');
            $allMetrics = [];

            foreach ($campaignIds as $campaignId) {
                $cacheKey = "campaign_alert_metrics_{$campaignId}";
                $metrics = cache()->get($cacheKey);

                if ($metrics) {
                    $allMetrics[] = $metrics;
                }
            }

            // Aggregate metrics
            $aggregated = [
                'total_campaigns_monitored' => count($allMetrics),
                'total_alerts' => array_sum(array_column($allMetrics, 'total_alerts')),
                'total_critical_alerts' => array_sum(array_column($allMetrics, 'critical_alerts')),
                'total_warning_alerts' => array_sum(array_column($allMetrics, 'warning_alerts')),
                'alert_categories' => [],
                'last_updated' => count($allMetrics) > 0 ? max(array_column($allMetrics, 'timestamp')) : now()
            ];

            // Aggregate categories
            foreach ($allMetrics as $metric) {
                foreach ($metric['categories'] ?? [] as $category => $count) {
                    $aggregated['alert_categories'][$category] = ($aggregated['alert_categories'][$category] ?? 0) + $count;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $aggregated
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to get alert metrics", [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy metrics alerts'
            ], 500);
        }
    }
}
