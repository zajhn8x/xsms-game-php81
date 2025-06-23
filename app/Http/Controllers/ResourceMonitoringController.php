<?php

namespace App\Http\Controllers;

use App\Services\ResourceUsageMonitoringService;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Micro-task 2.3.1.4: Resource usage monitoring (3h)
 * Controller for resource monitoring APIs
 */
class ResourceMonitoringController extends Controller
{
    protected ResourceUsageMonitoringService $resourceMonitor;

    public function __construct(ResourceUsageMonitoringService $resourceMonitor)
    {
        $this->resourceMonitor = $resourceMonitor;
        $this->middleware('auth');
    }

    /**
     * Get system-wide resource usage overview
     */
    public function getSystemOverview(Request $request): JsonResponse
    {
        try {
            $cacheKey = 'system_resource_overview';
            $cacheDuration = config('xsmb.resource_monitoring.cache_duration', 300);

            $systemUsage = Cache::remember($cacheKey, $cacheDuration, function () {
                return $this->resourceMonitor->getSystemResourceUsage();
            });

            // Filter sensitive information for non-admin users
            if (!$request->user()->isAdmin()) {
                $systemUsage = $this->filterSensitiveData($systemUsage);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'system_health' => [
                        'health_score' => $systemUsage['health_score'],
                        'timestamp' => $systemUsage['timestamp'],
                        'status' => $this->getHealthStatus($systemUsage['health_score'])
                    ],
                    'resources' => [
                        'memory' => [
                            'usage_percent' => $systemUsage['memory']['usage_percent'],
                            'current_usage_mb' => $systemUsage['memory']['current_usage_mb'],
                            'health_status' => $systemUsage['memory']['health_status']
                        ],
                        'storage' => [
                            'usage_percent' => $systemUsage['storage']['disk_usage_percent'],
                            'used_space_gb' => $systemUsage['storage']['used_disk_space_gb'],
                            'health_status' => $systemUsage['storage']['health_status']
                        ],
                        'database' => [
                            'connection_usage_percent' => $systemUsage['database']['connection_usage_percent'],
                            'active_connections' => $systemUsage['database']['active_connections'],
                            'health_status' => $systemUsage['database']['health_status']
                        ],
                        'queue' => [
                            'total_pending_jobs' => $systemUsage['queue']['total_pending_jobs'],
                            'health_status' => $systemUsage['queue']['health_status']
                        ],
                        'cpu' => [
                            'estimated_load' => $systemUsage['cpu']['estimated_cpu_load'],
                            'health_status' => $systemUsage['cpu']['health_status']
                        ]
                    ],
                    'activity' => [
                        'active_campaigns' => $systemUsage['campaigns']['total_campaigns'],
                        'active_users_24h' => $systemUsage['users']['active_users_24h'],
                        'resource_efficiency' => $systemUsage['campaigns']['resource_efficiency']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed resource breakdown (admin only)
     */
    public function getDetailedBreakdown(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        try {
            $systemUsage = $this->resourceMonitor->getSystemResourceUsage();

            return response()->json([
                'success' => true,
                'data' => $systemUsage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get detailed breakdown',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get resource alerts
     */
    public function getResourceAlerts(Request $request): JsonResponse
    {
        try {
            $systemUsage = $this->resourceMonitor->getSystemResourceUsage();
            $alerts = $this->generateResourceAlerts($systemUsage);

            return response()->json([
                'success' => true,
                'data' => [
                    'alerts' => $alerts,
                    'alert_count' => count($alerts),
                    'critical_count' => count(array_filter($alerts, fn($alert) => $alert['severity'] === 'critical')),
                    'warning_count' => count(array_filter($alerts, fn($alert) => $alert['severity'] === 'warning')),
                    'timestamp' => now()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get resource alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get campaign resource usage
     */
    public function getCampaignResourceUsage(Request $request, Campaign $campaign = null): JsonResponse
    {
        try {
            if ($campaign) {
                // Check if user can access this campaign
                if ($campaign->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied to this campaign'
                    ], 403);
                }

                $usage = $this->resourceMonitor->getCampaignResourceUsage($campaign);
            } else {
                // Get overall campaign resource usage
                $systemUsage = $this->resourceMonitor->getSystemResourceUsage();
                $usage = $systemUsage['campaigns'];

                // Filter heavy campaigns if not admin
                if (!$request->user()->isAdmin()) {
                    $usage['heavy_campaigns'] = [];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $usage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get campaign resource usage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user resource usage (for current user or admin view)
     */
    public function getUserResourceUsage(Request $request): JsonResponse
    {
        try {
            $systemUsage = $this->resourceMonitor->getSystemResourceUsage();
            $userUsage = $systemUsage['users'];

            // If not admin, only show user's own data
            if (!$request->user()->isAdmin()) {
                $user = $request->user();
                $userCampaigns = $user->campaigns()->whereIn('status', ['active', 'running'])->count();

                $userUsage = [
                    'user_campaigns' => $userCampaigns,
                    'resource_usage_estimate' => $this->estimateUserResourceUsage($user),
                    'activity_level' => $this->getUserActivityLevel($user),
                    'efficiency_score' => $this->calculateUserEfficiencyScore($user)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $userUsage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user resource usage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get optimization recommendations
     */
    public function getOptimizationRecommendations(Request $request): JsonResponse
    {
        try {
            $systemUsage = $this->resourceMonitor->getSystemResourceUsage();
            $recommendations = $systemUsage['campaigns']['recommendations'];

            // Add user-specific recommendations if not admin
            if (!$request->user()->isAdmin()) {
                $userRecommendations = $this->getUserSpecificRecommendations($request->user());
                $recommendations = array_merge($recommendations, $userRecommendations);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'total_count' => count($recommendations),
                    'by_priority' => [
                        'critical' => count(array_filter($recommendations, fn($rec) => $rec['priority'] === 'critical')),
                        'high' => count(array_filter($recommendations, fn($rec) => $rec['priority'] === 'high')),
                        'medium' => count(array_filter($recommendations, fn($rec) => $rec['priority'] === 'medium')),
                        'low' => count(array_filter($recommendations, fn($rec) => $rec['priority'] === 'low'))
                    ],
                    'timestamp' => now()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get optimization recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get health metrics for dashboard widgets
     */
    public function getHealthMetrics(Request $request): JsonResponse
    {
        try {
            $cacheKey = 'health_metrics_dashboard';
            $metrics = Cache::remember($cacheKey, 60, function () {
                $systemUsage = $this->resourceMonitor->getSystemResourceUsage();

                return [
                    'health_score' => $systemUsage['health_score'],
                    'memory_health' => $systemUsage['memory']['health_status'],
                    'storage_health' => $systemUsage['storage']['health_status'],
                    'database_health' => $systemUsage['database']['health_status'],
                    'queue_health' => $systemUsage['queue']['health_status'],
                    'active_campaigns' => $systemUsage['campaigns']['total_campaigns'],
                    'active_users' => $systemUsage['users']['active_users_24h'],
                    'resource_efficiency' => $systemUsage['campaigns']['resource_efficiency'],
                    'alert_level' => $this->calculateAlertLevel($systemUsage)
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get health metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force refresh resource monitoring cache
     */
    public function refreshCache(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        try {
            // Clear related caches
            Cache::forget('system_resource_overview');
            Cache::forget('health_metrics_dashboard');

            // Generate fresh data
            $systemUsage = $this->resourceMonitor->getSystemResourceUsage();

            return response()->json([
                'success' => true,
                'message' => 'Resource monitoring cache refreshed successfully',
                'data' => [
                    'health_score' => $systemUsage['health_score'],
                    'refreshed_at' => now()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper methods
     */
    protected function filterSensitiveData(array $systemUsage): array
    {
        // Remove sensitive system information for non-admin users
        unset($systemUsage['database']['table_sizes']);
        unset($systemUsage['users']['heavy_users']);
        unset($systemUsage['campaigns']['heavy_campaigns']);

        return $systemUsage;
    }

    protected function getHealthStatus(int $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'warning';
        return 'critical';
    }

    protected function generateResourceAlerts(array $systemUsage): array
    {
        $alerts = [];
        $thresholds = config('xsmb.resource_monitoring.alert_thresholds');

        // Memory alert
        if ($systemUsage['memory']['usage_percent'] > $thresholds['memory_usage_percent']) {
            $alerts[] = [
                'type' => 'memory',
                'severity' => 'critical',
                'title' => 'High Memory Usage',
                'message' => "Memory usage at {$systemUsage['memory']['usage_percent']}%",
                'current_value' => $systemUsage['memory']['usage_percent'],
                'threshold' => $thresholds['memory_usage_percent'],
                'timestamp' => now()
            ];
        }

        // Disk alert
        if ($systemUsage['storage']['disk_usage_percent'] > $thresholds['disk_usage_percent']) {
            $alerts[] = [
                'type' => 'storage',
                'severity' => 'critical',
                'title' => 'High Disk Usage',
                'message' => "Disk usage at {$systemUsage['storage']['disk_usage_percent']}%",
                'current_value' => $systemUsage['storage']['disk_usage_percent'],
                'threshold' => $thresholds['disk_usage_percent'],
                'timestamp' => now()
            ];
        }

        // Database alert
        if ($systemUsage['database']['connection_usage_percent'] > $thresholds['database_connections_percent']) {
            $alerts[] = [
                'type' => 'database',
                'severity' => 'warning',
                'title' => 'High Database Connections',
                'message' => "Database connections at {$systemUsage['database']['connection_usage_percent']}%",
                'current_value' => $systemUsage['database']['connection_usage_percent'],
                'threshold' => $thresholds['database_connections_percent'],
                'timestamp' => now()
            ];
        }

        // Queue alert
        if ($systemUsage['queue']['total_pending_jobs'] > $thresholds['queue_size']) {
            $alerts[] = [
                'type' => 'queue',
                'severity' => 'warning',
                'title' => 'High Queue Load',
                'message' => "Queue has {$systemUsage['queue']['total_pending_jobs']} pending jobs",
                'current_value' => $systemUsage['queue']['total_pending_jobs'],
                'threshold' => $thresholds['queue_size'],
                'timestamp' => now()
            ];
        }

        return $alerts;
    }

    protected function estimateUserResourceUsage($user): string
    {
        $activeCampaigns = $user->campaigns()->whereIn('status', ['active', 'running'])->count();

        if ($activeCampaigns > 10) return 'high';
        if ($activeCampaigns > 5) return 'medium';
        return 'low';
    }

    protected function getUserActivityLevel($user): string
    {
        $lastLogin = $user->last_login_at;
        if (!$lastLogin) return 'inactive';

        $hoursAgo = $lastLogin->diffInHours(now());

        if ($hoursAgo <= 2) return 'very_active';
        if ($hoursAgo <= 24) return 'active';
        if ($hoursAgo <= 168) return 'moderate';
        return 'low';
    }

    protected function calculateUserEfficiencyScore($user): int
    {
        // Simple efficiency calculation based on campaign performance vs resource usage
        $campaigns = $user->campaigns()->whereIn('status', ['active', 'running'])->get();

        if ($campaigns->isEmpty()) return 0;

        $totalEfficiency = 0;
        foreach ($campaigns as $campaign) {
            $efficiency = $this->resourceMonitor->getSingleCampaignUsage($campaign)['efficiency_score'] ?? 50;
            $totalEfficiency += $efficiency;
        }

        return round($totalEfficiency / $campaigns->count());
    }

    protected function getUserSpecificRecommendations($user): array
    {
        $recommendations = [];
        $activeCampaigns = $user->campaigns()->whereIn('status', ['active', 'running'])->count();

        if ($activeCampaigns > 10) {
            $recommendations[] = [
                'type' => 'user_optimization',
                'priority' => 'medium',
                'title' => 'Consider Campaign Consolidation',
                'description' => 'You have many active campaigns. Consider consolidating similar strategies.',
                'impact' => 'medium'
            ];
        }

        return $recommendations;
    }

    protected function calculateAlertLevel(array $systemUsage): string
    {
        $alerts = $this->generateResourceAlerts($systemUsage);
        $criticalCount = count(array_filter($alerts, fn($alert) => $alert['severity'] === 'critical'));

        if ($criticalCount > 0) return 'critical';
        if (count($alerts) > 0) return 'warning';
        return 'normal';
    }
}
