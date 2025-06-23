<?php

namespace App\Jobs;

use App\Services\ResourceUsageMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Micro-task 2.3.1.4: Resource usage monitoring (3h)
 * Job for processing resource monitoring data asynchronously
 */
class ProcessResourceMonitoringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $config;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $options = [])
    {
        $this->config = config('xsmb.resource_monitoring', []);
        $this->queue = 'resource-monitoring';
    }

    /**
     * Execute the job.
     */
    public function handle(ResourceUsageMonitoringService $resourceMonitor): void
    {
        try {
            Log::info('Starting resource monitoring job', $this->options);

            // Get system resource usage
            $systemUsage = $resourceMonitor->getSystemResourceUsage();

            // Cache the results
            $this->cacheResourceData($systemUsage);

            // Check for alerts
            $this->processResourceAlerts($systemUsage);

            // Cleanup old data if configured
            if ($this->config['optimization']['auto_cleanup_logs'] ?? false) {
                $this->performCleanupTasks();
            }

            // Log success
            Log::info('Resource monitoring job completed successfully', [
                'health_score' => $systemUsage['health_score'],
                'memory_usage' => $systemUsage['memory']['usage_percent'],
                'disk_usage' => $systemUsage['storage']['disk_usage_percent'],
                'active_campaigns' => $systemUsage['campaigns']['total_campaigns']
            ]);

        } catch (\Exception $e) {
            Log::error('Resource monitoring job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'options' => $this->options
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Cache resource monitoring data
     */
    protected function cacheResourceData(array $systemUsage): void
    {
        $cacheDuration = $this->config['cache_duration'] ?? 300;

        // Cache overall system usage
        Cache::put('system_resource_overview', $systemUsage, $cacheDuration);

        // Cache health metrics for dashboard
        $healthMetrics = [
            'health_score' => $systemUsage['health_score'],
            'memory_health' => $systemUsage['memory']['health_status'],
            'storage_health' => $systemUsage['storage']['health_status'],
            'database_health' => $systemUsage['database']['health_status'],
            'queue_health' => $systemUsage['queue']['health_status'],
            'cpu_health' => $systemUsage['cpu']['health_status'],
            'active_campaigns' => $systemUsage['campaigns']['total_campaigns'],
            'active_users' => $systemUsage['users']['active_users_24h'],
            'resource_efficiency' => $systemUsage['campaigns']['resource_efficiency'],
            'alert_level' => $this->calculateAlertLevel($systemUsage),
            'last_updated' => now()
        ];

        Cache::put('health_metrics_dashboard', $healthMetrics, 60); // Shorter cache for dashboard

        // Cache historical data for trends
        $this->cacheHistoricalData($systemUsage);

        Log::info('Resource data cached successfully');
    }

    /**
     * Process resource alerts
     */
    protected function processResourceAlerts(array $systemUsage): void
    {
        $alerts = $this->generateResourceAlerts($systemUsage);

        if (empty($alerts)) {
            Log::info('No resource alerts generated');
            return;
        }

        // Cache current alerts
        Cache::put('current_resource_alerts', $alerts, 600); // 10 minutes cache

        // Process critical alerts immediately
        $criticalAlerts = array_filter($alerts, fn($alert) => $alert['severity'] === 'critical');

        if (!empty($criticalAlerts)) {
            $this->processCriticalAlerts($criticalAlerts, $systemUsage);
        }

        // Process warning alerts with throttling
        $warningAlerts = array_filter($alerts, fn($alert) => $alert['severity'] === 'warning');

        if (!empty($warningAlerts)) {
            $this->processWarningAlerts($warningAlerts);
        }

        Log::info('Resource alerts processed', [
            'total_alerts' => count($alerts),
            'critical_alerts' => count($criticalAlerts),
            'warning_alerts' => count($warningAlerts)
        ]);
    }

    /**
     * Process critical alerts with immediate action
     */
    protected function processCriticalAlerts(array $criticalAlerts, array $systemUsage): void
    {
        foreach ($criticalAlerts as $alert) {
            // Log critical alert
            Log::critical('Critical resource alert triggered', $alert);

            // Take automatic actions based on alert type
            switch ($alert['type']) {
                case 'memory':
                    $this->handleCriticalMemoryAlert($alert, $systemUsage);
                    break;

                case 'storage':
                    $this->handleCriticalStorageAlert($alert, $systemUsage);
                    break;

                case 'database':
                    $this->handleCriticalDatabaseAlert($alert, $systemUsage);
                    break;

                case 'queue':
                    $this->handleCriticalQueueAlert($alert, $systemUsage);
                    break;
            }

            // Send notifications for critical alerts
            $this->sendCriticalAlertNotification($alert);
        }
    }

    /**
     * Process warning alerts with throttling
     */
    protected function processWarningAlerts(array $warningAlerts): void
    {
        $throttleMinutes = $this->config['notification_settings']['warning_throttle_minutes'] ?? 30;

        foreach ($warningAlerts as $alert) {
            $throttleKey = "warning_alert_throttle_{$alert['type']}";

            if (!Cache::has($throttleKey)) {
                // Send warning notification
                Log::warning('Resource warning alert', $alert);

                // Set throttle
                Cache::put($throttleKey, true, $throttleMinutes * 60);

                // Send notification
                $this->sendWarningAlertNotification($alert);
            }
        }
    }

    /**
     * Handle critical memory alert
     */
    protected function handleCriticalMemoryAlert(array $alert, array $systemUsage): void
    {
        // Log memory details
        Log::critical('Critical memory usage detected', [
            'current_usage_mb' => $systemUsage['memory']['current_usage_mb'],
            'peak_usage_mb' => $systemUsage['memory']['peak_usage_mb'],
            'usage_percent' => $systemUsage['memory']['usage_percent'],
            'recommendations' => $systemUsage['memory']['recommendations']
        ]);

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
            Log::info('Forced garbage collection to free memory');
        }

        // Clear some caches to free memory
        Cache::flush();
        Log::info('Cleared cache to free memory');
    }

    /**
     * Handle critical storage alert
     */
    protected function handleCriticalStorageAlert(array $alert, array $systemUsage): void
    {
        // Log storage details
        Log::critical('Critical disk usage detected', [
            'disk_usage_percent' => $systemUsage['storage']['disk_usage_percent'],
            'free_space_gb' => $systemUsage['storage']['free_disk_space_gb'],
            'logs_size_mb' => $systemUsage['storage']['logs_size_mb']
        ]);

        // Auto cleanup if enabled
        if ($this->config['optimization']['auto_cleanup_logs'] ?? false) {
            $this->performEmergencyCleanup();
        }
    }

    /**
     * Handle critical database alert
     */
    protected function handleCriticalDatabaseAlert(array $alert, array $systemUsage): void
    {
        Log::critical('Critical database usage detected', [
            'connection_usage_percent' => $systemUsage['database']['connection_usage_percent'],
            'active_connections' => $systemUsage['database']['active_connections'],
            'slow_queries' => $systemUsage['database']['slow_queries']
        ]);

        // Could implement database connection cleanup here
        // For now, just log the issue
    }

    /**
     * Handle critical queue alert
     */
    protected function handleCriticalQueueAlert(array $alert, array $systemUsage): void
    {
        Log::critical('Critical queue load detected', [
            'total_pending_jobs' => $systemUsage['queue']['total_pending_jobs'],
            'failed_jobs' => $systemUsage['queue']['failed_jobs'],
            'processing_rate' => $systemUsage['queue']['processing_rate']
        ]);

        // Could implement queue optimization here
        // For now, just log the issue
    }

    /**
     * Perform cleanup tasks
     */
    protected function performCleanupTasks(): void
    {
        $cleanupIntervalDays = $this->config['optimization']['cleanup_interval_days'] ?? 7;
        $maxLogSizeMB = $this->config['optimization']['max_log_size_mb'] ?? 100;

        try {
            // Clean old logs
            $this->cleanupOldLogs($cleanupIntervalDays);

            // Clean large log files
            $this->cleanupLargeLogFiles($maxLogSizeMB);

            // Clean cache if enabled
            if ($this->config['optimization']['auto_cleanup_cache'] ?? false) {
                $this->cleanupOldCache();
            }

            Log::info('Cleanup tasks completed successfully');

        } catch (\Exception $e) {
            Log::error('Cleanup tasks failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Perform emergency cleanup during critical storage alert
     */
    protected function performEmergencyCleanup(): void
    {
        try {
            // Clean logs older than 1 day in emergency
            $this->cleanupOldLogs(1);

            // Clean all large log files
            $this->cleanupLargeLogFiles(10); // 10MB limit in emergency

            // Clear all cache
            Cache::flush();

            Log::info('Emergency cleanup completed');

        } catch (\Exception $e) {
            Log::error('Emergency cleanup failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clean up old log files
     */
    protected function cleanupOldLogs(int $days): void
    {
        $logPath = storage_path('logs');
        $cutoffDate = now()->subDays($days);

        if (!is_dir($logPath)) return;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($logPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $deletedFiles = 0;
        $deletedSize = 0;

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoffDate->timestamp) {
                $size = $file->getSize();
                if (unlink($file->getPathname())) {
                    $deletedFiles++;
                    $deletedSize += $size;
                }
            }
        }

        Log::info('Old logs cleaned up', [
            'deleted_files' => $deletedFiles,
            'deleted_size_mb' => round($deletedSize / 1024 / 1024, 2),
            'days_older_than' => $days
        ]);
    }

    /**
     * Clean up large log files
     */
    protected function cleanupLargeLogFiles(int $maxSizeMB): void
    {
        $logPath = storage_path('logs');
        $maxSize = $maxSizeMB * 1024 * 1024;

        if (!is_dir($logPath)) return;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($logPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $cleanedFiles = 0;
        $cleanedSize = 0;

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getSize() > $maxSize) {
                $originalSize = $file->getSize();

                // Truncate file instead of deleting to preserve log structure
                if (file_put_contents($file->getPathname(), '')) {
                    $cleanedFiles++;
                    $cleanedSize += $originalSize;
                }
            }
        }

        Log::info('Large log files cleaned up', [
            'cleaned_files' => $cleanedFiles,
            'cleaned_size_mb' => round($cleanedSize / 1024 / 1024, 2),
            'max_size_mb' => $maxSizeMB
        ]);
    }

    /**
     * Clean up old cache data
     */
    protected function cleanupOldCache(): void
    {
        // Clean specific cache patterns that might be old
        $patterns = [
            'system_resource_*',
            'campaign_metrics_*',
            'user_performance_*'
        ];

        $clearedKeys = 0;

        foreach ($patterns as $pattern) {
            // Note: This is a simplified cache cleanup
            // In a real implementation, you'd want more sophisticated cache management
            Cache::forget($pattern);
            $clearedKeys++;
        }

        Log::info('Old cache cleaned up', [
            'cleared_patterns' => count($patterns)
        ]);
    }

    /**
     * Cache historical data for trend analysis
     */
    protected function cacheHistoricalData(array $systemUsage): void
    {
        $historicalData = [
            'timestamp' => $systemUsage['timestamp'],
            'health_score' => $systemUsage['health_score'],
            'memory_usage_percent' => $systemUsage['memory']['usage_percent'],
            'disk_usage_percent' => $systemUsage['storage']['disk_usage_percent'],
            'database_connections_percent' => $systemUsage['database']['connection_usage_percent'],
            'queue_pending_jobs' => $systemUsage['queue']['total_pending_jobs'],
            'cpu_load' => $systemUsage['cpu']['estimated_cpu_load'],
            'active_campaigns' => $systemUsage['campaigns']['total_campaigns'],
            'active_users' => $systemUsage['users']['active_users_24h']
        ];

        // Store in a list for trend analysis (keep last 24 hours)
        $historyKey = 'resource_monitoring_history';
        $history = Cache::get($historyKey, []);

        // Add new data point
        $history[] = $historicalData;

        // Keep only last 288 entries (24 hours with 5-minute intervals)
        if (count($history) > 288) {
            $history = array_slice($history, -288);
        }

        // Cache for 25 hours to ensure we don't lose data
        Cache::put($historyKey, $history, 25 * 60 * 60);
    }

    /**
     * Generate resource alerts
     */
    protected function generateResourceAlerts(array $systemUsage): array
    {
        $alerts = [];
        $thresholds = $this->config['alert_thresholds'] ?? [];

        // Memory alert
        if (($systemUsage['memory']['usage_percent'] ?? 0) > ($thresholds['memory_usage_percent'] ?? 85)) {
            $alerts[] = [
                'type' => 'memory',
                'severity' => 'critical',
                'title' => 'High Memory Usage',
                'message' => "Memory usage at {$systemUsage['memory']['usage_percent']}%",
                'current_value' => $systemUsage['memory']['usage_percent'],
                'threshold' => $thresholds['memory_usage_percent'],
                'timestamp' => now(),
                'recommendations' => $systemUsage['memory']['recommendations'] ?? []
            ];
        }

        // Storage alert
        if (($systemUsage['storage']['disk_usage_percent'] ?? 0) > ($thresholds['disk_usage_percent'] ?? 90)) {
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
        if (($systemUsage['database']['connection_usage_percent'] ?? 0) > ($thresholds['database_connections_percent'] ?? 80)) {
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
        if (($systemUsage['queue']['total_pending_jobs'] ?? 0) > ($thresholds['queue_size'] ?? 1000)) {
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

    /**
     * Calculate alert level
     */
    protected function calculateAlertLevel(array $systemUsage): string
    {
        $alerts = $this->generateResourceAlerts($systemUsage);
        $criticalCount = count(array_filter($alerts, fn($alert) => $alert['severity'] === 'critical'));

        if ($criticalCount > 0) return 'critical';
        if (count($alerts) > 0) return 'warning';
        return 'normal';
    }

    /**
     * Send critical alert notification
     */
    protected function sendCriticalAlertNotification(array $alert): void
    {
        // In a real implementation, this would send emails, SMS, Slack notifications, etc.
        Log::critical('CRITICAL ALERT NOTIFICATION', $alert);

        // Could dispatch additional notification jobs here
        // dispatch(new SendEmailAlertJob($alert));
        // dispatch(new SendSlackAlertJob($alert));
    }

    /**
     * Send warning alert notification
     */
    protected function sendWarningAlertNotification(array $alert): void
    {
        // In a real implementation, this would send warning notifications
        Log::warning('WARNING ALERT NOTIFICATION', $alert);

        // Could dispatch additional notification jobs here
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Resource monitoring job failed completely', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'options' => $this->options
        ]);

        // Could send failure notifications here
    }
}
