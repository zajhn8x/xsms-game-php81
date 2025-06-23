<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignBet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Micro-task 2.3.1.4: Resource usage monitoring (3h)
 * Service for monitoring system and campaign resource usage
 */
class ResourceUsageMonitoringService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('xsmb.resource_monitoring', $this->getDefaultConfig());
    }

    /**
     * Monitor system-wide resource usage
     */
    public function getSystemResourceUsage(): array
    {
        $timestamp = now();

        return [
            'timestamp' => $timestamp,
            'health_score' => $this->calculateSystemHealthScore(),
            'memory' => $this->getMemoryUsage(),
            'storage' => $this->getStorageUsage(),
            'database' => $this->getDatabaseUsage(),
            'queue' => $this->getQueueUsage(),
            'cpu' => $this->getCpuUsage(),
            'campaigns' => $this->getCampaignsResourceUsage(),
            'users' => $this->getUsersResourceUsage(),
            'network' => $this->getNetworkUsage()
        ];
    }

    /**
     * Monitor specific campaign resource usage
     */
    public function getCampaignResourceUsage(Campaign $campaign): array
    {
        return [
            'campaign_id' => $campaign->id,
            'memory_usage_mb' => $this->estimateCampaignMemoryUsage($campaign),
            'database_queries_per_hour' => $this->getCampaignQueryFrequency($campaign),
            'storage_usage_mb' => $this->getCampaignStorageUsage($campaign),
            'network_requests_per_hour' => $this->getCampaignNetworkRequests($campaign),
            'resource_intensity' => $this->calculateResourceIntensity($campaign),
            'efficiency_score' => $this->calculateCampaignEfficiencyScore($campaign),
            'optimization_potential' => $this->getOptimizationPotential($campaign),
            'recent_activity' => $this->getCampaignActivity($campaign),
            'cost_analysis' => $this->getCampaignCostAnalysis($campaign)
        ];
    }

    /**
     * Get single campaign usage (helper method for controller)
     */
    public function getSingleCampaignUsage(Campaign $campaign): array
    {
        return $this->getCampaignResourceUsage($campaign);
    }

    /**
     * Calculate overall system health score
     */
    protected function calculateSystemHealthScore(): int
    {
        $weights = $this->config['health_weights'];

        $memoryHealth = $this->getMemoryHealthScore();
        $storageHealth = $this->getStorageHealthScore();
        $databaseHealth = $this->getDatabaseHealthScore();
        $queueHealth = $this->getQueueHealthScore();
        $cpuHealth = $this->getCpuHealthScore();

        $totalScore =
            ($memoryHealth * $weights['memory']) +
            ($storageHealth * $weights['storage']) +
            ($databaseHealth * $weights['database']) +
            ($queueHealth * $weights['queue']) +
            ($cpuHealth * $weights['cpu']);

        return (int) round($totalScore);
    }

    /**
     * Get memory usage information
     */
    protected function getMemoryUsage(): array
    {
        $memoryLimitMB = $this->parseMemoryLimit();
        $currentUsageMB = $this->getCurrentMemoryUsage();
        $peakUsageMB = $this->getPeakMemoryUsage();
        $usagePercent = $memoryLimitMB > 0 ? round(($currentUsageMB / $memoryLimitMB) * 100, 2) : 0;

        return [
            'current_usage_mb' => $currentUsageMB,
            'peak_usage_mb' => $peakUsageMB,
            'memory_limit_mb' => $memoryLimitMB,
            'available_mb' => max(0, $memoryLimitMB - $currentUsageMB),
            'usage_percent' => $usagePercent,
            'health_status' => $this->getMemoryHealthStatus($usagePercent),
            'recommendations' => $this->getMemoryRecommendations($usagePercent)
        ];
    }

    /**
     * Get storage usage information
     */
    protected function getStorageUsage(): array
    {
        $totalSpace = disk_total_space(base_path());
        $freeSpace = disk_free_space(base_path());
        $usedSpace = $totalSpace - $freeSpace;

        $totalSpaceGB = round($totalSpace / 1024 / 1024 / 1024, 2);
        $freeSpaceGB = round($freeSpace / 1024 / 1024 / 1024, 2);
        $usedSpaceGB = round($usedSpace / 1024 / 1024 / 1024, 2);

        $usagePercent = $totalSpace > 0 ? round(($usedSpace / $totalSpace) * 100, 2) : 0;

        return [
            'total_disk_space_gb' => $totalSpaceGB,
            'free_disk_space_gb' => $freeSpaceGB,
            'used_disk_space_gb' => $usedSpaceGB,
            'disk_usage_percent' => $usagePercent,
            'logs_size_mb' => $this->getDirectorySize(storage_path('logs')),
            'cache_size_mb' => $this->getDirectorySize(storage_path('framework/cache')),
            'sessions_size_mb' => $this->getDirectorySize(storage_path('framework/sessions')),
            'uploads_size_mb' => $this->getDirectorySize(storage_path('app/public')),
            'health_status' => $this->getStorageHealthStatus($usagePercent),
            'cleanup_recommendations' => $this->getStorageCleanupRecommendations()
        ];
    }

    /**
     * Get database usage information
     */
    protected function getDatabaseUsage(): array
    {
        // Get database size
        $databaseName = config('database.connections.mysql.database');
        $databaseSize = DB::selectOne("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables
            WHERE table_schema = ?
        ", [$databaseName]);

        // Get connection information
        $maxConnections = DB::selectOne("SHOW VARIABLES LIKE 'max_connections'");
        $activeConnections = DB::selectOne("SHOW STATUS LIKE 'Threads_connected'");

        $maxConn = (int) ($maxConnections->Value ?? 100);
        $activeConn = (int) ($activeConnections->Value ?? 0);
        $connectionUsagePercent = $maxConn > 0 ? round(($activeConn / $maxConn) * 100, 2) : 0;

        // Get slow queries
        $slowQueries = DB::selectOne("SHOW STATUS LIKE 'Slow_queries'");
        $slowQueryCount = (int) ($slowQueries->Value ?? 0);

        // Get table sizes
        $tableSizes = DB::select("
            SELECT
                table_name,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                table_rows
            FROM information_schema.tables
            WHERE table_schema = ?
            ORDER BY (data_length + index_length) DESC
            LIMIT 10
        ", [$databaseName]);

        return [
            'database_size_mb' => $databaseSize->size_mb ?? 0,
            'active_connections' => $activeConn,
            'max_connections' => $maxConn,
            'connection_usage_percent' => $connectionUsagePercent,
            'slow_queries' => $slowQueryCount,
            'table_sizes' => $tableSizes,
            'health_status' => $this->getDatabaseHealthStatus($connectionUsagePercent, $slowQueryCount),
            'optimization_suggestions' => $this->getDatabaseOptimizationSuggestions($tableSizes)
        ];
    }

    /**
     * Get queue usage information
     */
    protected function getQueueUsage(): array
    {
        // Count jobs in different queues
        $defaultQueueSize = DB::table('jobs')->where('queue', 'default')->count();
        $performanceAlertsQueueSize = DB::table('jobs')->where('queue', 'performance-alerts')->count();
        $campaignExecutionQueueSize = DB::table('jobs')->where('queue', 'campaign-execution')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        $totalPendingJobs = $defaultQueueSize + $performanceAlertsQueueSize + $campaignExecutionQueueSize;

        // Estimate processing rate
        $processingRate = $this->estimateQueueProcessingRate();

        return [
            'total_pending_jobs' => $totalPendingJobs,
            'default_queue_size' => $defaultQueueSize,
            'performance_alerts_queue_size' => $performanceAlertsQueueSize,
            'campaign_execution_queue_size' => $campaignExecutionQueueSize,
            'failed_jobs' => $failedJobs,
            'processing_rate' => $processingRate,
            'health_status' => $this->getQueueHealthStatus($totalPendingJobs, $failedJobs),
            'recommendations' => $this->getQueueRecommendations($totalPendingJobs, $failedJobs)
        ];
    }

    /**
     * Get CPU usage estimation
     */
    protected function getCpuUsage(): array
    {
        // Since PHP can't directly get CPU usage, we estimate based on various factors
        $estimatedLoad = $this->estimateCpuLoad();

        return [
            'estimated_cpu_load' => $estimatedLoad,
            'load_factors' => $this->getCpuLoadFactors(),
            'health_status' => $this->getCpuHealthStatus($estimatedLoad),
            'recommendations' => $this->getCpuRecommendations($estimatedLoad)
        ];
    }

    /**
     * Get campaigns resource usage overview
     */
    protected function getCampaignsResourceUsage(): array
    {
        $totalCampaigns = Campaign::whereIn('status', ['active', 'running'])->count();
        $heavyCampaigns = $this->getHeavyResourceCampaigns();

        return [
            'total_campaigns' => $totalCampaigns,
            'database_connections' => $this->estimateCampaignDatabaseConnections(),
            'memory_usage_per_campaign' => $this->estimateMemoryPerCampaign(),
            'query_frequency' => $this->estimateCampaignQueryFrequency(),
            'bet_processing_load' => $this->getBetProcessingLoad(),
            'job_queue_load' => $this->getCampaignJobLoad(),
            'resource_efficiency' => $this->calculateResourceEfficiency(),
            'heavy_campaigns' => $heavyCampaigns,
            'recommendations' => $this->getCampaignOptimizationRecommendations()
        ];
    }

    /**
     * Get users resource usage overview
     */
    protected function getUsersResourceUsage(): array
    {
        $totalUsers = User::count();
        $activeUsers24h = User::where('last_login_at', '>=', now()->subDay())->count();
        $activityRate = $totalUsers > 0 ? round(($activeUsers24h / $totalUsers) * 100, 2) : 0;

        return [
            'total_users' => $totalUsers,
            'active_users_24h' => $activeUsers24h,
            'activity_rate_percent' => $activityRate,
            'average_campaigns_per_user' => $this->getAverageCampaignsPerUser(),
            'concurrent_users_estimate' => $this->estimateConcurrentUsers(),
            'heavy_users' => $this->getHeavyResourceUsers(),
            'user_efficiency_metrics' => $this->getUserEfficiencyMetrics()
        ];
    }

    /**
     * Get network usage information
     */
    protected function getNetworkUsage(): array
    {
        return [
            'api_requests_per_hour' => $this->estimateApiRequestsPerHour(),
            'database_queries_per_hour' => $this->estimateDatabaseQueriesPerHour(),
            'external_api_calls' => $this->estimateExternalApiCalls(),
            'bandwidth_usage_estimate' => $this->estimateBandwidthUsage(),
            'health_status' => 'normal'
        ];
    }

    // Helper methods for calculations
    protected function parseMemoryLimit(): float
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit === '-1') return 0; // No limit

        $value = (float) $memoryLimit;
        $unit = strtoupper(substr($memoryLimit, -1));

        return match($unit) {
            'G' => $value * 1024,
            'M' => $value,
            'K' => $value / 1024,
            default => $value / 1024 / 1024
        };
    }

    protected function getCurrentMemoryUsage(): float
    {
        return round(memory_get_usage(true) / 1024 / 1024, 2);
    }

    protected function getPeakMemoryUsage(): float
    {
        return round(memory_get_peak_usage(true) / 1024 / 1024, 2);
    }

    protected function getDirectorySize(string $path): float
    {
        if (!is_dir($path)) return 0;

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return round($size / 1024 / 1024, 2);
    }

    protected function estimateCpuLoad(): float
    {
        // Estimate CPU load based on various system factors
        $factors = [
            'active_campaigns' => Campaign::whereIn('status', ['active', 'running'])->count(),
            'pending_jobs' => DB::table('jobs')->count(),
            'recent_bets' => CampaignBet::where('created_at', '>=', now()->subHour())->count(),
            'active_users' => User::where('last_login_at', '>=', now()->subHour())->count()
        ];

        // Simple estimation algorithm
        $load = 0.1; // Base load
        $load += $factors['active_campaigns'] * 0.05;
        $load += $factors['pending_jobs'] * 0.01;
        $load += $factors['recent_bets'] * 0.002;
        $load += $factors['active_users'] * 0.01;

        return round(min($load, 10.0), 2); // Cap at 10.0
    }

    protected function getCpuLoadFactors(): array
    {
        return [
            'active_campaigns' => Campaign::whereIn('status', ['active', 'running'])->count(),
            'pending_jobs' => DB::table('jobs')->count(),
            'recent_bets_1h' => CampaignBet::where('created_at', '>=', now()->subHour())->count(),
            'active_users_1h' => User::where('last_login_at', '>=', now()->subHour())->count()
        ];
    }

    protected function getHeavyResourceCampaigns(): array
    {
        $heavyThreshold = $this->config['heavy_thresholds']['campaign_bets_per_day'];

        return Campaign::whereIn('status', ['active', 'running'])
            ->withCount(['bets as recent_bets' => function($query) {
                $query->where('created_at', '>=', now()->subDay());
            }])
            ->having('recent_bets', '>', $heavyThreshold)
            ->limit(10)
            ->get()
            ->map(function($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'recent_bets' => $campaign->recent_bets,
                    'resource_intensity' => $this->calculateResourceIntensity($campaign),
                    'optimization_potential' => $this->getOptimizationPotential($campaign)
                ];
            })
            ->toArray();
    }

    protected function getHeavyResourceUsers(): array
    {
        $heavyThreshold = $this->config['heavy_thresholds']['user_campaigns'];

        return User::withCount(['campaigns as active_campaigns' => function($query) {
                $query->whereIn('status', ['active', 'running']);
            }])
            ->having('active_campaigns', '>', $heavyThreshold)
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'active_campaigns' => $user->active_campaigns,
                    'resource_usage_estimate' => $this->estimateUserResourceUsage($user)
                ];
            })
            ->toArray();
    }

    // Health scoring methods
    protected function getMemoryHealthScore(): int
    {
        $memoryData = $this->getMemoryUsage();
        $usagePercent = $memoryData['usage_percent'];

        if ($usagePercent >= 90) return 0;
        if ($usagePercent >= 80) return 30;
        if ($usagePercent >= 70) return 60;
        if ($usagePercent >= 50) return 80;
        return 100;
    }

    protected function getStorageHealthScore(): int
    {
        $storageData = $this->getStorageUsage();
        $usagePercent = $storageData['disk_usage_percent'];

        if ($usagePercent >= 95) return 0;
        if ($usagePercent >= 90) return 30;
        if ($usagePercent >= 80) return 60;
        if ($usagePercent >= 70) return 80;
        return 100;
    }

    protected function getDatabaseHealthScore(): int
    {
        $dbData = $this->getDatabaseUsage();
        $connectionPercent = $dbData['connection_usage_percent'];
        $slowQueries = $dbData['slow_queries'];

        $connectionScore = match(true) {
            $connectionPercent >= 90 => 0,
            $connectionPercent >= 80 => 30,
            $connectionPercent >= 70 => 60,
            $connectionPercent >= 50 => 80,
            default => 100
        };

        $slowQueryScore = match(true) {
            $slowQueries >= 1000 => 0,
            $slowQueries >= 500 => 30,
            $slowQueries >= 100 => 60,
            $slowQueries >= 50 => 80,
            default => 100
        };

        return (int) round(($connectionScore + $slowQueryScore) / 2);
    }

    protected function getQueueHealthScore(): int
    {
        $queueData = $this->getQueueUsage();
        $pendingJobs = $queueData['total_pending_jobs'];
        $failedJobs = $queueData['failed_jobs'];

        $pendingScore = match(true) {
            $pendingJobs >= 10000 => 0,
            $pendingJobs >= 5000 => 30,
            $pendingJobs >= 1000 => 60,
            $pendingJobs >= 100 => 80,
            default => 100
        };

        $failedScore = match(true) {
            $failedJobs >= 1000 => 0,
            $failedJobs >= 500 => 30,
            $failedJobs >= 100 => 60,
            $failedJobs >= 50 => 80,
            default => 100
        };

        return (int) round(($pendingScore + $failedScore) / 2);
    }

    protected function getCpuHealthScore(): int
    {
        $cpuData = $this->getCpuUsage();
        $estimatedLoad = $cpuData['estimated_cpu_load'];

        if ($estimatedLoad >= 8.0) return 0;
        if ($estimatedLoad >= 6.0) return 30;
        if ($estimatedLoad >= 4.0) return 60;
        if ($estimatedLoad >= 2.0) return 80;
        return 100;
    }

    // Status determination methods
    protected function getMemoryHealthStatus(float $usagePercent): string
    {
        if ($usagePercent >= 90) return 'critical';
        if ($usagePercent >= 80) return 'warning';
        return 'good';
    }

    protected function getStorageHealthStatus(float $usagePercent): string
    {
        if ($usagePercent >= 95) return 'critical';
        if ($usagePercent >= 85) return 'warning';
        return 'good';
    }

    protected function getDatabaseHealthStatus(float $connectionPercent, int $slowQueries): string
    {
        if ($connectionPercent >= 90 || $slowQueries >= 1000) return 'critical';
        if ($connectionPercent >= 80 || $slowQueries >= 100) return 'warning';
        return 'good';
    }

    protected function getQueueHealthStatus(int $pendingJobs, int $failedJobs): string
    {
        if ($pendingJobs >= 10000 || $failedJobs >= 1000) return 'critical';
        if ($pendingJobs >= 1000 || $failedJobs >= 100) return 'warning';
        return 'good';
    }

    protected function getCpuHealthStatus(float $estimatedLoad): string
    {
        if ($estimatedLoad >= 8.0) return 'critical';
        if ($estimatedLoad >= 4.0) return 'warning';
        return 'good';
    }

    // Estimation methods for specific metrics
    protected function estimateCampaignMemoryUsage(Campaign $campaign): float
    {
        // Base memory + memory per bet + memory per configuration complexity
        $baseMemory = 0.5; // MB
        $recentBets = $campaign->bets()->where('created_at', '>=', now()->subDay())->count();
        $memoryPerBet = 0.001; // MB per bet

        return round($baseMemory + ($recentBets * $memoryPerBet), 3);
    }

    protected function getCampaignQueryFrequency(Campaign $campaign): int
    {
        // Estimate based on campaign activity and bet frequency
        $recentBets = $campaign->bets()->where('created_at', '>=', now()->subHour())->count();
        return $recentBets * 5; // Assume 5 queries per bet on average
    }

    protected function getCampaignStorageUsage(Campaign $campaign): float
    {
        // Calculate storage used for logs, bet history, etc.
        $betCount = $campaign->bets()->count();
        $storagePerBet = 0.0001; // MB per bet record

        return round($betCount * $storagePerBet, 3);
    }

    protected function getCampaignNetworkRequests(Campaign $campaign): int
    {
        // Estimate network requests based on external API calls, etc.
        $recentBets = $campaign->bets()->where('created_at', '>=', now()->subHour())->count();
        return $recentBets * 2; // Assume 2 network requests per bet
    }

    protected function calculateResourceIntensity(Campaign $campaign): string
    {
        $recentBets = $campaign->bets()->where('created_at', '>=', now()->subDay())->count();

        if ($recentBets > 500) return 'very_high';
        if ($recentBets > 200) return 'high';
        if ($recentBets > 50) return 'medium';
        return 'low';
    }

    protected function calculateCampaignEfficiencyScore(Campaign $campaign): int
    {
        // Simple efficiency score based on resource usage vs performance
        $recentBets = $campaign->bets()->where('created_at', '>=', now()->subWeek())->count();
        $winRate = $this->calculateCampaignWinRate($campaign);

        if ($recentBets === 0) return 50; // Neutral score for inactive campaigns

        $efficiency = 50;
        if ($winRate > 60) $efficiency += 30;
        elseif ($winRate > 40) $efficiency += 10;
        elseif ($winRate < 20) $efficiency -= 20;

        if ($recentBets > 1000) $efficiency -= 20; // High activity penalty
        elseif ($recentBets < 10) $efficiency -= 10; // Low activity penalty

        return max(0, min(100, $efficiency));
    }

    protected function calculateCampaignWinRate(Campaign $campaign): float
    {
        $totalBets = $campaign->bets()->count();
        if ($totalBets === 0) return 0;

        $winningBets = $campaign->bets()->where('result', 'win')->count();
        return round(($winningBets / $totalBets) * 100, 2);
    }

    protected function getOptimizationPotential(Campaign $campaign): string
    {
        $intensity = $this->calculateResourceIntensity($campaign);
        $efficiency = $this->calculateCampaignEfficiencyScore($campaign);

        if ($intensity === 'very_high' && $efficiency < 60) return 'high';
        if ($intensity === 'high' && $efficiency < 70) return 'medium';
        if ($efficiency < 40) return 'medium';
        return 'low';
    }

    protected function getCampaignActivity(Campaign $campaign): array
    {
        return [
            'bets_24h' => $campaign->bets()->where('created_at', '>=', now()->subDay())->count(),
            'bets_7d' => $campaign->bets()->where('created_at', '>=', now()->subWeek())->count(),
            'last_bet_at' => $campaign->bets()->latest()->first()?->created_at,
            'avg_bet_amount' => $campaign->bets()->avg('amount') ?? 0
        ];
    }

    protected function getCampaignCostAnalysis(Campaign $campaign): array
    {
        $totalBets = $campaign->bets()->count();
        $totalAmount = $campaign->bets()->sum('amount');
        $estimatedResourceCost = $this->estimateCampaignResourceUsage($campaign) * 0.001; // VND per MB

        return [
            'total_bets' => $totalBets,
            'total_amount_vnd' => $totalAmount,
            'estimated_resource_cost_vnd' => $estimatedResourceCost,
            'cost_per_bet' => $totalBets > 0 ? round($estimatedResourceCost / $totalBets, 6) : 0
        ];
    }

    // Additional helper methods
    protected function estimateCampaignResourceUsage(Campaign $campaign): float
    {
        $memory = $this->estimateCampaignMemoryUsage($campaign);
        $storage = $this->getCampaignStorageUsage($campaign);
        return $memory + $storage;
    }

    protected function estimateQueueProcessingRate(): string
    {
        $recentJobsCount = DB::table('jobs')->where('created_at', '>=', now()->subHour())->count();

        if ($recentJobsCount > 1000) return 'very_high';
        if ($recentJobsCount > 500) return 'high';
        if ($recentJobsCount > 100) return 'medium';
        return 'low';
    }

    protected function estimateCampaignDatabaseConnections(): int
    {
        $activeCampaigns = Campaign::whereIn('status', ['active', 'running'])->count();
        return min($activeCampaigns * 2, 50); // Estimate 2 connections per active campaign, max 50
    }

    protected function estimateMemoryPerCampaign(): float
    {
        $activeCampaigns = Campaign::whereIn('status', ['active', 'running'])->count();
        if ($activeCampaigns === 0) return 0;

        $totalMemory = $this->getCurrentMemoryUsage();
        return round($totalMemory / $activeCampaigns, 2);
    }

    protected function estimateCampaignQueryFrequency(): int
    {
        $recentBets = CampaignBet::where('created_at', '>=', now()->subHour())->count();
        return $recentBets * 5; // Estimate 5 queries per bet
    }

    protected function getBetProcessingLoad(): array
    {
        $recentBets = CampaignBet::where('created_at', '>=', now()->subHour())->count();

        $loadLevel = match(true) {
            $recentBets > 1000 => 'very_high',
            $recentBets > 500 => 'high',
            $recentBets > 100 => 'medium',
            default => 'low'
        };

        return [
            'recent_bets_1h' => $recentBets,
            'load_level' => $loadLevel,
            'processing_capacity' => 'normal'
        ];
    }

    protected function getCampaignJobLoad(): array
    {
        $campaignJobs = DB::table('jobs')->where('queue', 'campaign-execution')->count();

        $loadLevel = match(true) {
            $campaignJobs > 1000 => 'very_high',
            $campaignJobs > 500 => 'high',
            $campaignJobs > 100 => 'medium',
            default => 'low'
        };

        return [
            'pending_jobs' => $campaignJobs,
            'load_level' => $loadLevel
        ];
    }

    protected function calculateResourceEfficiency(): int
    {
        $activeCampaigns = Campaign::whereIn('status', ['active', 'running'])->count();
        if ($activeCampaigns === 0) return 5;

        $totalBets = CampaignBet::where('created_at', '>=', now()->subDay())->count();
        $efficiency = $activeCampaigns > 0 ? min(5, max(1, round($totalBets / $activeCampaigns / 20))) : 5;

        return (int) $efficiency;
    }

    protected function getAverageCampaignsPerUser(): float
    {
        $totalUsers = User::count();
        if ($totalUsers === 0) return 0;

        $totalCampaigns = Campaign::count();
        return round($totalCampaigns / $totalUsers, 2);
    }

    protected function estimateConcurrentUsers(): int
    {
        return User::where('last_login_at', '>=', now()->subHour())->count();
    }

    protected function getUserEfficiencyMetrics(): array
    {
        return [
            'average_campaigns_per_active_user' => $this->getAverageCampaignsPerActiveUser(),
            'user_retention_rate' => $this->calculateUserRetentionRate(),
            'activity_distribution' => $this->getUserActivityDistribution()
        ];
    }

    protected function getAverageCampaignsPerActiveUser(): float
    {
        $activeUsers = User::where('last_login_at', '>=', now()->subWeek())->count();
        if ($activeUsers === 0) return 0;

        $activeCampaigns = Campaign::whereIn('status', ['active', 'running'])->count();
        return round($activeCampaigns / $activeUsers, 2);
    }

    protected function calculateUserRetentionRate(): float
    {
        $totalUsers = User::count();
        if ($totalUsers === 0) return 0;

        $activeUsers = User::where('last_login_at', '>=', now()->subWeek())->count();
        return round(($activeUsers / $totalUsers) * 100, 2);
    }

    protected function getUserActivityDistribution(): array
    {
        return [
            'very_active' => User::where('last_login_at', '>=', now()->subHour())->count(),
            'active' => User::where('last_login_at', '>=', now()->subDay())->count(),
            'moderate' => User::where('last_login_at', '>=', now()->subWeek())->count(),
            'inactive' => User::where('last_login_at', '<', now()->subWeek())->orWhereNull('last_login_at')->count()
        ];
    }

    protected function estimateApiRequestsPerHour(): int
    {
        $activeUsers = User::where('last_login_at', '>=', now()->subHour())->count();
        return $activeUsers * 50; // Estimate 50 API requests per active user per hour
    }

    protected function estimateDatabaseQueriesPerHour(): int
    {
        $recentBets = CampaignBet::where('created_at', '>=', now()->subHour())->count();
        $activeCampaigns = Campaign::whereIn('status', ['active', 'running'])->count();
        return ($recentBets * 10) + ($activeCampaigns * 20); // Estimate queries
    }

    protected function estimateExternalApiCalls(): int
    {
        $recentBets = CampaignBet::where('created_at', '>=', now()->subHour())->count();
        return $recentBets * 3; // Estimate 3 external API calls per bet
    }

    protected function estimateBandwidthUsage(): string
    {
        $activeUsers = User::where('last_login_at', '>=', now()->subHour())->count();

        if ($activeUsers > 100) return 'high';
        if ($activeUsers > 50) return 'medium';
        return 'low';
    }

    protected function estimateUserResourceUsage($user): string
    {
        if (is_object($user)) {
            $activeCampaigns = $user->campaigns()->whereIn('status', ['active', 'running'])->count();
        } else {
            // Handle array case
            $activeCampaigns = $user['active_campaigns'] ?? 0;
        }

        if ($activeCampaigns > 10) return 'high';
        if ($activeCampaigns > 5) return 'medium';
        return 'low';
    }

    // Recommendation methods
    protected function getMemoryRecommendations(float $usagePercent): array
    {
        $recommendations = [];

        if ($usagePercent > 85) {
            $recommendations[] = 'Consider increasing memory limit or optimizing memory usage';
        }
        if ($usagePercent > 70) {
            $recommendations[] = 'Monitor memory usage closely and implement caching strategies';
        }

        return $recommendations;
    }

    protected function getStorageCleanupRecommendations(): array
    {
        $recommendations = [];

        if ($this->config['optimization']['auto_cleanup_logs']) {
            $recommendations[] = 'Automatic log cleanup is enabled';
        } else {
            $recommendations[] = 'Enable automatic log cleanup to save disk space';
        }

        return $recommendations;
    }

    protected function getDatabaseOptimizationSuggestions(array $tableSizes): array
    {
        $suggestions = [];

        foreach ($tableSizes as $table) {
            if ($table->size_mb > 100) {
                $suggestions[] = "Consider optimizing large table: {$table->table_name} ({$table->size_mb} MB)";
            }
        }

        return $suggestions;
    }

    protected function getQueueRecommendations(int $pendingJobs, int $failedJobs): array
    {
        $recommendations = [];

        if ($pendingJobs > 1000) {
            $recommendations[] = 'Consider adding more queue workers to process pending jobs';
        }
        if ($failedJobs > 100) {
            $recommendations[] = 'Review and requeue failed jobs, investigate failure causes';
        }

        return $recommendations;
    }

    protected function getCpuRecommendations(float $estimatedLoad): array
    {
        $recommendations = [];

        if ($estimatedLoad > 6.0) {
            $recommendations[] = 'High CPU load detected - consider scaling resources';
        }
        if ($estimatedLoad > 4.0) {
            $recommendations[] = 'Monitor CPU usage and optimize heavy operations';
        }

        return $recommendations;
    }

    protected function getCampaignOptimizationRecommendations(): array
    {
        $recommendations = [];

        $heavyCampaigns = Campaign::whereIn('status', ['active', 'running'])
            ->withCount(['bets as recent_bets' => function($query) {
                $query->where('created_at', '>=', now()->subDay());
            }])
            ->having('recent_bets', '>', 500)
            ->count();

        if ($heavyCampaigns > 5) {
            $recommendations[] = [
                'type' => 'campaign_optimization',
                'priority' => 'high',
                'title' => 'Multiple Heavy Resource Campaigns',
                'description' => 'Several campaigns are using significant resources. Consider optimization.',
                'impact' => 'high'
            ];
        }

        $lowEfficiencyCampaigns = Campaign::whereIn('status', ['active', 'running'])
            ->where('created_at', '<', now()->subWeek())
            ->withCount('bets')
            ->having('bets_count', '<', 10)
            ->count();

        if ($lowEfficiencyCampaigns > 0) {
            $recommendations[] = [
                'type' => 'campaign_cleanup',
                'priority' => 'medium',
                'title' => 'Inactive Campaigns',
                'description' => 'Consider pausing or removing inactive campaigns to free resources.',
                'impact' => 'medium'
            ];
        }

        return $recommendations;
    }

    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => true,
            'monitoring_interval' => 300,
            'cache_duration' => 300,
            'alert_thresholds' => [
                'memory_usage_percent' => 85,
                'disk_usage_percent' => 90,
                'database_connections_percent' => 80,
                'queue_size' => 1000,
                'cpu_load' => 3.0,
                'cache_keys' => 50000,
                'failed_jobs' => 100,
                'slow_queries' => 100
            ],
            'heavy_thresholds' => [
                'user_campaigns' => 5,
                'campaign_bets_per_day' => 100,
                'network_requests_per_hour' => 10000
            ],
            'health_weights' => [
                'database' => 0.25,
                'memory' => 0.20,
                'storage' => 0.15,
                'queue' => 0.20,
                'cpu' => 0.20
            ],
            'optimization' => [
                'auto_cleanup_logs' => true,
                'auto_cleanup_cache' => true,
                'auto_optimize_database' => false,
                'cleanup_interval_days' => 7,
                'max_log_size_mb' => 100
            ]
        ];
    }
}
