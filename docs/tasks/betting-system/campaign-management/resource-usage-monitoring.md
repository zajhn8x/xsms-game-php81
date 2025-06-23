# Micro-task 2.3.1.4: Resource Usage Monitoring

## Tổng quan

Micro-task 2.3.1.4 triển khai hệ thống giám sát tài nguyên hệ thống toàn diện cho XSMB Game Platform, cho phép theo dõi và quản lý việc sử dụng tài nguyên của campaigns và hệ thống.

## Thành phần chính

### 1. ResourceUsageMonitoringService

**File**: `app/Services/ResourceUsageMonitoringService.php`

Service chính để monitor resource usage của system và campaigns:

- **System monitoring**: Memory, Storage, Database, Queue, CPU usage
- **Campaign monitoring**: Per-campaign resource analysis
- **User monitoring**: Heavy users identification
- **Health scoring**: Overall system health assessment
- **Optimization recommendations**: Automatic suggestions

### 2. MonitorResourceUsage Command

**File**: `app/Console/Commands/MonitorResourceUsage.php`

CLI command với nhiều options:

```bash
php artisan monitor:resources [options]
```

**Options:**
- `--detailed`: Show detailed resource breakdown
- `--alerts`: Only show resources that exceed thresholds
- `--campaigns`: Show per-campaign resource usage
- `--users`: Show heavy users
- `--recommendations`: Show optimization recommendations
- `--json`: Output in JSON format
- `--save`: Save monitoring data to log

### 3. ResourceMonitoringController

**File**: `app/Http/Controllers/ResourceMonitoringController.php`

REST API controller với các endpoints:

```php
// System Overview
GET /resource-monitoring/system/overview

// Detailed Breakdown (Admin only)
GET /resource-monitoring/system/detailed

// Resource Alerts
GET /resource-monitoring/alerts

// Campaign Resource Usage
GET /resource-monitoring/campaigns
GET /resource-monitoring/campaigns/{campaign}

// User Resource Usage
GET /resource-monitoring/users

// Optimization Recommendations
GET /resource-monitoring/recommendations

// Health Metrics for Dashboard
GET /resource-monitoring/health-metrics

// Refresh Cache (Admin only)
POST /resource-monitoring/refresh-cache
```

### 4. ProcessResourceMonitoringJob

**File**: `app/Jobs/ProcessResourceMonitoringJob.php`

Async job để xử lý resource monitoring:

- **Automatic monitoring**: Background resource monitoring
- **Alert processing**: Critical và warning alerts
- **Auto cleanup**: Logs và cache cleanup
- **Emergency actions**: Automatic resource cleanup
- **Historical data**: Trend analysis caching

### 5. Configuration System

**File**: `config/xsmb.php` (resource_monitoring section)

```php
'resource_monitoring' => [
    'enabled' => env('RESOURCE_MONITORING_ENABLED', true),
    'monitoring_interval' => 300, // 5 minutes
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
]
```

## Chức năng chính

### 1. System Resource Monitoring

**Memory Usage:**
```php
[
    'current_usage_mb' => 512.45,
    'peak_usage_mb' => 768.32,
    'memory_limit_mb' => 2048,
    'available_mb' => 1535.55,
    'usage_percent' => 25.02,
    'health_status' => 'good'
]
```

**Storage Usage:**
```php
[
    'total_disk_space_gb' => 500.00,
    'free_disk_space_gb' => 150.25,
    'used_disk_space_gb' => 349.75,
    'disk_usage_percent' => 69.95,
    'logs_size_mb' => 250.5,
    'cache_size_mb' => 128.7,
    'health_status' => 'good'
]
```

**Database Usage:**
```php
[
    'database_size_mb' => 1024.50,
    'active_connections' => 15,
    'max_connections' => 151,
    'connection_usage_percent' => 9.93,
    'slow_queries' => 5,
    'table_sizes' => [...],
    'health_status' => 'good'
]
```

### 2. Campaign Resource Analysis

```php
[
    'campaign_id' => 123,
    'memory_usage_mb' => 2.5,
    'database_queries_per_hour' => 150,
    'storage_usage_mb' => 0.8,
    'network_requests_per_hour' => 50,
    'resource_intensity' => 'medium',
    'efficiency_score' => 75,
    'optimization_potential' => 'low'
]
```

### 3. Health Scoring System

Hệ thống tính điểm sức khỏe tổng thể (0-100):

- **Memory Health**: 20% weight
- **Storage Health**: 15% weight  
- **Database Health**: 25% weight
- **Queue Health**: 20% weight
- **CPU Health**: 20% weight

**Health Status Levels:**
- `excellent`: 80-100
- `good`: 60-79
- `warning`: 40-59
- `critical`: 0-39

### 4. Alert System

**Alert Types:**
- `memory`: Memory usage alerts
- `storage`: Disk space alerts
- `database`: Database connection alerts
- `queue`: Queue processing alerts
- `cpu`: CPU load alerts

**Alert Severities:**
- `critical`: Immediate action required
- `warning`: Attention needed
- `info`: Informational only

**Alert Processing:**
- Critical alerts: Immediate processing + automatic actions
- Warning alerts: Throttled notifications (30 min default)
- Auto cleanup: Emergency cleanup for critical storage alerts

### 5. Optimization Recommendations

```php
[
    'type' => 'campaign_optimization',
    'priority' => 'high',
    'title' => 'Multiple Heavy Resource Campaigns',
    'description' => 'Several campaigns are using significant resources.',
    'impact' => 'high'
]
```

**Recommendation Types:**
- `campaign_optimization`: Campaign resource optimization
- `campaign_cleanup`: Inactive campaign cleanup
- `user_optimization`: User resource optimization
- `system_optimization`: System-level optimization

## API Usage Examples

### 1. Get System Overview

```bash
curl -X GET /resource-monitoring/system/overview \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
    "success": true,
    "data": {
        "system_health": {
            "health_score": 85,
            "timestamp": "2025-01-23T10:30:00Z",
            "status": "excellent"
        },
        "resources": {
            "memory": {
                "usage_percent": 25.02,
                "current_usage_mb": 512.45,
                "health_status": "good"
            },
            "storage": {
                "usage_percent": 69.95,
                "used_space_gb": 349.75,
                "health_status": "good"
            }
        },
        "activity": {
            "active_campaigns": 15,
            "active_users_24h": 45,
            "resource_efficiency": 4
        }
    }
}
```

### 2. Get Resource Alerts

```bash
curl -X GET /resource-monitoring/alerts \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
    "success": true,
    "data": {
        "alerts": [
            {
                "type": "memory",
                "severity": "warning",
                "title": "High Memory Usage",
                "message": "Memory usage at 87%",
                "current_value": 87,
                "threshold": 85,
                "timestamp": "2025-01-23T10:30:00Z"
            }
        ],
        "alert_count": 1,
        "critical_count": 0,
        "warning_count": 1
    }
}
```

### 3. Get Campaign Resource Usage

```bash
curl -X GET /resource-monitoring/campaigns/123 \
  -H "Authorization: Bearer {token}"
```

### 4. Get Health Metrics (Dashboard)

```bash
curl -X GET /resource-monitoring/health-metrics \
  -H "Authorization: Bearer {token}"
```

## CLI Usage Examples

### 1. Basic Monitoring

```bash
php artisan monitor:resources
```

**Output:**
```
🔍 Bắt đầu giám sát Resource Usage...

📊 SYSTEM RESOURCE OVERVIEW
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

┌─────────────────────────┬────────────────────────┬──────────────┐
│ Metric                  │ Value                  │ Status       │
├─────────────────────────┼────────────────────────┼──────────────┤
│ Overall Health Score    │ 85/100                 │ 🟢 Excellent │
│ Memory Usage            │ 512MB (25%)            │ 🟢 Good      │
│ Disk Usage              │ 350GB (70%)            │ 🟢 Good      │
│ Database Connections    │ 15/151 (10%)           │ 🟢 Good      │
│ Queue Jobs              │ 5                      │ 🟢 Good      │
│ CPU Load (Estimated)    │ 1.2                    │ 🟢 Good      │
│ Active Campaigns        │ 15                     │ 📈           │
│ Active Users (24h)      │ 45                     │ 👥           │
└─────────────────────────┴────────────────────────┴──────────────┘

✅ Resource monitoring hoàn thành trong 125.45ms
```

### 2. Detailed Breakdown

```bash
php artisan monitor:resources --detailed
```

### 3. Alerts Only

```bash
php artisan monitor:resources --alerts
```

### 4. Campaign Analysis

```bash
php artisan monitor:resources --campaigns
```

### 5. JSON Output

```bash
php artisan monitor:resources --json > resource_report.json
```

### 6. Save to Log

```bash
php artisan monitor:resources --save
```

## Async Processing

### 1. Dispatch Monitoring Job

```php
use App\Jobs\ProcessResourceMonitoringJob;

// Basic monitoring
ProcessResourceMonitoringJob::dispatch();

// With options
ProcessResourceMonitoringJob::dispatch([
    'detailed' => true,
    'cleanup' => true
]);
```

### 2. Schedule Regular Monitoring

**File**: `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // Run every 5 minutes
    $schedule->job(new ProcessResourceMonitoringJob())
             ->everyFiveMinutes()
             ->onQueue('resource-monitoring');
             
    // Run detailed analysis hourly
    $schedule->job(new ProcessResourceMonitoringJob(['detailed' => true]))
             ->hourly()
             ->onQueue('resource-monitoring');
}
```

## Programmatic Usage

### 1. Basic Service Usage

```php
use App\Services\ResourceUsageMonitoringService;

$resourceMonitor = app(ResourceUsageMonitoringService::class);

// Get system overview
$systemUsage = $resourceMonitor->getSystemResourceUsage();

// Get campaign analysis
$campaignUsage = $resourceMonitor->getCampaignResourceUsage($campaign);
```

### 2. Cache Integration

```php
use Illuminate\Support\Facades\Cache;

// Get cached health metrics
$healthMetrics = Cache::get('health_metrics_dashboard');

// Get cached alerts
$alerts = Cache::get('current_resource_alerts', []);

// Get historical data
$history = Cache::get('resource_monitoring_history', []);
```

### 3. Custom Monitoring

```php
class CustomResourceMonitor
{
    public function checkCampaignHealth(Campaign $campaign): array
    {
        $resourceMonitor = app(ResourceUsageMonitoringService::class);
        
        $usage = $resourceMonitor->getCampaignResourceUsage($campaign);
        
        $health = [
            'efficiency' => $usage['efficiency_score'],
            'intensity' => $usage['resource_intensity'],
            'optimization' => $usage['optimization_potential']
        ];
        
        return $health;
    }
}
```

## Environment Variables

```env
# Resource Monitoring
RESOURCE_MONITORING_ENABLED=true
RESOURCE_MONITORING_INTERVAL=300
RESOURCE_MONITORING_CACHE_DURATION=300

# Alert Thresholds
RESOURCE_ALERT_MEMORY_PERCENT=85
RESOURCE_ALERT_DISK_PERCENT=90
RESOURCE_ALERT_DB_CONNECTIONS_PERCENT=80
RESOURCE_ALERT_QUEUE_SIZE=1000
RESOURCE_ALERT_CPU_LOAD=3.0

# Heavy Usage Thresholds
RESOURCE_HEAVY_USER_CAMPAIGNS=5
RESOURCE_HEAVY_CAMPAIGN_BETS=100
RESOURCE_HEAVY_NETWORK_REQUESTS=10000

# Auto Cleanup
RESOURCE_AUTO_CLEANUP_LOGS=true
RESOURCE_AUTO_CLEANUP_CACHE=true
RESOURCE_CLEANUP_INTERVAL_DAYS=7
RESOURCE_MAX_LOG_SIZE_MB=100
```

## Performance Considerations

### 1. Caching Strategy

- **System Overview**: 5 minutes cache
- **Health Metrics**: 1 minute cache (dashboard)
- **Historical Data**: 25 hours cache (trend analysis)
- **Alerts**: 10 minutes cache

### 2. Resource Efficiency

- **Lazy Loading**: Only calculate metrics when needed
- **Batch Processing**: Group database queries
- **Memory Management**: Use generators for large datasets
- **Queue Processing**: Async processing for heavy operations

### 3. Database Optimization

```sql
-- Indexes for performance monitoring
ALTER TABLE campaigns ADD INDEX idx_status_created (status, created_at);
ALTER TABLE campaign_bets ADD INDEX idx_created_campaign (created_at, campaign_id);
ALTER TABLE users ADD INDEX idx_last_login (last_login_at);
```

## Security

### 1. Access Control

- **Admin Only**: Detailed system breakdown, cache refresh
- **User Scope**: Users can only see their own resource usage
- **Sensitive Data**: Filter sensitive information for non-admin users

### 2. Rate Limiting

```php
// In controller
$this->middleware('throttle:60,1')->only(['getSystemOverview']);
$this->middleware('throttle:10,1')->only(['refreshCache']);
```

### 3. Data Privacy

- **Personal Data**: No personal information in logs
- **Sensitive Metrics**: Admin-only access to detailed metrics
- **Audit Trail**: Log all admin actions

## Testing

### 1. Unit Tests

```php
class ResourceUsageMonitoringServiceTest extends TestCase
{
    public function test_get_system_resource_usage()
    {
        $service = app(ResourceUsageMonitoringService::class);
        $usage = $service->getSystemResourceUsage();
        
        $this->assertArrayHasKey('health_score', $usage);
        $this->assertArrayHasKey('memory', $usage);
        $this->assertArrayHasKey('storage', $usage);
        $this->assertArrayHasKey('database', $usage);
    }
    
    public function test_campaign_resource_analysis()
    {
        $campaign = Campaign::factory()->create();
        
        $service = app(ResourceUsageMonitoringService::class);
        $usage = $service->getCampaignResourceUsage($campaign);
        
        $this->assertArrayHasKey('efficiency_score', $usage);
        $this->assertArrayHasKey('resource_intensity', $usage);
    }
}
```

### 2. Feature Tests

```php
class ResourceMonitoringControllerTest extends TestCase
{
    public function test_system_overview_endpoint()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                         ->getJson('/resource-monitoring/system/overview');
                         
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'system_health',
                         'resources',
                         'activity'
                     ]
                 ]);
    }
}
```

### 3. Command Tests

```bash
# Test command help
php artisan monitor:resources --help

# Test basic execution
php artisan monitor:resources

# Test JSON output
php artisan monitor:resources --json
```

## Troubleshooting

### 1. Common Issues

**Memory Limit Exceeded:**
```bash
# Increase PHP memory limit
php -d memory_limit=512M artisan monitor:resources
```

**Database Connection Issues:**
```php
// Check database configuration
DB::select("SHOW STATUS LIKE 'Threads_connected'");
DB::select("SHOW VARIABLES LIKE 'max_connections'");
```

**Queue Processing Problems:**
```bash
# Check queue status
php artisan queue:work --queue=resource-monitoring

# Restart queue workers
php artisan queue:restart
```

### 2. Performance Issues

**Slow Monitoring:**
- Increase cache duration
- Use database indexes
- Optimize heavy calculations
- Use async processing

**High Memory Usage:**
- Enable auto cleanup
- Reduce monitoring frequency
- Optimize data structures
- Use streaming for large datasets

### 3. Alert Issues

**Missing Alerts:**
- Check threshold configuration
- Verify job processing
- Check notification settings
- Review throttle configuration

**False Alerts:**
- Adjust thresholds
- Review calculation methods
- Check data accuracy
- Implement alert suppression

## Future Enhancements

### 1. Advanced Analytics

- **Trend Analysis**: Historical resource usage trends
- **Predictive Analytics**: Resource usage forecasting
- **Anomaly Detection**: Unusual pattern detection
- **Performance Correlation**: Campaign performance vs resource usage

### 2. Enhanced Monitoring

- **Real-time Monitoring**: WebSocket-based real-time updates
- **Custom Metrics**: User-defined monitoring metrics
- **External Integration**: Third-party monitoring services
- **Mobile Alerts**: Push notifications for mobile apps

### 3. Optimization Features

- **Auto Scaling**: Automatic resource scaling recommendations
- **Load Balancing**: Resource distribution optimization
- **Smart Cleanup**: AI-powered cleanup recommendations
- **Resource Scheduling**: Intelligent resource allocation

### 4. Reporting

- **Executive Reports**: High-level resource utilization reports
- **Cost Analysis**: Resource cost tracking and optimization
- **Compliance Reports**: Resource usage compliance monitoring
- **Custom Dashboards**: User-configurable monitoring dashboards

## Kết luận

Micro-task 2.3.1.4: Resource Usage Monitoring cung cấp hệ thống giám sát tài nguyên toàn diện cho XSMB Game Platform với:

✅ **System Monitoring**: Giám sát toàn diện memory, storage, database, queue, CPU
✅ **Campaign Analysis**: Phân tích resource usage per-campaign
✅ **Health Scoring**: Hệ thống đánh giá sức khỏe tự động
✅ **Alert System**: Cảnh báo và xử lý tự động
✅ **Optimization**: Đề xuất tối ưu hóa thông minh
✅ **CLI Tools**: Command line interface mạnh mẽ
✅ **REST API**: API đầy đủ cho web integration
✅ **Async Processing**: Xử lý bất đồng bộ hiệu quả
✅ **Auto Cleanup**: Tự động dọn dẹp tài nguyên

 