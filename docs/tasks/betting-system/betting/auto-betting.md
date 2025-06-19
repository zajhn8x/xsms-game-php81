# Auto Betting System

## Tổng quan
Xây dựng hệ thống đặt cược tự động với nhiều chiến lược khác nhau dựa trên AI/ML và phân tích dữ liệu lịch sử.

## Mục tiêu
- Implement multiple auto betting strategies
- Real-time market analysis
- Risk management integration
- Performance optimization
- Machine learning predictions
- Configurable betting rules

## Auto Betting Strategies

### 1. Heatmap-based Strategy
Dựa trên phân tích heatmap để xác định các số có xác suất xuất hiện cao.

### 2. Streak-based Strategy
Theo dõi chuỗi thắng/thua để điều chỉnh chiến lược đặt cược.

### 3. Pattern Recognition
Sử dụng ML để nhận diện patterns trong dữ liệu lịch sử.

### 4. Hybrid Strategy
Kết hợp nhiều chiến lược để tối ưu hóa hiệu quả.

## Implementation Steps

### Step 1: Auto Betting Engine
```php
// app/Services/AutoBettingEngine.php
class AutoBettingEngine
{
    protected $strategies = [];
    protected $riskManager;
    protected $marketAnalyzer;

    public function __construct(
        RiskManagementService $riskManager,
        MarketAnalyzerService $marketAnalyzer
    ) {
        $this->riskManager = $riskManager;
        $this->marketAnalyzer = $marketAnalyzer;
        $this->initializeStrategies();
    }

    public function processCampaign(Campaign $campaign)
    {
        if (!$this->shouldProcessCampaign($campaign)) {
            return;
        }

        try {
            // Get strategy instance
            $strategy = $this->getStrategy($campaign->betting_strategy);
            
            // Check risk conditions
            if (!$this->riskManager->canPlaceBet($campaign)) {
                Log::info("Risk management prevented bet for campaign {$campaign->id}");
                return;
            }

            // Generate betting recommendations
            $recommendations = $strategy->generateRecommendations($campaign);
            
            if (empty($recommendations)) {
                Log::info("No recommendations generated for campaign {$campaign->id}");
                return;
            }

            // Process each recommendation
            foreach ($recommendations as $recommendation) {
                $this->processBettingRecommendation($campaign, $recommendation);
            }

            // Update campaign last processed time
            $campaign->update(['last_processed_at' => now()]);

        } catch (\Exception $e) {
            Log::error("Auto betting error for campaign {$campaign->id}: " . $e->getMessage());
        }
    }

    protected function shouldProcessCampaign(Campaign $campaign): bool
    {
        // Check if campaign is active
        if (!in_array($campaign->status, ['active', 'running'])) {
            return false;
        }

        // Check if enough time has passed since last processing
        if ($campaign->last_processed_at && 
            $campaign->last_processed_at->diffInMinutes(now()) < 15) {
            return false;
        }

        // Check if today's bet limit is reached
        $todayBets = $campaign->bets()
            ->whereDate('created_at', today())
            ->sum('amount');

        if ($campaign->daily_bet_limit && $todayBets >= $campaign->daily_bet_limit) {
            return false;
        }

        return true;
    }

    protected function processBettingRecommendation(Campaign $campaign, BettingRecommendation $recommendation)
    {
        // Validate recommendation
        if (!$this->validateRecommendation($campaign, $recommendation)) {
            return;
        }

        // Calculate bet amount based on strategy
        $betAmount = $this->calculateBetAmount($campaign, $recommendation);

        // Check if sufficient balance
        if ($campaign->current_balance < $betAmount) {
            Log::warning("Insufficient balance for campaign {$campaign->id}");
            return;
        }

        // Place the bet
        $bet = $this->placeBet($campaign, $recommendation, $betAmount);

        Log::info("Auto bet placed: Campaign {$campaign->id}, Number {$recommendation->number}, Amount {$betAmount}");
    }

    protected function calculateBetAmount(Campaign $campaign, BettingRecommendation $recommendation): float
    {
        $config = $campaign->strategy_config ?? [];
        
        // Base amount calculation
        $baseAmount = $config['base_amount'] ?? 10000;
        
        // Confidence multiplier
        $confidenceMultiplier = $recommendation->confidence;
        
        // Balance percentage
        $balancePercentage = $config['balance_percentage'] ?? 0.01; // 1% of balance
        $balanceAmount = $campaign->current_balance * $balancePercentage;
        
        // Progressive betting based on recent performance
        $progressiveMultiplier = $this->getProgressiveMultiplier($campaign);
        
        $calculatedAmount = min(
            $baseAmount * $confidenceMultiplier * $progressiveMultiplier,
            $balanceAmount,
            $config['max_bet_amount'] ?? 100000
        );

        return max($calculatedAmount, $config['min_bet_amount'] ?? 5000);
    }

    protected function getProgressiveMultiplier(Campaign $campaign): float
    {
        $recentBets = $campaign->bets()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($recentBets->isEmpty()) {
            return 1.0;
        }

        $winRate = $recentBets->where('is_win', true)->count() / $recentBets->count();
        
        // Increase bet size when winning, decrease when losing
        if ($winRate >= 0.7) {
            return 1.5; // Increase by 50%
        } elseif ($winRate >= 0.5) {
            return 1.0; // Keep same
        } elseif ($winRate >= 0.3) {
            return 0.7; // Decrease by 30%
        } else {
            return 0.5; // Decrease by 50%
        }
    }

    protected function placeBet(Campaign $campaign, BettingRecommendation $recommendation, float $amount)
    {
        return DB::transaction(function () use ($campaign, $recommendation, $amount) {
            // Create bet record
            $bet = CampaignBet::create([
                'campaign_id' => $campaign->id,
                'lo_number' => str_pad($recommendation->number, 2, '0', STR_PAD_LEFT),
                'amount' => $amount,
                'date' => now()->toDateString(),
                'source' => 'auto_' . $campaign->betting_strategy,
                'confidence' => $recommendation->confidence,
                'reasoning' => $recommendation->reasoning,
                'is_win' => false // Will be updated when results are available
            ]);

            // Update campaign balance
            $campaign->decrement('current_balance', $amount);
            $campaign->increment('total_bet_amount', $amount);
            $campaign->increment('total_bet_count');
            $campaign->update(['last_bet_at' => now()]);

            return $bet;
        });
    }

    protected function validateRecommendation(Campaign $campaign, BettingRecommendation $recommendation): bool
    {
        // Check number range
        if ($recommendation->number < 0 || $recommendation->number > 99) {
            return false;
        }

        // Check confidence threshold
        $minConfidence = $campaign->strategy_config['min_confidence'] ?? 0.6;
        if ($recommendation->confidence < $minConfidence) {
            return false;
        }

        // Check if number was recently played
        $recentBets = $campaign->bets()
            ->where('lo_number', str_pad($recommendation->number, 2, '0', STR_PAD_LEFT))
            ->whereDate('created_at', '>=', now()->subDays(3))
            ->exists();

        if ($recentBets && !($campaign->strategy_config['allow_repeat'] ?? false)) {
            return false;
        }

        return true;
    }

    protected function initializeStrategies()
    {
        $this->strategies = [
            'auto_heatmap' => new HeatmapBettingStrategy(),
            'auto_streak' => new StreakBettingStrategy(),
            'auto_pattern' => new PatternBettingStrategy(),
            'auto_hybrid' => new HybridBettingStrategy()
        ];
    }

    protected function getStrategy(string $strategyName): AutoBettingStrategyInterface
    {
        if (!isset($this->strategies[$strategyName])) {
            throw new \InvalidArgumentException("Unknown strategy: {$strategyName}");
        }

        return $this->strategies[$strategyName];
    }
}
```

### Step 2: Betting Strategy Interface
```php
// app/Contracts/AutoBettingStrategyInterface.php
interface AutoBettingStrategyInterface
{
    public function generateRecommendations(Campaign $campaign): array;
    public function getRequiredConfiguration(): array;
    public function validateConfiguration(array $config): bool;
    public function getDescription(): string;
}

// app/Models/BettingRecommendation.php
class BettingRecommendation
{
    public $number;
    public $confidence;
    public $reasoning;
    public $metadata;

    public function __construct(int $number, float $confidence, string $reasoning = '', array $metadata = [])
    {
        $this->number = $number;
        $this->confidence = $confidence;
        $this->reasoning = $reasoning;
        $this->metadata = $metadata;
    }
}
```

### Step 3: Heatmap Strategy Implementation
```php
// app/Services/Strategies/HeatmapBettingStrategy.php
class HeatmapBettingStrategy implements AutoBettingStrategyInterface
{
    protected $heatmapService;

    public function __construct(HeatmapInsightQueryService $heatmapService)
    {
        $this->heatmapService = $heatmapService;
    }

    public function generateRecommendations(Campaign $campaign): array
    {
        $config = $campaign->strategy_config ?? [];
        $maxNumbers = $config['max_numbers_per_day'] ?? 3;
        $minConfidence = $config['min_confidence'] ?? 0.7;
        
        // Get current heatmap data
        $heatmapData = $this->heatmapService->getCurrentHeatmap();
        
        // Filter and sort by confidence
        $candidates = collect($heatmapData)
            ->filter(function ($item) use ($minConfidence) {
                return $item['confidence'] >= $minConfidence;
            })
            ->sortByDesc('confidence')
            ->take($maxNumbers);

        $recommendations = [];
        
        foreach ($candidates as $candidate) {
            $recommendations[] = new BettingRecommendation(
                $candidate['number'],
                $candidate['confidence'],
                "Heatmap analysis - Streak: {$candidate['current_streak']}, Recent hits: {$candidate['recent_hits']}",
                [
                    'streak' => $candidate['current_streak'],
                    'recent_hits' => $candidate['recent_hits'],
                    'pattern_strength' => $candidate['pattern_strength']
                ]
            );
        }

        return $recommendations;
    }

    public function getRequiredConfiguration(): array
    {
        return [
            'max_numbers_per_day' => [
                'type' => 'integer',
                'min' => 1,
                'max' => 10,
                'default' => 3,
                'description' => 'Số lượng số tối đa mỗi ngày'
            ],
            'min_confidence' => [
                'type' => 'float',
                'min' => 0.1,
                'max' => 1.0,
                'default' => 0.7,
                'description' => 'Độ tin cậy tối thiểu'
            ],
            'streak_weight' => [
                'type' => 'float',
                'min' => 0.1,
                'max' => 2.0,
                'default' => 1.0,
                'description' => 'Trọng số cho streak pattern'
            ]
        ];
    }

    public function validateConfiguration(array $config): bool
    {
        $required = $this->getRequiredConfiguration();
        
        foreach ($required as $key => $rules) {
            if (!isset($config[$key])) {
                continue;
            }
            
            $value = $config[$key];
            
            if ($rules['type'] === 'integer' && !is_int($value)) {
                return false;
            }
            
            if ($rules['type'] === 'float' && !is_numeric($value)) {
                return false;
            }
            
            if (isset($rules['min']) && $value < $rules['min']) {
                return false;
            }
            
            if (isset($rules['max']) && $value > $rules['max']) {
                return false;
            }
        }
        
        return true;
    }

    public function getDescription(): string
    {
        return 'Sử dụng phân tích heatmap để xác định các số có xác suất xuất hiện cao dựa trên patterns lịch sử.';
    }
}
```

### Step 4: Streak Strategy Implementation
```php
// app/Services/Strategies/StreakBettingStrategy.php
class StreakBettingStrategy implements AutoBettingStrategyInterface
{
    protected $streakAnalyzer;

    public function __construct(StreakAnalyzerService $streakAnalyzer)
    {
        $this->streakAnalyzer = $streakAnalyzer;
    }

    public function generateRecommendations(Campaign $campaign): array
    {
        $config = $campaign->strategy_config ?? [];
        $maxNumbers = $config['max_numbers_per_day'] ?? 2;
        $streakThreshold = $config['streak_threshold'] ?? 5;
        
        // Analyze current streaks
        $streakData = $this->streakAnalyzer->analyzeCurrentStreaks();
        
        $recommendations = [];
        
        // Find numbers with long miss streaks (due to hit)
        $longMissStreaks = collect($streakData['miss_streaks'])
            ->filter(function ($item) use ($streakThreshold) {
                return $item['streak_length'] >= $streakThreshold;
            })
            ->sortByDesc('streak_length')
            ->take($maxNumbers);

        foreach ($longMissStreaks as $streak) {
            $confidence = $this->calculateStreakConfidence($streak);
            
            $recommendations[] = new BettingRecommendation(
                $streak['number'],
                $confidence,
                "Long miss streak: {$streak['streak_length']} days, Last hit: {$streak['last_hit_date']}",
                [
                    'streak_type' => 'miss',
                    'streak_length' => $streak['streak_length'],
                    'last_hit_date' => $streak['last_hit_date'],
                    'historical_average' => $streak['historical_average']
                ]
            );
        }

        return $recommendations;
    }

    protected function calculateStreakConfidence(array $streakData): float
    {
        $streakLength = $streakData['streak_length'];
        $historicalAverage = $streakData['historical_average'] ?? 10;
        
        // Calculate confidence based on how much the streak exceeds historical average
        $ratio = $streakLength / $historicalAverage;
        
        if ($ratio >= 2.0) {
            return 0.9;
        } elseif ($ratio >= 1.5) {
            return 0.8;
        } elseif ($ratio >= 1.2) {
            return 0.7;
        } else {
            return 0.6;
        }
    }

    public function getRequiredConfiguration(): array
    {
        return [
            'max_numbers_per_day' => [
                'type' => 'integer',
                'min' => 1,
                'max' => 5,
                'default' => 2,
                'description' => 'Số lượng số tối đa mỗi ngày'
            ],
            'streak_threshold' => [
                'type' => 'integer',
                'min' => 3,
                'max' => 20,
                'default' => 5,
                'description' => 'Độ dài streak tối thiểu để xem xét'
            ],
            'confidence_multiplier' => [
                'type' => 'float',
                'min' => 0.5,
                'max' => 2.0,
                'default' => 1.0,
                'description' => 'Hệ số điều chỉnh độ tin cậy'
            ]
        ];
    }

    public function validateConfiguration(array $config): bool
    {
        // Similar validation logic as HeatmapBettingStrategy
        return true;
    }

    public function getDescription(): string
    {
        return 'Phân tích streak patterns để tìm các số có khả năng xuất hiện dựa trên thống kê lịch sử.';
    }
}
```

### Step 5: Auto Betting Job
```php
// app/Jobs/ProcessAutoBettingJob.php
class ProcessAutoBettingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaignId;

    public function __construct($campaignId = null)
    {
        $this->campaignId = $campaignId;
    }

    public function handle(AutoBettingEngine $autoBettingEngine)
    {
        if ($this->campaignId) {
            // Process specific campaign
            $campaign = Campaign::find($this->campaignId);
            if ($campaign && $this->isAutoBettingStrategy($campaign->betting_strategy)) {
                $autoBettingEngine->processCampaign($campaign);
            }
        } else {
            // Process all active auto betting campaigns
            $campaigns = Campaign::whereIn('status', ['active', 'running'])
                ->whereIn('betting_strategy', ['auto_heatmap', 'auto_streak', 'auto_pattern', 'auto_hybrid'])
                ->get();

            foreach ($campaigns as $campaign) {
                try {
                    $autoBettingEngine->processCampaign($campaign);
                } catch (\Exception $e) {
                    Log::error("Auto betting failed for campaign {$campaign->id}: " . $e->getMessage());
                }
            }
        }
    }

    protected function isAutoBettingStrategy(string $strategy): bool
    {
        return in_array($strategy, ['auto_heatmap', 'auto_streak', 'auto_pattern', 'auto_hybrid']);
    }
}
```

### Step 6: Auto Betting Controller
```php
// app/Http/Controllers/AutoBettingController.php
class AutoBettingController extends Controller
{
    protected $autoBettingEngine;

    public function __construct(AutoBettingEngine $autoBettingEngine)
    {
        $this->autoBettingEngine = $autoBettingEngine;
        $this->middleware('auth');
    }

    public function strategies()
    {
        $strategies = [
            'auto_heatmap' => app(HeatmapBettingStrategy::class),
            'auto_streak' => app(StreakBettingStrategy::class),
            'auto_pattern' => app(PatternBettingStrategy::class),
            'auto_hybrid' => app(HybridBettingStrategy::class)
        ];

        $strategiesData = [];
        foreach ($strategies as $key => $strategy) {
            $strategiesData[$key] = [
                'name' => $this->getStrategyName($key),
                'description' => $strategy->getDescription(),
                'configuration' => $strategy->getRequiredConfiguration()
            ];
        }

        return response()->json($strategiesData);
    }

    public function testStrategy(Request $request)
    {
        $request->validate([
            'strategy' => 'required|in:auto_heatmap,auto_streak,auto_pattern,auto_hybrid',
            'config' => 'required|array'
        ]);

        try {
            // Create a mock campaign for testing
            $mockCampaign = new Campaign([
                'betting_strategy' => $request->strategy,
                'strategy_config' => $request->config,
                'current_balance' => 1000000
            ]);

            $strategy = $this->autoBettingEngine->getStrategy($request->strategy);
            
            if (!$strategy->validateConfiguration($request->config)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cấu hình không hợp lệ'
                ], 400);
            }

            $recommendations = $strategy->generateRecommendations($mockCampaign);

            return response()->json([
                'success' => true,
                'recommendations' => $recommendations,
                'strategy_info' => [
                    'name' => $this->getStrategyName($request->strategy),
                    'description' => $strategy->getDescription()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function runManual(Request $request, Campaign $campaign)
    {
        if ($campaign->user_id !== auth()->id()) {
            abort(403);
        }

        if (!in_array($campaign->betting_strategy, ['auto_heatmap', 'auto_streak', 'auto_pattern', 'auto_hybrid'])) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign không sử dụng auto betting strategy'
            ], 400);
        }

        try {
            $this->autoBettingEngine->processCampaign($campaign);
            
            return response()->json([
                'success' => true,
                'message' => 'Auto betting đã được thực hiện'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function getStrategyName(string $strategy): string
    {
        $names = [
            'auto_heatmap' => 'Heatmap Strategy',
            'auto_streak' => 'Streak Strategy', 
            'auto_pattern' => 'Pattern Recognition',
            'auto_hybrid' => 'Hybrid Strategy'
        ];

        return $names[$strategy] ?? $strategy;
    }
}
```

### Step 7: Scheduler Integration
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Run auto betting every 30 minutes during betting hours
    $schedule->job(new ProcessAutoBettingJob())
             ->everyThirtyMinutes()
             ->between('08:00', '18:30')
             ->timezone('Asia/Ho_Chi_Minh');
    
    // Run analysis update every hour
    $schedule->command('analytics:update-heatmap')
             ->hourly();
             
    // Process results after lottery draw time
    $schedule->command('lottery:process-results')
             ->dailyAt('18:45');
}
```

## Frontend Integration

### Strategy Configuration Component
```javascript
// resources/js/components/AutoBettingConfig.vue
<template>
    <div class="auto-betting-config">
        <div class="strategy-selector mb-4">
            <label class="form-label">Chọn chiến lược auto betting:</label>
            <select v-model="selectedStrategy" @change="loadStrategyConfig" class="form-select">
                <option value="">-- Chọn chiến lược --</option>
                <option v-for="(strategy, key) in strategies" :key="key" :value="key">
                    {{ strategy.name }}
                </option>
            </select>
        </div>

        <div v-if="selectedStrategy" class="strategy-details">
            <div class="alert alert-info">
                <strong>{{ strategies[selectedStrategy].name }}</strong><br>
                {{ strategies[selectedStrategy].description }}
            </div>

            <div class="configuration-form">
                <h6>Cấu hình chiến lược:</h6>
                <div v-for="(config, key) in strategyConfig" :key="key" class="mb-3">
                    <label class="form-label">{{ config.description }}</label>
                    <input 
                        :type="config.type === 'integer' ? 'number' : (config.type === 'float' ? 'number' : 'text')"
                        :step="config.type === 'float' ? '0.1' : '1'"
                        :min="config.min"
                        :max="config.max"
                        v-model="configValues[key]"
                        :placeholder="config.default"
                        class="form-control"
                    >
                    <small class="form-text text-muted">
                        Mặc định: {{ config.default }}
                        <span v-if="config.min !== undefined"> | Tối thiểu: {{ config.min }}</span>
                        <span v-if="config.max !== undefined"> | Tối đa: {{ config.max }}</span>
                    </small>
                </div>
            </div>

            <div class="test-section mt-4">
                <button @click="testStrategy" :disabled="testing" class="btn btn-outline-primary">
                    <span v-if="testing">
                        <i class="fas fa-spinner fa-spin"></i> Đang test...
                    </span>
                    <span v-else>
                        <i class="fas fa-flask"></i> Test chiến lược
                    </span>
                </button>
            </div>

            <div v-if="testResults" class="test-results mt-4">
                <h6>Kết quả test:</h6>
                <div class="alert alert-success">
                    <p><strong>Số lượng recommendations:</strong> {{ testResults.recommendations.length }}</p>
                    <div v-if="testResults.recommendations.length > 0">
                        <p><strong>Danh sách số được đề xuất:</strong></p>
                        <ul>
                            <li v-for="rec in testResults.recommendations" :key="rec.number">
                                Số {{ rec.number.toString().padStart(2, '0') }} 
                                - Độ tin cậy: {{ (rec.confidence * 100).toFixed(1) }}%
                                <br><small class="text-muted">{{ rec.reasoning }}</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AutoBettingConfig',
    props: {
        initialStrategy: String,
        initialConfig: Object
    },
    data() {
        return {
            strategies: {},
            selectedStrategy: this.initialStrategy || '',
            strategyConfig: {},
            configValues: { ...this.initialConfig } || {},
            testing: false,
            testResults: null
        }
    },
    mounted() {
        this.loadStrategies();
    },
    methods: {
        async loadStrategies() {
            try {
                const response = await axios.get('/api/auto-betting/strategies');
                this.strategies = response.data;
                
                if (this.selectedStrategy) {
                    this.loadStrategyConfig();
                }
            } catch (error) {
                console.error('Failed to load strategies:', error);
            }
        },
        
        loadStrategyConfig() {
            if (this.selectedStrategy && this.strategies[this.selectedStrategy]) {
                this.strategyConfig = this.strategies[this.selectedStrategy].configuration;
                
                // Set default values if not already set
                Object.keys(this.strategyConfig).forEach(key => {
                    if (this.configValues[key] === undefined) {
                        this.configValues[key] = this.strategyConfig[key].default;
                    }
                });
            }
            
            this.testResults = null;
        },
        
        async testStrategy() {
            if (!this.selectedStrategy) return;
            
            this.testing = true;
            this.testResults = null;
            
            try {
                const response = await axios.post('/api/auto-betting/test-strategy', {
                    strategy: this.selectedStrategy,
                    config: this.configValues
                });
                
                this.testResults = response.data;
            } catch (error) {
                alert('Lỗi test chiến lược: ' + (error.response?.data?.message || error.message));
            } finally {
                this.testing = false;
            }
        },
        
        getConfiguration() {
            return {
                strategy: this.selectedStrategy,
                config: { ...this.configValues }
            };
        }
    },
    watch: {
        configValues: {
            handler() {
                this.$emit('config-changed', this.getConfiguration());
            },
            deep: true
        },
        selectedStrategy() {
            this.$emit('config-changed', this.getConfiguration());
        }
    }
}
</script>
```

## Testing Requirements

### Unit Tests
```php
// tests/Unit/AutoBettingEngineTest.php
class AutoBettingEngineTest extends TestCase
{
    public function test_can_process_heatmap_campaign()
    {
        $campaign = Campaign::factory()->create([
            'betting_strategy' => 'auto_heatmap',
            'strategy_config' => [
                'max_numbers_per_day' => 2,
                'min_confidence' => 0.7
            ],
            'current_balance' => 100000
        ]);

        $engine = app(AutoBettingEngine::class);
        $engine->processCampaign($campaign);

        // Assert bets were created
        $this->assertTrue($campaign->bets()->count() <= 2);
        
        // Assert bets have proper confidence
        $campaign->bets->each(function ($bet) {
            $this->assertGreaterThanOrEqual(0.7, $bet->confidence);
        });
    }

    public function test_respects_daily_bet_limit()
    {
        $campaign = Campaign::factory()->create([
            'daily_bet_limit' => 50000,
            'current_balance' => 100000
        ]);

        // Create existing bets for today that reach the limit
        CampaignBet::factory()->create([
            'campaign_id' => $campaign->id,
            'amount' => 50000,
            'created_at' => now()
        ]);

        $engine = app(AutoBettingEngine::class);
        $initialBetCount = $campaign->bets()->count();
        
        $engine->processCampaign($campaign);

        // Should not create new bets
        $this->assertEquals($initialBetCount, $campaign->bets()->count());
    }
}
```

### Integration Tests
```php
// tests/Feature/AutoBettingTest.php
class AutoBettingTest extends TestCase
{
    public function test_user_can_configure_auto_betting_strategy()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->post('/campaigns', [
                'name' => 'Auto Test Campaign',
                'betting_strategy' => 'auto_heatmap',
                'strategy_config' => [
                    'max_numbers_per_day' => 3,
                    'min_confidence' => 0.8
                ],
                'initial_balance' => 1000000
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Auto Test Campaign',
            'betting_strategy' => 'auto_heatmap'
        ]);
    }

    public function test_auto_betting_job_processes_campaigns()
    {
        $campaigns = Campaign::factory(3)->create([
            'betting_strategy' => 'auto_heatmap',
            'status' => 'active',
            'current_balance' => 100000
        ]);

        ProcessAutoBettingJob::dispatchSync();

        // Check that campaigns were processed
        $campaigns->each(function ($campaign) {
            $this->assertNotNull($campaign->fresh()->last_processed_at);
        });
    }
}
```

## Performance Optimization

### Caching Strategy
```php
// Cache heatmap data for 15 minutes
Cache::remember('heatmap:current', 900, function () {
    return $this->heatmapService->getCurrentHeatmap();
});

// Cache streak analysis for 30 minutes
Cache::remember('streaks:current', 1800, function () {
    return $this->streakAnalyzer->analyzeCurrentStreaks();
});
```

### Queue Optimization
```php
// Use separate queue for auto betting
ProcessAutoBettingJob::dispatch()->onQueue('auto-betting');

// Use batch processing for multiple campaigns
Bus::batch([
    new ProcessAutoBettingJob($campaign1->id),
    new ProcessAutoBettingJob($campaign2->id),
    new ProcessAutoBettingJob($campaign3->id),
])->name('auto-betting-batch')->dispatch();
```

## Success Criteria
- [ ] Multiple auto betting strategies implemented
- [ ] Real-time processing of active campaigns
- [ ] Configurable strategy parameters
- [ ] Risk management integration
- [ ] Performance metrics tracking
- [ ] Error handling and logging
- [ ] User-friendly configuration interface
- [ ] Comprehensive testing coverage
- [ ] Queue-based processing for scalability 
