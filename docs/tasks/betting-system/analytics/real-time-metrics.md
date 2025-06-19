# Real-time Metrics System

## Tổng quan
Xây dựng hệ thống metrics real-time để theo dõi hiệu suất hệ thống đặt cược, campaign performance và user activities.

## Mục tiêu
- Real-time dashboard metrics
- Performance monitoring
- Alert system for anomalies
- WebSocket-based updates
- Configurable KPI tracking
- Historical trend analysis

## Key Metrics Categories

### 1. System Metrics
- Active users count
- Campaign status distribution
- Betting volume (real-time)
- Success rates
- Revenue metrics

### 2. Campaign Metrics
- Live campaign performance
- Auto-betting effectiveness
- Risk management triggers
- Profit/loss tracking

### 3. User Metrics
- User engagement
- Betting patterns
- Wallet activity
- Social interactions

## Implementation Steps

### Step 1: Metrics Collection Service
```php
// app/Services/RealTimeMetricsService.php
class RealTimeMetricsService
{
    protected $redis;
    protected $cache;

    public function __construct()
    {
        $this->redis = Redis::connection('metrics');
        $this->cache = Cache::store('redis');
    }

    public function collectSystemMetrics(): array
    {
        return [
            'active_users' => $this->getActiveUsersCount(),
            'active_campaigns' => $this->getActiveCampaignsCount(),
            'total_betting_volume' => $this->getTotalBettingVolume(),
            'success_rate' => $this->getSystemSuccessRate(),
            'revenue_today' => $this->getRevenueToday(),
            'new_registrations' => $this->getNewRegistrationsToday(),
            'system_health' => $this->getSystemHealth(),
            'timestamp' => now()->toISOString()
        ];
    }

    public function collectCampaignMetrics($timeframe = '1h'): array
    {
        return [
            'campaign_performance' => $this->getCampaignPerformance($timeframe),
            'auto_betting_stats' => $this->getAutoBettingStats($timeframe),
            'risk_triggers' => $this->getRiskTriggers($timeframe),
            'top_performers' => $this->getTopPerformingCampaigns($timeframe),
            'timestamp' => now()->toISOString()
        ];
    }

    public function collectUserMetrics($timeframe = '1h'): array
    {
        return [
            'user_activity' => $this->getUserActivityStats($timeframe),
            'betting_patterns' => $this->getBettingPatterns($timeframe),
            'wallet_activity' => $this->getWalletActivity($timeframe),
            'engagement_metrics' => $this->getEngagementMetrics($timeframe),
            'timestamp' => now()->toISOString()
        ];
    }

    protected function getActiveUsersCount(): int
    {
        return $this->cache->remember('metrics:active_users', 300, function () {
            return User::where('last_login_at', '>=', now()->subMinutes(30))->count();
        });
    }

    protected function getActiveCampaignsCount(): array
    {
        return $this->cache->remember('metrics:active_campaigns', 300, function () {
            $campaigns = Campaign::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();

            return [
                'total' => $campaigns->sum('count'),
                'by_status' => $campaigns->pluck('count', 'status')->toArray(),
                'auto_betting' => Campaign::whereIn('betting_strategy', [
                    'auto_heatmap', 'auto_streak', 'auto_pattern', 'auto_hybrid'
                ])->whereIn('status', ['active', 'running'])->count()
            ];
        });
    }

    protected function getTotalBettingVolume(): array
    {
        $key = 'metrics:betting_volume:' . now()->format('Y-m-d-H');
        
        return $this->cache->remember($key, 900, function () {
            $today = now()->startOfDay();
            
            return [
                'today' => CampaignBet::whereDate('created_at', $today)->sum('amount'),
                'last_hour' => CampaignBet::where('created_at', '>=', now()->subHour())->sum('amount'),
                'last_24h' => CampaignBet::where('created_at', '>=', now()->subDay())->sum('amount'),
                'current_trend' => $this->getBettingTrend()
            ];
        });
    }

    protected function getSystemSuccessRate(): array
    {
        return $this->cache->remember('metrics:success_rate', 600, function () {
            $totalBets = CampaignBet::whereDate('created_at', today())->count();
            $winBets = CampaignBet::whereDate('created_at', today())
                ->where('is_win', true)->count();

            $overallTotal = CampaignBet::count();
            $overallWins = CampaignBet::where('is_win', true)->count();

            return [
                'today' => $totalBets > 0 ? round(($winBets / $totalBets) * 100, 2) : 0,
                'overall' => $overallTotal > 0 ? round(($overallWins / $overallTotal) * 100, 2) : 0,
                'trend' => $this->getSuccessRateTrend()
            ];
        });
    }

    protected function getRevenueToday(): array
    {
        return $this->cache->remember('metrics:revenue_today', 600, function () {
            $today = now()->startOfDay();
            
            $totalBets = CampaignBet::whereDate('created_at', $today)->sum('amount');
            $totalWinnings = CampaignBet::whereDate('created_at', $today)
                ->where('is_win', true)->sum('win_amount');
            
            return [
                'gross_revenue' => $totalBets,
                'net_revenue' => $totalBets - $totalWinnings,
                'profit_margin' => $totalBets > 0 ? round((($totalBets - $totalWinnings) / $totalBets) * 100, 2) : 0,
                'hourly_breakdown' => $this->getHourlyRevenue()
            ];
        });
    }

    protected function getCampaignPerformance($timeframe): array
    {
        $startTime = $this->getTimeframeStart($timeframe);
        
        return [
            'total_campaigns' => Campaign::where('created_at', '>=', $startTime)->count(),
            'active_campaigns' => Campaign::whereIn('status', ['active', 'running'])
                ->where('updated_at', '>=', $startTime)->count(),
            'completed_campaigns' => Campaign::where('status', 'completed')
                ->where('updated_at', '>=', $startTime)->count(),
            'average_performance' => $this->getAverageCampaignPerformance($startTime),
            'performance_distribution' => $this->getPerformanceDistribution($startTime)
        ];
    }

    protected function getAutoBettingStats($timeframe): array
    {
        $startTime = $this->getTimeframeStart($timeframe);
        
        $autoBets = CampaignBet::where('created_at', '>=', $startTime)
            ->where('source', 'like', 'auto_%')
            ->get();

        $totalAuto = $autoBets->count();
        $autoWins = $autoBets->where('is_win', true)->count();

        return [
            'total_auto_bets' => $totalAuto,
            'auto_success_rate' => $totalAuto > 0 ? round(($autoWins / $totalAuto) * 100, 2) : 0,
            'by_strategy' => $autoBets->groupBy('source')->map(function ($bets, $strategy) {
                $wins = $bets->where('is_win', true)->count();
                return [
                    'total' => $bets->count(),
                    'wins' => $wins,
                    'success_rate' => $bets->count() > 0 ? round(($wins / $bets->count()) * 100, 2) : 0,
                    'volume' => $bets->sum('amount')
                ];
            }),
            'trend' => $this->getAutoBettingTrend($timeframe)
        ];
    }

    protected function getUserActivityStats($timeframe): array
    {
        $startTime = $this->getTimeframeStart($timeframe);
        
        return [
            'active_users' => User::where('last_login_at', '>=', $startTime)->count(),
            'new_registrations' => User::where('created_at', '>=', $startTime)->count(),
            'users_with_bets' => CampaignBet::where('created_at', '>=', $startTime)
                ->join('campaigns', 'campaign_bets.campaign_id', '=', 'campaigns.id')
                ->distinct('campaigns.user_id')
                ->count('campaigns.user_id'),
            'session_duration' => $this->getAverageSessionDuration($timeframe),
            'page_views' => $this->getPageViews($timeframe)
        ];
    }

    public function pushMetricsUpdate($channel, $data)
    {
        // Store in Redis for persistence
        $this->redis->setex("metrics:{$channel}:latest", 3600, json_encode($data));
        
        // Broadcast via WebSocket
        broadcast(new MetricsUpdated($channel, $data));
        
        // Store historical data
        $this->storeHistoricalMetrics($channel, $data);
    }

    public function getHistoricalMetrics($channel, $timeframe = '24h'): array
    {
        $startTime = $this->getTimeframeStart($timeframe);
        
        return DB::table('metrics_history')
            ->where('channel', $channel)
            ->where('created_at', '>=', $startTime)
            ->orderBy('created_at')
            ->get()
            ->map(function ($row) {
                return [
                    'timestamp' => $row->created_at,
                    'data' => json_decode($row->data, true)
                ];
            })
            ->toArray();
    }

    public function getMetricsTrends($metrics, $timeframe = '24h'): array
    {
        $historical = $this->getHistoricalMetrics('system', $timeframe);
        
        $trends = [];
        foreach ($metrics as $metric) {
            $values = collect($historical)->pluck("data.{$metric}")->filter()->values();
            
            if ($values->count() >= 2) {
                $current = $values->last();
                $previous = $values->get($values->count() - 2);
                
                $change = $previous != 0 ? (($current - $previous) / $previous) * 100 : 0;
                
                $trends[$metric] = [
                    'current' => $current,
                    'previous' => $previous,
                    'change_percent' => round($change, 2),
                    'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')
                ];
            }
        }
        
        return $trends;
    }

    protected function storeHistoricalMetrics($channel, $data)
    {
        DB::table('metrics_history')->insert([
            'channel' => $channel,
            'data' => json_encode($data),
            'created_at' => now()
        ]);

        // Clean old data (keep only last 30 days)
        DB::table('metrics_history')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();
    }

    protected function getTimeframeStart($timeframe): Carbon
    {
        switch ($timeframe) {
            case '1h':
                return now()->subHour();
            case '6h':
                return now()->subHours(6);
            case '24h':
                return now()->subDay();
            case '7d':
                return now()->subWeek();
            case '30d':
                return now()->subMonth();
            default:
                return now()->subHour();
        }
    }
}
```

### Step 2: Real-time Metrics Job
```php
// app/Jobs/CollectRealTimeMetricsJob.php
class CollectRealTimeMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $channel;

    public function __construct($channel = 'all')
    {
        $this->channel = $channel;
    }

    public function handle(RealTimeMetricsService $metricsService)
    {
        try {
            if ($this->channel === 'all' || $this->channel === 'system') {
                $systemMetrics = $metricsService->collectSystemMetrics();
                $metricsService->pushMetricsUpdate('system', $systemMetrics);
            }

            if ($this->channel === 'all' || $this->channel === 'campaigns') {
                $campaignMetrics = $metricsService->collectCampaignMetrics();
                $metricsService->pushMetricsUpdate('campaigns', $campaignMetrics);
            }

            if ($this->channel === 'all' || $this->channel === 'users') {
                $userMetrics = $metricsService->collectUserMetrics();
                $metricsService->pushMetricsUpdate('users', $userMetrics);
            }

        } catch (\Exception $e) {
            Log::error('Metrics collection failed: ' . $e->getMessage());
        }
    }
}
```

### Step 3: WebSocket Event
```php
// app/Events/MetricsUpdated.php
class MetricsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channel;
    public $metrics;

    public function __construct($channel, $metrics)
    {
        $this->channel = $channel;
        $this->metrics = $metrics;
    }

    public function broadcastOn()
    {
        return new Channel('metrics.' . $this->channel);
    }

    public function broadcastAs()
    {
        return 'metrics.updated';
    }

    public function broadcastWith()
    {
        return [
            'channel' => $this->channel,
            'data' => $this->metrics,
            'timestamp' => now()->toISOString()
        ];
    }
}
```

### Step 4: Metrics Controller
```php
// app/Http/Controllers/Api/MetricsController.php
class MetricsController extends Controller
{
    protected $metricsService;

    public function __construct(RealTimeMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
        $this->middleware('auth:sanctum');
    }

    public function current(Request $request)
    {
        $channel = $request->get('channel', 'system');
        
        $data = match($channel) {
            'system' => $this->metricsService->collectSystemMetrics(),
            'campaigns' => $this->metricsService->collectCampaignMetrics($request->get('timeframe', '1h')),
            'users' => $this->metricsService->collectUserMetrics($request->get('timeframe', '1h')),
            default => ['error' => 'Invalid channel']
        };

        return response()->json($data);
    }

    public function historical(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:system,campaigns,users',
            'timeframe' => 'nullable|in:1h,6h,24h,7d,30d'
        ]);

        $data = $this->metricsService->getHistoricalMetrics(
            $request->channel,
            $request->get('timeframe', '24h')
        );

        return response()->json($data);
    }

    public function trends(Request $request)
    {
        $request->validate([
            'metrics' => 'required|array',
            'timeframe' => 'nullable|in:1h,6h,24h,7d,30d'
        ]);

        $trends = $this->metricsService->getMetricsTrends(
            $request->metrics,
            $request->get('timeframe', '24h')
        );

        return response()->json($trends);
    }

    public function summary()
    {
        $summary = [
            'system' => $this->metricsService->collectSystemMetrics(),
            'campaigns' => $this->metricsService->collectCampaignMetrics('1h'),
            'users' => $this->metricsService->collectUserMetrics('1h')
        ];

        return response()->json($summary);
    }

    public function export(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:system,campaigns,users',
            'timeframe' => 'required|in:1h,6h,24h,7d,30d',
            'format' => 'nullable|in:json,csv'
        ]);

        $data = $this->metricsService->getHistoricalMetrics(
            $request->channel,
            $request->timeframe
        );

        if ($request->get('format') === 'csv') {
            return $this->exportAsCsv($data, $request->channel);
        }

        return response()->json($data);
    }

    protected function exportAsCsv($data, $channel)
    {
        $filename = "metrics-{$channel}-" . now()->format('Y-m-d-H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\""
        ];

        return response()->stream(function () use ($data) {
            $file = fopen('php://output', 'w');
            
            // Write headers
            if (!empty($data)) {
                $firstRow = $data[0]['data'];
                fputcsv($file, array_merge(['timestamp'], array_keys($firstRow)));
                
                // Write data
                foreach ($data as $row) {
                    fputcsv($file, array_merge([$row['timestamp']], array_values($row['data'])));
                }
            }
            
            fclose($file);
        }, 200, $headers);
    }
}
```

### Step 5: Frontend Real-time Dashboard
```vue
<!-- resources/js/components/RealTimeMetrics.vue -->
<template>
    <div class="real-time-metrics">
        <div class="metrics-header mb-4">
            <h3>Real-time Metrics</h3>
            <div class="controls">
                <select v-model="selectedChannel" @change="switchChannel" class="form-select">
                    <option value="system">System Metrics</option>
                    <option value="campaigns">Campaign Metrics</option>
                    <option value="users">User Metrics</option>
                </select>
                <select v-model="timeframe" @change="loadMetrics" class="form-select">
                    <option value="1h">Last Hour</option>
                    <option value="6h">Last 6 Hours</option>
                    <option value="24h">Last 24 Hours</option>
                    <option value="7d">Last 7 Days</option>
                </select>
            </div>
        </div>

        <div v-if="loading" class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Loading metrics...</p>
        </div>

        <div v-else class="metrics-content">
            <!-- System Metrics -->
            <div v-if="selectedChannel === 'system'" class="system-metrics">
                <div class="row">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <h6>Active Users</h6>
                            <div class="metric-value">{{ currentMetrics.active_users || 0 }}</div>
                            <div class="metric-trend" :class="getTrendClass('active_users')">
                                <i :class="getTrendIcon('active_users')"></i>
                                {{ getTrendPercent('active_users') }}%
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <h6>Active Campaigns</h6>
                            <div class="metric-value">{{ currentMetrics.active_campaigns?.total || 0 }}</div>
                            <small class="text-muted">
                                Auto: {{ currentMetrics.active_campaigns?.auto_betting || 0 }}
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <h6>Betting Volume (Today)</h6>
                            <div class="metric-value">{{ formatCurrency(currentMetrics.total_betting_volume?.today) }}</div>
                            <div class="metric-trend" :class="getTrendClass('betting_volume')">
                                <i :class="getTrendIcon('betting_volume')"></i>
                                {{ getTrendPercent('betting_volume') }}%
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <h6>Success Rate</h6>
                            <div class="metric-value">{{ currentMetrics.success_rate?.today || 0 }}%</div>
                            <small class="text-muted">
                                Overall: {{ currentMetrics.success_rate?.overall || 0 }}%
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="chart-container mt-4">
                    <canvas ref="revenueChart"></canvas>
                </div>
            </div>

            <!-- Campaign Metrics -->
            <div v-if="selectedChannel === 'campaigns'" class="campaign-metrics">
                <div class="row">
                    <div class="col-md-6">
                        <div class="metric-card">
                            <h6>Campaign Performance</h6>
                            <div class="performance-stats">
                                <div class="stat">
                                    <span class="label">Total:</span>
                                    <span class="value">{{ currentMetrics.campaign_performance?.total_campaigns || 0 }}</span>
                                </div>
                                <div class="stat">
                                    <span class="label">Active:</span>
                                    <span class="value">{{ currentMetrics.campaign_performance?.active_campaigns || 0 }}</span>
                                </div>
                                <div class="stat">
                                    <span class="label">Completed:</span>
                                    <span class="value">{{ currentMetrics.campaign_performance?.completed_campaigns || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card">
                            <h6>Auto Betting Stats</h6>
                            <div class="auto-betting-stats">
                                <div class="stat">
                                    <span class="label">Total Bets:</span>
                                    <span class="value">{{ currentMetrics.auto_betting_stats?.total_auto_bets || 0 }}</span>
                                </div>
                                <div class="stat">
                                    <span class="label">Success Rate:</span>
                                    <span class="value">{{ currentMetrics.auto_betting_stats?.auto_success_rate || 0 }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Strategy Performance Chart -->
                <div class="chart-container mt-4">
                    <canvas ref="strategyChart"></canvas>
                </div>
            </div>

            <!-- User Metrics -->
            <div v-if="selectedChannel === 'users'" class="user-metrics">
                <div class="row">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <h6>Active Users</h6>
                            <div class="metric-value">{{ currentMetrics.user_activity?.active_users || 0 }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <h6>New Registrations</h6>
                            <div class="metric-value">{{ currentMetrics.user_activity?.new_registrations || 0 }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <h6>Users with Bets</h6>
                            <div class="metric-value">{{ currentMetrics.user_activity?.users_with_bets || 0 }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <h6>Avg Session Duration</h6>
                            <div class="metric-value">{{ formatDuration(currentMetrics.user_activity?.session_duration) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection Status -->
        <div class="connection-status" :class="connectionStatus">
            <i :class="connectionIcon"></i>
            {{ connectionText }}
            <small class="ms-2">Last update: {{ lastUpdate }}</small>
        </div>
    </div>
</template>

<script>
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

export default {
    name: 'RealTimeMetrics',
    data() {
        return {
            selectedChannel: 'system',
            timeframe: '1h',
            currentMetrics: {},
            trends: {},
            historicalData: [],
            loading: true,
            connected: false,
            lastUpdate: null,
            charts: {}
        }
    },
    computed: {
        connectionStatus() {
            return this.connected ? 'connected' : 'disconnected';
        },
        connectionIcon() {
            return this.connected ? 'fas fa-circle text-success' : 'fas fa-circle text-danger';
        },
        connectionText() {
            return this.connected ? 'Connected' : 'Disconnected';
        }
    },
    mounted() {
        this.initializeWebSocket();
        this.loadMetrics();
        this.loadTrends();
        
        // Refresh data every 5 minutes as fallback
        setInterval(() => {
            if (!this.connected) {
                this.loadMetrics();
            }
        }, 300000);
    },
    beforeUnmount() {
        this.disconnectWebSocket();
        this.destroyCharts();
    },
    methods: {
        async loadMetrics() {
            this.loading = true;
            try {
                const response = await axios.get('/api/metrics/current', {
                    params: {
                        channel: this.selectedChannel,
                        timeframe: this.timeframe
                    }
                });
                
                this.currentMetrics = response.data;
                this.lastUpdate = new Date().toLocaleTimeString();
                this.updateCharts();
            } catch (error) {
                console.error('Failed to load metrics:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadTrends() {
            try {
                const metrics = ['active_users', 'betting_volume', 'success_rate'];
                const response = await axios.get('/api/metrics/trends', {
                    params: {
                        metrics: metrics,
                        timeframe: this.timeframe
                    }
                });
                
                this.trends = response.data;
            } catch (error) {
                console.error('Failed to load trends:', error);
            }
        },

        initializeWebSocket() {
            this.echo = window.Echo.channel(`metrics.${this.selectedChannel}`)
                .listen('metrics.updated', (data) => {
                    this.currentMetrics = data.data;
                    this.lastUpdate = new Date().toLocaleTimeString();
                    this.connected = true;
                    this.updateCharts();
                });
        },

        disconnectWebSocket() {
            if (this.echo) {
                this.echo.stopListening('metrics.updated');
                window.Echo.leaveChannel(`metrics.${this.selectedChannel}`);
            }
        },

        switchChannel() {
            this.disconnectWebSocket();
            this.initializeWebSocket();
            this.loadMetrics();
            this.loadTrends();
        },

        updateCharts() {
            this.$nextTick(() => {
                if (this.selectedChannel === 'system') {
                    this.updateRevenueChart();
                } else if (this.selectedChannel === 'campaigns') {
                    this.updateStrategyChart();
                }
            });
        },

        updateRevenueChart() {
            const ctx = this.$refs.revenueChart;
            if (!ctx) return;

            if (this.charts.revenue) {
                this.charts.revenue.destroy();
            }

            const hourlyData = this.currentMetrics.revenue_today?.hourly_breakdown || [];
            
            this.charts.revenue = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: hourlyData.map(item => item.hour),
                    datasets: [{
                        label: 'Revenue',
                        data: hourlyData.map(item => item.revenue),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND'
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });
        },

        updateStrategyChart() {
            const ctx = this.$refs.strategyChart;
            if (!ctx) return;

            if (this.charts.strategy) {
                this.charts.strategy.destroy();
            }

            const strategies = this.currentMetrics.auto_betting_stats?.by_strategy || {};
            
            this.charts.strategy = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(strategies),
                    datasets: [{
                        data: Object.values(strategies).map(s => s.total),
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB', 
                            '#FFCE56',
                            '#4BC0C0'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        destroyCharts() {
            Object.values(this.charts).forEach(chart => {
                if (chart) chart.destroy();
            });
        },

        getTrendClass(metric) {
            const trend = this.trends[metric];
            if (!trend) return '';
            return trend.direction === 'up' ? 'trend-up' : 
                   trend.direction === 'down' ? 'trend-down' : 'trend-stable';
        },

        getTrendIcon(metric) {
            const trend = this.trends[metric];
            if (!trend) return 'fas fa-minus';
            return trend.direction === 'up' ? 'fas fa-arrow-up' :
                   trend.direction === 'down' ? 'fas fa-arrow-down' : 'fas fa-minus';
        },

        getTrendPercent(metric) {
            const trend = this.trends[metric];
            return trend ? Math.abs(trend.change_percent).toFixed(1) : '0.0';
        },

        formatCurrency(amount) {
            if (!amount) return '0 ₫';
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        },

        formatDuration(seconds) {
            if (!seconds) return '0s';
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            
            if (hours > 0) {
                return `${hours}h ${minutes % 60}m`;
            } else if (minutes > 0) {
                return `${minutes}m ${seconds % 60}s`;
            } else {
                return `${seconds}s`;
            }
        }
    }
}
</script>

<style scoped>
.real-time-metrics {
    padding: 20px;
}

.metrics-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.controls {
    display: flex;
    gap: 10px;
}

.controls select {
    min-width: 150px;
}

.metric-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    height: 100%;
}

.metric-card h6 {
    margin: 0 0 10px 0;
    color: #6c757d;
    font-size: 14px;
    font-weight: 600;
}

.metric-value {
    font-size: 28px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 8px;
}

.metric-trend {
    font-size: 12px;
    font-weight: 600;
}

.trend-up {
    color: #28a745;
}

.trend-down {
    color: #dc3545;
}

.trend-stable {
    color: #6c757d;
}

.chart-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    height: 400px;
}

.connection-status {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: white;
    padding: 10px 15px;
    border-radius: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    font-size: 12px;
    border: 1px solid #e9ecef;
}

.connected {
    border-color: #28a745;
}

.disconnected {
    border-color: #dc3545;
}

.performance-stats,
.auto-betting-stats {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat .label {
    color: #6c757d;
    font-size: 14px;
}

.stat .value {
    font-weight: 600;
    color: #2c3e50;
}
</style>
```

### Step 6: Database Migration for Metrics History
```sql
-- Create metrics_history table
CREATE TABLE metrics_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    channel VARCHAR(50) NOT NULL,
    data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_channel_time (channel, created_at),
    INDEX idx_created_at (created_at)
);
```

### Step 7: Scheduler Configuration
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Collect metrics every minute
    $schedule->job(new CollectRealTimeMetricsJob('system'))
             ->everyMinute()
             ->withoutOverlapping();
             
    $schedule->job(new CollectRealTimeMetricsJob('campaigns'))
             ->everyTwoMinutes()
             ->withoutOverlapping();
             
    $schedule->job(new CollectRealTimeMetricsJob('users'))
             ->everyFiveMinutes()
             ->withoutOverlapping();
             
    // Clean old metrics data
    $schedule->command('metrics:cleanup')
             ->daily();
}
```

## Success Criteria
- [ ] Real-time metrics collection working
- [ ] WebSocket updates functioning
- [ ] Historical data storage implemented
- [ ] Interactive dashboard responsive
- [ ] Trend analysis accurate
- [ ] Performance optimized for high load
- [ ] Alert system for anomalies
- [ ] Export functionality working
- [ ] Mobile-friendly interface 
