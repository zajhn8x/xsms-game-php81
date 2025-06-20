<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\SubCampaign;
use App\Models\CampaignBet;
use App\Models\LotteryResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Micro-task 2.2.2: Campaign execution engine (12h)
 * Core service for executing campaigns and managing betting automation
 */
class CampaignExecutionService
{
    protected LotteryFormulaService $formulaService;
    protected HeatmapCacheService $heatmapService;
    protected RiskManagementService $riskService;
    protected CampaignSchedulerService $schedulerService;

    public function __construct(
        LotteryFormulaService $formulaService,
        HeatmapCacheService $heatmapService,
        RiskManagementService $riskService,
        CampaignSchedulerService $schedulerService
    ) {
        $this->formulaService = $formulaService;
        $this->heatmapService = $heatmapService;
        $this->riskService = $riskService;
        $this->schedulerService = $schedulerService;
    }

    /**
     * Execute all active campaigns
     */
    public function executeActiveCampaigns(): array
    {
        $results = [
            'campaigns_processed' => 0,
            'sub_campaigns_processed' => 0,
            'bets_placed' => 0,
            'total_bet_amount' => 0,
            'errors' => [],
            'execution_time' => 0
        ];

        $startTime = microtime(true);

        try {
            $activeCampaigns = Campaign::whereIn('status', ['active', 'running'])->get();

            foreach ($activeCampaigns as $campaign) {
                $campaignResult = $this->executeCampaign($campaign);

                $results['campaigns_processed']++;
                $results['sub_campaigns_processed'] += $campaignResult['sub_campaigns_processed'];
                $results['bets_placed'] += $campaignResult['bets_placed'];
                $results['total_bet_amount'] += $campaignResult['total_bet_amount'];

                if (!empty($campaignResult['errors'])) {
                    $results['errors'] = array_merge($results['errors'], $campaignResult['errors']);
                }
            }

            $results['execution_time'] = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Campaign execution completed', $results);

        } catch (\Exception $e) {
            Log::error('Campaign execution failed', ['error' => $e->getMessage()]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Execute a specific campaign
     */
    public function executeCampaign(Campaign $campaign): array
    {
        $results = [
            'sub_campaigns_processed' => 0,
            'bets_placed' => 0,
            'total_bet_amount' => 0,
            'errors' => []
        ];

        try {
            // Check if campaign should continue running
            if (!$this->shouldCampaignContinue($campaign)) {
                $this->schedulerService->stopCampaign($campaign, 'execution_limit_reached');
                return $results;
            }

            // Execute main campaign betting logic
            if ($campaign->betting_strategy !== 'manual') {
                $mainResult = $this->executeCampaignBetting($campaign);
                $results['bets_placed'] += $mainResult['bets_placed'];
                $results['total_bet_amount'] += $mainResult['total_bet_amount'];
            }

            // Execute sub-campaigns
            $activeSubCampaigns = $campaign->subCampaigns()->where('status', 'active')->get();

            foreach ($activeSubCampaigns as $subCampaign) {
                $subResult = $this->executeSubCampaign($subCampaign);

                $results['sub_campaigns_processed']++;
                $results['bets_placed'] += $subResult['bets_placed'];
                $results['total_bet_amount'] += $subResult['total_bet_amount'];

                if (!empty($subResult['errors'])) {
                    $results['errors'] = array_merge($results['errors'], $subResult['errors']);
                }
            }

            // Update campaign status
            $this->updateCampaignExecutionStatus($campaign);

        } catch (\Exception $e) {
            Log::error("Campaign execution failed for campaign {$campaign->id}", ['error' => $e->getMessage()]);
            $results['errors'][] = "Campaign {$campaign->id}: {$e->getMessage()}";
        }

        return $results;
    }

    /**
     * Execute sub-campaign betting logic
     */
    public function executeSubCampaign(SubCampaign $subCampaign): array
    {
        $results = [
            'bets_placed' => 0,
            'total_bet_amount' => 0,
            'errors' => []
        ];

        try {
            // Check if sub-campaign should continue
            if ($subCampaign->shouldAutoStop()) {
                $subCampaign->stop('auto_stop');
                return $results;
            }

            // Check daily limits
            if (!$this->canSubCampaignBetToday($subCampaign)) {
                return $results;
            }

            // Execute betting based on strategy
            $bettingResult = $this->executeBettingStrategy($subCampaign);

            $results['bets_placed'] = $bettingResult['bets_placed'];
            $results['total_bet_amount'] = $bettingResult['total_bet_amount'];

            // Update sub-campaign performance
            $this->updateSubCampaignPerformance($subCampaign, $bettingResult);

        } catch (\Exception $e) {
            Log::error("Sub-campaign execution failed for {$subCampaign->id}", ['error' => $e->getMessage()]);
            $results['errors'][] = "Sub-campaign {$subCampaign->id}: {$e->getMessage()}";
        }

        return $results;
    }

    /**
     * Execute campaign betting logic (for main campaign)
     */
    protected function executeCampaignBetting(Campaign $campaign): array
    {
        $results = [
            'bets_placed' => 0,
            'total_bet_amount' => 0
        ];

        // Check daily limits
        if (!$this->canCampaignBetToday($campaign)) {
            return $results;
        }

        // Execute betting based on strategy
        $bettingNumbers = $this->generateBettingNumbers($campaign->betting_strategy, $campaign->strategy_config ?? []);

        foreach ($bettingNumbers as $numberData) {
            $betAmount = $this->calculateBetAmount($campaign, $numberData);

            if ($betAmount > 0 && $this->validateBet($campaign, $betAmount)) {
                $bet = $this->placeBet($campaign, $numberData['number'], $betAmount, $numberData);

                if ($bet) {
                    $results['bets_placed']++;
                    $results['total_bet_amount'] += $betAmount;
                }
            }
        }

        return $results;
    }

    /**
     * Execute betting strategy for sub-campaign
     */
    protected function executeBettingStrategy(SubCampaign $subCampaign): array
    {
        $results = [
            'bets_placed' => 0,
            'total_bet_amount' => 0
        ];

        $strategy = $subCampaign->betting_strategy;
        $config = $subCampaign->strategy_config ?? [];

        // Generate betting numbers based on strategy
        $bettingNumbers = $this->generateBettingNumbers($strategy, $config);

        foreach ($bettingNumbers as $numberData) {
            $betAmount = $this->calculateSubCampaignBetAmount($subCampaign, $numberData);

            if ($betAmount > 0 && $this->validateSubCampaignBet($subCampaign, $betAmount)) {
                $bet = $this->placeSubCampaignBet($subCampaign, $numberData['number'], $betAmount, $numberData);

                if ($bet) {
                    $results['bets_placed']++;
                    $results['total_bet_amount'] += $betAmount;
                }
            }
        }

        return $results;
    }

    /**
     * Generate betting numbers based on strategy
     */
    protected function generateBettingNumbers(string $strategy, array $config): array
    {
        $numbers = [];
        $maxNumbers = $config['max_numbers_per_day'] ?? 3;
        $minConfidence = $config['min_confidence'] ?? 0.6;

        switch ($strategy) {
            case 'auto_heatmap':
                $numbers = $this->generateHeatmapNumbers($config, $maxNumbers, $minConfidence);
                break;

            case 'auto_streak':
                $numbers = $this->generateStreakNumbers($config, $maxNumbers);
                break;

            case 'auto_pattern':
                $numbers = $this->generatePatternNumbers($config, $maxNumbers);
                break;

            case 'auto_hybrid':
                $numbers = $this->generateHybridNumbers($config, $maxNumbers, $minConfidence);
                break;

            case 'auto_fibonacci':
                $numbers = $this->generateFibonacciNumbers($config, $maxNumbers);
                break;

            case 'auto_martingale':
                $numbers = $this->generateMartingaleNumbers($config, $maxNumbers);
                break;

            default:
                $numbers = [];
        }

        return $numbers;
    }

    /**
     * Generate heatmap-based numbers
     */
    protected function generateHeatmapNumbers(array $config, int $maxNumbers, float $minConfidence): array
    {
        $heatmapData = $this->heatmapService->getHotNumbers();
        $numbers = [];

        foreach ($heatmapData as $data) {
            if (count($numbers) >= $maxNumbers) break;

            if ($data['confidence'] >= $minConfidence) {
                $numbers[] = [
                    'number' => $data['number'],
                    'confidence' => $data['confidence'],
                    'priority' => $data['priority'] ?? 1,
                    'strategy' => 'heatmap',
                    'metadata' => $data
                ];
            }
        }

        return $numbers;
    }

    /**
     * Generate streak-based numbers
     */
    protected function generateStreakNumbers(array $config, int $maxNumbers): array
    {
        $minStreak = $config['min_streak'] ?? 3;
        $streakNumbers = $this->formulaService->getStreakNumbers($minStreak);
        $numbers = [];

        foreach ($streakNumbers as $data) {
            if (count($numbers) >= $maxNumbers) break;

            $numbers[] = [
                'number' => $data['number'],
                'confidence' => min(0.9, 0.5 + ($data['streak_length'] * 0.1)),
                'priority' => $data['streak_length'],
                'strategy' => 'streak',
                'metadata' => $data
            ];
        }

        return $numbers;
    }

    /**
     * Generate pattern-based numbers
     */
    protected function generatePatternNumbers(array $config, int $maxNumbers): array
    {
        $patternLength = $config['pattern_length'] ?? 7;
        $patternNumbers = $this->formulaService->getPatternNumbers($patternLength);
        $numbers = [];

        foreach ($patternNumbers as $data) {
            if (count($numbers) >= $maxNumbers) break;

            $numbers[] = [
                'number' => $data['number'],
                'confidence' => $data['pattern_confidence'] ?? 0.6,
                'priority' => $data['pattern_strength'] ?? 1,
                'strategy' => 'pattern',
                'metadata' => $data
            ];
        }

        return $numbers;
    }

    /**
     * Generate hybrid strategy numbers
     */
    protected function generateHybridNumbers(array $config, int $maxNumbers, float $minConfidence): array
    {
        $strategies = $config['strategies'] ?? ['heatmap', 'streak'];
        $allNumbers = [];

        // Collect numbers from multiple strategies
        foreach ($strategies as $strategy) {
            $strategyConfig = array_merge($config, ['max_numbers_per_day' => $maxNumbers]);
            $strategyNumbers = $this->generateBettingNumbers("auto_{$strategy}", $strategyConfig);
            $allNumbers = array_merge($allNumbers, $strategyNumbers);
        }

        // Sort by confidence and priority
        usort($allNumbers, function ($a, $b) {
            $confidenceDiff = $b['confidence'] - $a['confidence'];
            if (abs($confidenceDiff) < 0.01) {
                return $b['priority'] - $a['priority'];
            }
            return $confidenceDiff <=> 0;
        });

        // Remove duplicates and apply confidence filter
        $uniqueNumbers = [];
        $seen = [];

        foreach ($allNumbers as $numberData) {
            if (!isset($seen[$numberData['number']]) &&
                $numberData['confidence'] >= $minConfidence &&
                count($uniqueNumbers) < $maxNumbers) {

                $uniqueNumbers[] = $numberData;
                $seen[$numberData['number']] = true;
            }
        }

        return $uniqueNumbers;
    }

    /**
     * Generate Fibonacci sequence numbers
     */
    protected function generateFibonacciNumbers(array $config, int $maxNumbers): array
    {
        $sequence = $config['fibonacci_sequence'] ?? [1, 1, 2, 3, 5, 8];
        $recentResults = $this->getRecentLotteryResults(14);
        $numbers = [];

        // Apply Fibonacci logic to recent results
        for ($i = 0; $i < min($maxNumbers, count($sequence)); $i++) {
            $targetSum = $sequence[$i] % 100;

            // Find numbers with digit sum matching Fibonacci number
            foreach (range(0, 99) as $num) {
                $digitSum = array_sum(str_split(sprintf('%02d', $num)));
                if ($digitSum === $targetSum) {
                    $numbers[] = [
                        'number' => sprintf('%02d', $num),
                        'confidence' => 0.5 + ($i * 0.1),
                        'priority' => count($sequence) - $i,
                        'strategy' => 'fibonacci',
                        'metadata' => ['fibonacci_value' => $sequence[$i], 'position' => $i]
                    ];
                    break;
                }
            }
        }

        return $numbers;
    }

    /**
     * Generate Martingale strategy numbers
     */
    protected function generateMartingaleNumbers(array $config, int $maxNumbers): array
    {
        $multiplier = $config['martingale_multiplier'] ?? 2.0;
        $baseNumbers = $this->getLastLostNumbers();
        $numbers = [];

        foreach ($baseNumbers as $data) {
            if (count($numbers) >= $maxNumbers) break;

            $consecutiveLosses = $data['consecutive_losses'] ?? 1;
            $adjustedConfidence = min(0.8, 0.3 + ($consecutiveLosses * 0.1));

            $numbers[] = [
                'number' => $data['number'],
                'confidence' => $adjustedConfidence,
                'priority' => $consecutiveLosses,
                'strategy' => 'martingale',
                'metadata' => array_merge($data, ['multiplier' => $multiplier])
            ];
        }

        return $numbers;
    }

    /**
     * Calculate bet amount for campaign
     */
    protected function calculateBetAmount(Campaign $campaign, array $numberData): float
    {
        $baseAmount = 10000; // Base bet amount
        $confidence = $numberData['confidence'] ?? 0.5;
        $priority = $numberData['priority'] ?? 1;

        // Apply confidence multiplier
        $amount = $baseAmount * (1 + $confidence);

        // Apply priority multiplier
        $amount *= (1 + ($priority * 0.1));

        // Apply campaign-specific multipliers
        if (isset($campaign->strategy_config['bet_multiplier'])) {
            $amount *= $campaign->strategy_config['bet_multiplier'];
        }

        // Check daily limit
        if ($campaign->daily_bet_limit) {
            $todayBets = $this->getTodayBetAmount($campaign);
            $remainingLimit = $campaign->daily_bet_limit - $todayBets;
            $amount = min($amount, $remainingLimit);
        }

        // Check available balance
        $amount = min($amount, $campaign->current_balance);

        // Round to nearest 1000
        return floor($amount / 1000) * 1000;
    }

    /**
     * Calculate bet amount for sub-campaign
     */
    protected function calculateSubCampaignBetAmount(SubCampaign $subCampaign, array $numberData): float
    {
        $baseAmount = 5000; // Smaller base for sub-campaigns
        $confidence = $numberData['confidence'] ?? 0.5;
        $priority = $numberData['priority'] ?? 1;

        // Apply confidence and priority multipliers
        $amount = $baseAmount * (1 + $confidence) * (1 + ($priority * 0.05));

        // Apply sub-campaign weight
        $amount *= $subCampaign->weight;

        // Apply strategy-specific multipliers
        if (isset($subCampaign->strategy_config['bet_multiplier'])) {
            $amount *= $subCampaign->strategy_config['bet_multiplier'];
        }

        // Check daily limit
        if ($subCampaign->daily_bet_limit) {
            $todayBets = $this->getTodaySubCampaignBetAmount($subCampaign);
            $remainingLimit = $subCampaign->daily_bet_limit - $todayBets;
            $amount = min($amount, $remainingLimit);
        }

        // Check available balance
        $amount = min($amount, $subCampaign->current_balance);

        // Round to nearest 1000
        return floor($amount / 1000) * 1000;
    }

    /**
     * Place a bet for campaign
     */
    protected function placeBet(Campaign $campaign, string $number, float $amount, array $metadata): ?CampaignBet
    {
        try {
            return DB::transaction(function () use ($campaign, $number, $amount, $metadata) {
                // Create bet record
                $bet = CampaignBet::create([
                    'campaign_id' => $campaign->id,
                    'sub_campaign_id' => null,
                    'lo_number' => $number,
                    'amount' => $amount,
                    'bet_date' => now()->toDateString(),
                    'status' => 'pending',
                    'strategy' => $metadata['strategy'] ?? 'unknown',
                    'confidence' => $metadata['confidence'] ?? 0.5,
                    'metadata' => $metadata
                ]);

                // Update campaign balance
                $campaign->decrement('current_balance', $amount);
                $campaign->increment('total_bet_amount', $amount);
                $campaign->increment('total_bets');
                $campaign->update(['last_bet_at' => now()]);

                return $bet;
            });

        } catch (\Exception $e) {
            Log::error("Failed to place bet for campaign {$campaign->id}", [
                'error' => $e->getMessage(),
                'number' => $number,
                'amount' => $amount
            ]);
            return null;
        }
    }

    /**
     * Place a bet for sub-campaign
     */
    protected function placeSubCampaignBet(SubCampaign $subCampaign, string $number, float $amount, array $metadata): ?CampaignBet
    {
        try {
            return DB::transaction(function () use ($subCampaign, $number, $amount, $metadata) {
                // Create bet record
                $bet = CampaignBet::create([
                    'campaign_id' => $subCampaign->parent_campaign_id,
                    'sub_campaign_id' => $subCampaign->id,
                    'lo_number' => $number,
                    'amount' => $amount,
                    'bet_date' => now()->toDateString(),
                    'status' => 'pending',
                    'strategy' => $metadata['strategy'] ?? 'unknown',
                    'confidence' => $metadata['confidence'] ?? 0.5,
                    'metadata' => $metadata
                ]);

                // Update sub-campaign balance
                $subCampaign->decrement('current_balance', $amount);
                $subCampaign->increment('total_bet_amount', $amount);
                $subCampaign->increment('total_bets');

                return $bet;
            });

        } catch (\Exception $e) {
            Log::error("Failed to place bet for sub-campaign {$subCampaign->id}", [
                'error' => $e->getMessage(),
                'number' => $number,
                'amount' => $amount
            ]);
            return null;
        }
    }

    /**
     * Validate bet for campaign
     */
    protected function validateBet(Campaign $campaign, float $amount): bool
    {
        // Check minimum amount
        if ($amount < 1000) return false;

        // Check balance
        if ($amount > $campaign->current_balance) return false;

        // Check daily limit
        if ($campaign->daily_bet_limit) {
            $todayBets = $this->getTodayBetAmount($campaign);
            if (($todayBets + $amount) > $campaign->daily_bet_limit) return false;
        }

        // Check risk management rules
        return $this->riskService->validateCampaignBet($campaign, $amount);
    }

    /**
     * Validate bet for sub-campaign
     */
    protected function validateSubCampaignBet(SubCampaign $subCampaign, float $amount): bool
    {
        // Check minimum amount
        if ($amount < 1000) return false;

        // Check balance
        if ($amount > $subCampaign->current_balance) return false;

        // Check daily limit
        if ($subCampaign->daily_bet_limit) {
            $todayBets = $this->getTodaySubCampaignBetAmount($subCampaign);
            if (($todayBets + $amount) > $subCampaign->daily_bet_limit) return false;
        }

        return true;
    }

    /**
     * Check if campaign should continue running
     */
    protected function shouldCampaignContinue(Campaign $campaign): bool
    {
        // Check if campaign has ended
        if ($campaign->days) {
            $endDate = $campaign->start_date->addDays($campaign->days);
            if (now()->toDateString() >= $endDate->toDateString()) {
                return false;
            }
        }

        // Check balance
        if ($campaign->current_balance <= 0) return false;

        // Check stop loss
        if ($campaign->auto_stop_loss && $campaign->stop_loss_amount) {
            $currentLoss = $campaign->initial_balance - $campaign->current_balance;
            if ($currentLoss >= $campaign->stop_loss_amount) return false;
        }

        // Check take profit
        if ($campaign->auto_take_profit && $campaign->take_profit_amount) {
            $currentProfit = $campaign->current_balance - $campaign->initial_balance;
            if ($currentProfit >= $campaign->take_profit_amount) return false;
        }

        return true;
    }

    /**
     * Check if campaign can bet today
     */
    protected function canCampaignBetToday(Campaign $campaign): bool
    {
        if (!$campaign->daily_bet_limit) return true;

        $todayBets = $this->getTodayBetAmount($campaign);
        return $todayBets < $campaign->daily_bet_limit;
    }

    /**
     * Check if sub-campaign can bet today
     */
    protected function canSubCampaignBetToday(SubCampaign $subCampaign): bool
    {
        if (!$subCampaign->daily_bet_limit) return true;

        $todayBets = $this->getTodaySubCampaignBetAmount($subCampaign);
        return $todayBets < $subCampaign->daily_bet_limit;
    }

    /**
     * Get today's bet amount for campaign
     */
    protected function getTodayBetAmount(Campaign $campaign): float
    {
        return CampaignBet::where('campaign_id', $campaign->id)
            ->whereNull('sub_campaign_id')
            ->where('bet_date', now()->toDateString())
            ->sum('amount');
    }

    /**
     * Get today's bet amount for sub-campaign
     */
    protected function getTodaySubCampaignBetAmount(SubCampaign $subCampaign): float
    {
        return CampaignBet::where('sub_campaign_id', $subCampaign->id)
            ->where('bet_date', now()->toDateString())
            ->sum('amount');
    }

    /**
     * Update campaign execution status
     */
    protected function updateCampaignExecutionStatus(Campaign $campaign): void
    {
        $campaign->touch(); // Update timestamp

        // Check if campaign should be stopped
        if (!$this->shouldCampaignContinue($campaign)) {
            $reason = $this->determineCampaignStopReason($campaign);
            $this->schedulerService->stopCampaign($campaign, $reason);
        }
    }

    /**
     * Update sub-campaign performance
     */
    protected function updateSubCampaignPerformance(SubCampaign $subCampaign, array $bettingResult): void
    {
        $subCampaign->touch(); // Update timestamp

        // Additional performance metrics can be updated here
        // This will be extended when bet results are processed
    }

    /**
     * Determine campaign stop reason
     */
    protected function determineCampaignStopReason(Campaign $campaign): string
    {
        if ($campaign->current_balance <= 0) return 'balance_depleted';

        if ($campaign->auto_stop_loss && $campaign->stop_loss_amount) {
            $currentLoss = $campaign->initial_balance - $campaign->current_balance;
            if ($currentLoss >= $campaign->stop_loss_amount) return 'stop_loss';
        }

        if ($campaign->auto_take_profit && $campaign->take_profit_amount) {
            $currentProfit = $campaign->current_balance - $campaign->initial_balance;
            if ($currentProfit >= $campaign->take_profit_amount) return 'target_reached';
        }

        if ($campaign->days) {
            $endDate = $campaign->start_date->addDays($campaign->days);
            if (now()->toDateString() >= $endDate->toDateString()) return 'time_limit';
        }

        return 'unknown';
    }

    /**
     * Get recent lottery results
     */
    protected function getRecentLotteryResults(int $days = 7): Collection
    {
        return LotteryResult::where('result_date', '>=', now()->subDays($days))
            ->orderBy('result_date', 'desc')
            ->get();
    }

    /**
     * Get last lost numbers for Martingale strategy
     */
    protected function getLastLostNumbers(int $limit = 5): array
    {
        $lostBets = CampaignBet::where('is_win', false)
            ->where('bet_date', '>=', now()->subDays(7))
            ->select('lo_number')
            ->selectRaw('COUNT(*) as consecutive_losses')
            ->groupBy('lo_number')
            ->orderBy('consecutive_losses', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($bet) {
                return [
                    'number' => $bet->lo_number,
                    'consecutive_losses' => $bet->consecutive_losses
                ];
            })
            ->toArray();

        return $lostBets;
    }
}
