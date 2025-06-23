<?php

namespace App\Console\Commands;

use App\Services\ResourceUsageMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Micro-task 2.3.1.4: Resource usage monitoring (3h)
 * Console command to monitor system and campaign resource usage
 */
class MonitorResourceUsage extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'monitor:resources
                          {--detailed : Show detailed resource breakdown}
                          {--alerts : Only show resources that exceed thresholds}
                          {--campaigns : Show per-campaign resource usage}
                          {--users : Show heavy users}
                          {--recommendations : Show optimization recommendations}
                          {--json : Output in JSON format}
                          {--save : Save monitoring data to log}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor system and campaign resource usage with detailed analysis';

    protected ResourceUsageMonitoringService $resourceMonitor;

    public function __construct(ResourceUsageMonitoringService $resourceMonitor)
    {
        parent::__construct();
        $this->resourceMonitor = $resourceMonitor;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        $this->info('🔍 Bắt đầu giám sát Resource Usage...');
        $this->newLine();

        // Check if monitoring is enabled
        if (!config('xsmb.resource_monitoring.enabled', true)) {
            $this->warn('⚠️ Resource monitoring đã bị tắt trong config.');
            return Command::FAILURE;
        }

        try {
            // Get system resource usage
            $systemUsage = $this->resourceMonitor->getSystemResourceUsage();

            if ($this->option('json')) {
                $this->outputJson($systemUsage);
                return Command::SUCCESS;
            }

            // Display results
            $this->displaySystemOverview($systemUsage);

            if ($this->option('detailed')) {
                $this->displayDetailedBreakdown($systemUsage);
            }

            if ($this->option('alerts')) {
                $this->displayResourceAlerts($systemUsage);
            }

            if ($this->option('campaigns')) {
                $this->displayCampaignResourceUsage($systemUsage);
            }

            if ($this->option('users')) {
                $this->displayUserResourceUsage($systemUsage);
            }

            if ($this->option('recommendations')) {
                $this->displayOptimizationRecommendations($systemUsage);
            }

            // Save to log if requested
            if ($this->option('save')) {
                $this->saveMonitoringData($systemUsage);
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->newLine();
            $this->info("✅ Resource monitoring hoàn thành trong {$executionTime}ms");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Lỗi trong quá trình monitoring: {$e->getMessage()}");
            Log::error("Resource monitoring failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Display system overview
     */
    protected function displaySystemOverview(array $systemUsage): void
    {
        $this->info('📊 SYSTEM RESOURCE OVERVIEW');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Health Score
        $healthScore = $systemUsage['health_score'];
        $healthStatus = $healthScore >= 80 ? '🟢 Excellent' :
                       ($healthScore >= 60 ? '🟡 Good' :
                       ($healthScore >= 40 ? '🟠 Warning' : '🔴 Critical'));

        $this->table(['Metric', 'Value', 'Status'], [
            ['Overall Health Score', $healthScore . '/100', $healthStatus],
            ['Memory Usage', $systemUsage['memory']['current_usage_mb'] . 'MB (' . $systemUsage['memory']['usage_percent'] . '%)', $this->getStatusIcon($systemUsage['memory']['health_status'])],
            ['Disk Usage', $systemUsage['storage']['used_disk_space_gb'] . 'GB (' . $systemUsage['storage']['disk_usage_percent'] . '%)', $this->getStatusIcon($systemUsage['storage']['health_status'])],
            ['Database Connections', $systemUsage['database']['active_connections'] . '/' . $systemUsage['database']['max_connections'] . ' (' . $systemUsage['database']['connection_usage_percent'] . '%)', $this->getStatusIcon($systemUsage['database']['health_status'])],
            ['Queue Jobs', $systemUsage['queue']['total_pending_jobs'], $this->getStatusIcon($systemUsage['queue']['health_status'])],
            ['CPU Load (Estimated)', $systemUsage['cpu']['estimated_cpu_load'], $this->getStatusIcon($systemUsage['cpu']['health_status'])],
            ['Active Campaigns', $systemUsage['campaigns']['total_campaigns'], '📈'],
            ['Active Users (24h)', $systemUsage['users']['active_users_24h'], '👥']
        ]);
    }

    /**
     * Display detailed resource breakdown
     */
    protected function displayDetailedBreakdown(array $systemUsage): void
    {
        $this->newLine();
        $this->info('🔬 DETAILED RESOURCE BREAKDOWN');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Memory Details
        $this->line('<info>💾 Memory Usage:</info>');
        $this->table(['Metric', 'Value'], [
            ['Current Usage', $systemUsage['memory']['current_usage_mb'] . ' MB'],
            ['Peak Usage', $systemUsage['memory']['peak_usage_mb'] . ' MB'],
            ['Memory Limit', $systemUsage['memory']['memory_limit_mb'] . ' MB'],
            ['Available', $systemUsage['memory']['available_mb'] . ' MB'],
            ['Usage Percentage', $systemUsage['memory']['usage_percent'] . '%']
        ]);

        // Storage Details
        $this->newLine();
        $this->line('<info>💽 Storage Usage:</info>');
        $this->table(['Metric', 'Value'], [
            ['Total Disk Space', $systemUsage['storage']['total_disk_space_gb'] . ' GB'],
            ['Free Space', $systemUsage['storage']['free_disk_space_gb'] . ' GB'],
            ['Used Space', $systemUsage['storage']['used_disk_space_gb'] . ' GB'],
            ['Logs Size', $systemUsage['storage']['logs_size_mb'] . ' MB'],
            ['Cache Size', $systemUsage['storage']['cache_size_mb'] . ' MB'],
            ['Sessions Size', $systemUsage['storage']['sessions_size_mb'] . ' MB'],
            ['Uploads Size', $systemUsage['storage']['uploads_size_mb'] . ' MB']
        ]);

        // Database Details
        $this->newLine();
        $this->line('<info>🗃️ Database Usage:</info>');
        $this->table(['Metric', 'Value'], [
            ['Database Size', $systemUsage['database']['database_size_mb'] . ' MB'],
            ['Active Connections', $systemUsage['database']['active_connections']],
            ['Max Connections', $systemUsage['database']['max_connections']],
            ['Connection Usage', $systemUsage['database']['connection_usage_percent'] . '%'],
            ['Slow Queries', $systemUsage['database']['slow_queries']]
        ]);

        // Top Tables by Size
        if (!empty($systemUsage['database']['table_sizes'])) {
            $this->newLine();
            $this->line('<info>📋 Top Database Tables:</info>');
            $tableData = [];
            foreach (array_slice($systemUsage['database']['table_sizes'], 0, 5) as $table) {
                $tableData[] = [$table->table_name, $table->size_mb . ' MB', number_format($table->table_rows)];
            }
            $this->table(['Table', 'Size', 'Rows'], $tableData);
        }

        // Queue Details
        $this->newLine();
        $this->line('<info>⚡ Queue Usage:</info>');
        $this->table(['Queue', 'Pending Jobs'], [
            ['Default Queue', $systemUsage['queue']['default_queue_size']],
            ['Performance Alerts', $systemUsage['queue']['performance_alerts_queue_size']],
            ['Campaign Execution', $systemUsage['queue']['campaign_execution_queue_size']],
            ['Failed Jobs', $systemUsage['queue']['failed_jobs']],
            ['Processing Rate', $systemUsage['queue']['processing_rate']]
        ]);
    }

    /**
     * Display resource alerts
     */
    protected function displayResourceAlerts(array $systemUsage): void
    {
        $this->newLine();
        $this->info('🚨 RESOURCE ALERTS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $alerts = [];
        $thresholds = config('xsmb.resource_monitoring.alert_thresholds');

        // Check each resource against thresholds
        if ($systemUsage['memory']['usage_percent'] > $thresholds['memory_usage_percent']) {
            $alerts[] = ['🔴 Memory', 'Critical', $systemUsage['memory']['usage_percent'] . '%', 'Memory usage exceeds threshold'];
        }

        if ($systemUsage['storage']['disk_usage_percent'] > $thresholds['disk_usage_percent']) {
            $alerts[] = ['🔴 Disk', 'Critical', $systemUsage['storage']['disk_usage_percent'] . '%', 'Disk usage exceeds threshold'];
        }

        if ($systemUsage['database']['connection_usage_percent'] > $thresholds['database_connections_percent']) {
            $alerts[] = ['🟠 Database', 'Warning', $systemUsage['database']['connection_usage_percent'] . '%', 'Database connections high'];
        }

        if ($systemUsage['queue']['total_pending_jobs'] > $thresholds['queue_size']) {
            $alerts[] = ['🟡 Queue', 'Warning', $systemUsage['queue']['total_pending_jobs'], 'High number of pending jobs'];
        }

        if ($systemUsage['cpu']['estimated_cpu_load'] > $thresholds['cpu_load']) {
            $alerts[] = ['🔴 CPU', 'Critical', $systemUsage['cpu']['estimated_cpu_load'], 'High CPU load estimated'];
        }

        if (empty($alerts)) {
            $this->info('✅ Không có resource alerts nào cần chú ý');
        } else {
            $this->table(['Resource', 'Severity', 'Current', 'Description'], $alerts);
        }
    }

    /**
     * Display campaign resource usage
     */
    protected function displayCampaignResourceUsage(array $systemUsage): void
    {
        $campaigns = $systemUsage['campaigns'];

        $this->newLine();
        $this->info('🎯 CAMPAIGN RESOURCE USAGE');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->table(['Metric', 'Value'], [
            ['Total Active Campaigns', $campaigns['total_campaigns']],
            ['Database Connections', $campaigns['database_connections']],
            ['Memory per Campaign', $campaigns['memory_usage_per_campaign'] . ' MB'],
            ['Query Frequency', $campaigns['query_frequency'] . ' queries/min'],
            ['Bet Processing Load', $campaigns['bet_processing_load']['load_level']],
            ['Campaign Job Load', $campaigns['job_queue_load']['load_level']],
            ['Resource Efficiency', $campaigns['resource_efficiency'] . '/5']
        ]);

        // Heavy Campaigns
        if (!empty($campaigns['heavy_campaigns'])) {
            $this->newLine();
            $this->line('<info>🔥 Heavy Resource Campaigns:</info>');
            $heavyCampaignData = [];
            foreach ($campaigns['heavy_campaigns'] as $campaign) {
                $heavyCampaignData[] = [
                    $campaign['id'],
                    substr($campaign['name'], 0, 30) . (strlen($campaign['name']) > 30 ? '...' : ''),
                    $campaign['recent_bets'],
                    $campaign['resource_intensity'],
                    $campaign['optimization_potential']
                ];
            }
            $this->table(['ID', 'Name', 'Recent Bets', 'Intensity', 'Optimization'], $heavyCampaignData);
        }
    }

    /**
     * Display user resource usage
     */
    protected function displayUserResourceUsage(array $systemUsage): void
    {
        $users = $systemUsage['users'];

        $this->newLine();
        $this->info('👥 USER RESOURCE USAGE');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->table(['Metric', 'Value'], [
            ['Total Users', $users['total_users']],
            ['Active Users (24h)', $users['active_users_24h']],
            ['Activity Rate', $users['activity_rate_percent'] . '%'],
            ['Avg Campaigns/User', $users['average_campaigns_per_user']],
            ['Concurrent Users', $users['concurrent_users_estimate']]
        ]);

        // Heavy Users
        if (!empty($users['heavy_users'])) {
            $this->newLine();
            $this->line('<info>🔥 Heavy Resource Users:</info>');
            $heavyUserData = [];
            foreach ($users['heavy_users'] as $user) {
                $heavyUserData[] = [
                    $user['id'],
                    substr($user['email'], 0, 30) . (strlen($user['email']) > 30 ? '...' : ''),
                    $user['active_campaigns'],
                    $user['resource_usage_estimate']
                ];
            }
            $this->table(['ID', 'Email', 'Active Campaigns', 'Usage'], $heavyUserData);
        }
    }

    /**
     * Display optimization recommendations
     */
    protected function displayOptimizationRecommendations(array $systemUsage): void
    {
        $recommendations = $systemUsage['campaigns']['recommendations'];

        $this->newLine();
        $this->info('💡 OPTIMIZATION RECOMMENDATIONS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if (empty($recommendations)) {
            $this->info('✅ Không có recommendations nào. Hệ thống đang hoạt động tối ưu!');
        } else {
            $recData = [];
            foreach ($recommendations as $rec) {
                $priority = match($rec['priority']) {
                    'critical' => '🔴 Critical',
                    'high' => '🟠 High',
                    'medium' => '🟡 Medium',
                    default => '🟢 Low'
                };

                $recData[] = [
                    $priority,
                    $rec['title'],
                    $rec['description'],
                    $rec['impact']
                ];
            }
            $this->table(['Priority', 'Title', 'Description', 'Impact'], $recData);
        }
    }

    /**
     * Output data in JSON format
     */
    protected function outputJson(array $systemUsage): void
    {
        $this->line(json_encode($systemUsage, JSON_PRETTY_PRINT));
    }

    /**
     * Save monitoring data to log
     */
    protected function saveMonitoringData(array $systemUsage): void
    {
        Log::channel('monitoring')->info('Resource usage monitoring', [
            'timestamp' => $systemUsage['timestamp'],
            'health_score' => $systemUsage['health_score'],
            'memory_usage_percent' => $systemUsage['memory']['usage_percent'],
            'disk_usage_percent' => $systemUsage['storage']['disk_usage_percent'],
            'database_connections_percent' => $systemUsage['database']['connection_usage_percent'],
            'queue_pending_jobs' => $systemUsage['queue']['total_pending_jobs'],
            'cpu_load' => $systemUsage['cpu']['estimated_cpu_load'],
            'active_campaigns' => $systemUsage['campaigns']['total_campaigns'],
            'active_users' => $systemUsage['users']['active_users_24h']
        ]);

        $this->info('📝 Monitoring data saved to logs');
    }

    /**
     * Get status icon for health status
     */
    protected function getStatusIcon(string $status): string
    {
        return match($status) {
            'good', 'normal' => '🟢 Good',
            'warning' => '🟡 Warning',
            'critical' => '🔴 Critical',
            'high_load' => '🟠 High Load',
            default => '⚪ Unknown'
        };
    }
}
