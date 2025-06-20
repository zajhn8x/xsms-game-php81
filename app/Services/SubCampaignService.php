<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\SubCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Micro-task 2.1.4.4: Sub-campaign aggregation (4h)
 * Service for managing sub-campaigns and aggregating their data
 */
class SubCampaignService
{
    /**
     * Create multiple sub-campaigns from a parent campaign
     */
    public function createSubCampaigns(Campaign $parentCampaign, array $subCampaignConfigs): Collection
    {
        return DB::transaction(function () use ($parentCampaign, $subCampaignConfigs) {
            $subCampaigns = collect();
            $totalAllocatedBalance = 0;

            // Validate total allocation doesn't exceed parent balance
            foreach ($subCampaignConfigs as $config) {
                $totalAllocatedBalance += $config['allocated_balance'];
            }

            if ($totalAllocatedBalance > $parentCampaign->current_balance) {
                throw new \Exception('Tổng số dư phân bổ vượt quá số dư hiện tại của chiến dịch cha');
            }

            // Create sub-campaigns
            foreach ($subCampaignConfigs as $config) {
                $subCampaign = $this->createSingleSubCampaign($parentCampaign, $config);
                $subCampaigns->push($subCampaign);
            }

            // Update parent campaign balance
            $parentCampaign->decrement('current_balance', $totalAllocatedBalance);

            return $subCampaigns;
        });
    }

    /**
     * Create a single sub-campaign
     */
    public function createSingleSubCampaign(Campaign $parentCampaign, array $config): SubCampaign
    {
        $defaultConfig = [
            'parent_campaign_id' => $parentCampaign->id,
            'betting_strategy' => $parentCampaign->betting_strategy,
            'strategy_config' => $parentCampaign->strategy_config,
            'start_date' => $parentCampaign->start_date,
            'end_date' => $parentCampaign->end_date,
            'type' => 'segment',
            'status' => 'pending',
            'priority' => 1,
            'weight' => 1.0,
            'auto_start' => false,
            'auto_stop' => true
        ];

        $mergedConfig = array_merge($defaultConfig, $config);
        $mergedConfig['current_balance'] = $mergedConfig['allocated_balance'];

        return SubCampaign::create($mergedConfig);
    }

    /**
     * Split campaign balance across multiple sub-campaigns by percentage
     */
    public function splitCampaignByPercentage(Campaign $parentCampaign, array $splits): Collection
    {
        $totalPercentage = array_sum(array_column($splits, 'percentage'));

        if ($totalPercentage > 100) {
            throw new \Exception('Tổng phần trăm phân chia không được vượt quá 100%');
        }

        $availableBalance = $parentCampaign->current_balance;
        $configs = [];

        foreach ($splits as $split) {
            $allocatedBalance = ($split['percentage'] / 100) * $availableBalance;

            $configs[] = array_merge($split, [
                'allocated_balance' => $allocatedBalance,
                'type' => 'split'
            ]);
        }

        return $this->createSubCampaigns($parentCampaign, $configs);
    }

    /**
     * Create A/B test sub-campaigns
     */
    public function createABTestCampaigns(Campaign $parentCampaign, array $testConfigs): Collection
    {
        $configs = [];
        $balancePerTest = $parentCampaign->current_balance / count($testConfigs);

        foreach ($testConfigs as $index => $testConfig) {
            $configs[] = array_merge($testConfig, [
                'name' => $testConfig['name'] ?? "Test " . chr(65 + $index), // A, B, C...
                'allocated_balance' => $balancePerTest,
                'type' => 'test',
                'priority' => $index + 1
            ]);
        }

        return $this->createSubCampaigns($parentCampaign, $configs);
    }

    /**
     * Aggregate performance data from all sub-campaigns
     */
    public function aggregatePerformance(Campaign $parentCampaign): array
    {
        $subCampaigns = $parentCampaign->subCampaigns;

        if ($subCampaigns->isEmpty()) {
            return $this->getEmptyAggregation();
        }

        $aggregation = [
            'summary' => $this->aggregateSummary($subCampaigns),
            'financial' => $this->aggregateFinancial($subCampaigns),
            'betting' => $this->aggregateBetting($subCampaigns),
            'performance' => $this->aggregatePerformanceMetrics($subCampaigns),
            'by_type' => $this->aggregateByType($subCampaigns),
            'by_strategy' => $this->aggregateByStrategy($subCampaigns),
            'status_distribution' => $this->aggregateStatusDistribution($subCampaigns)
        ];

        return $aggregation;
    }

    /**
     * Aggregate summary statistics
     */
    protected function aggregateSummary(Collection $subCampaigns): array
    {
        return [
            'total_sub_campaigns' => $subCampaigns->count(),
            'active_sub_campaigns' => $subCampaigns->where('status', 'active')->count(),
            'completed_sub_campaigns' => $subCampaigns->where('status', 'completed')->count(),
            'total_allocated_balance' => $subCampaigns->sum('allocated_balance'),
            'total_current_balance' => $subCampaigns->sum('current_balance'),
            'total_profit_loss' => $subCampaigns->sum(function ($sc) {
                return $sc->profit_loss;
            }),
            'average_roi' => $subCampaigns->avg(function ($sc) {
                return $sc->roi;
            })
        ];
    }

    /**
     * Aggregate financial data
     */
    protected function aggregateFinancial(Collection $subCampaigns): array
    {
        $totalAllocated = $subCampaigns->sum('allocated_balance');
        $totalCurrent = $subCampaigns->sum('current_balance');
        $totalChange = $totalCurrent - $totalAllocated;

        return [
            'total_allocated' => $totalAllocated,
            'total_current' => $totalCurrent,
            'total_change' => $totalChange,
            'change_percentage' => $totalAllocated > 0 ?
                round(($totalChange / $totalAllocated) * 100, 2) : 0,
            'best_performer' => $this->getBestPerformer($subCampaigns, 'balance_change'),
            'worst_performer' => $this->getWorstPerformer($subCampaigns, 'balance_change')
        ];
    }

    /**
     * Aggregate betting statistics
     */
    protected function aggregateBetting(Collection $subCampaigns): array
    {
        $totalBets = $subCampaigns->sum('total_bets');
        $totalWins = $subCampaigns->sum('winning_bets');
        $totalBetAmount = $subCampaigns->sum('total_bet_amount');
        $totalWinAmount = $subCampaigns->sum('total_win_amount');
        $totalLossAmount = $subCampaigns->sum('total_loss_amount');

        return [
            'total_bets' => $totalBets,
            'total_bet_amount' => $totalBetAmount,
            'total_win_amount' => $totalWinAmount,
            'total_loss_amount' => $totalLossAmount,
            'overall_win_rate' => $totalBets > 0 ? round(($totalWins / $totalBets) * 100, 2) : 0,
            'net_profit_loss' => $totalWinAmount - $totalLossAmount,
            'average_bet_size' => $totalBets > 0 ? round($totalBetAmount / $totalBets, 2) : 0,
            'best_win_rate' => $this->getBestPerformer($subCampaigns, 'win_rate'),
            'most_active' => $this->getBestPerformer($subCampaigns, 'total_bets')
        ];
    }

    /**
     * Aggregate performance metrics
     */
    protected function aggregatePerformanceMetrics(Collection $subCampaigns): array
    {
        $rois = $subCampaigns->pluck('roi')->filter();
        $winRates = $subCampaigns->pluck('win_rate')->filter();

        return [
            'roi_statistics' => [
                'min' => $rois->min() ?? 0,
                'max' => $rois->max() ?? 0,
                'avg' => $rois->avg() ?? 0,
                'median' => $rois->median() ?? 0
            ],
            'win_rate_statistics' => [
                'min' => $winRates->min() ?? 0,
                'max' => $winRates->max() ?? 0,
                'avg' => $winRates->avg() ?? 0,
                'median' => $winRates->median() ?? 0
            ],
            'consistency_score' => $this->calculateConsistencyScore($subCampaigns),
            'risk_adjusted_performance' => $this->calculateRiskAdjustedPerformance($subCampaigns)
        ];
    }

    /**
     * Aggregate data by sub-campaign type
     */
    protected function aggregateByType(Collection $subCampaigns): array
    {
        return $subCampaigns->groupBy('type')->map(function ($group, $type) {
            return [
                'count' => $group->count(),
                'total_allocated' => $group->sum('allocated_balance'),
                'total_current' => $group->sum('current_balance'),
                'total_bets' => $group->sum('total_bets'),
                'avg_roi' => $group->avg(function ($sc) { return $sc->roi; }),
                'avg_win_rate' => $group->avg(function ($sc) { return $sc->win_rate; })
            ];
        })->toArray();
    }

    /**
     * Aggregate data by betting strategy
     */
    protected function aggregateByStrategy(Collection $subCampaigns): array
    {
        return $subCampaigns->groupBy('betting_strategy')->map(function ($group, $strategy) {
            return [
                'count' => $group->count(),
                'total_allocated' => $group->sum('allocated_balance'),
                'total_current' => $group->sum('current_balance'),
                'total_bets' => $group->sum('total_bets'),
                'avg_roi' => $group->avg(function ($sc) { return $sc->roi; }),
                'avg_win_rate' => $group->avg(function ($sc) { return $sc->win_rate; })
            ];
        })->toArray();
    }

    /**
     * Aggregate status distribution
     */
    protected function aggregateStatusDistribution(Collection $subCampaigns): array
    {
        return $subCampaigns->groupBy('status')->map(function ($group) {
            return [
                'count' => $group->count(),
                'percentage' => round(($group->count() / $subCampaigns->count()) * 100, 1)
            ];
        })->toArray();
    }

    /**
     * Get best performer by metric
     */
    protected function getBestPerformer(Collection $subCampaigns, string $metric): ?array
    {
        $best = $subCampaigns->sortByDesc($metric)->first();

        if (!$best) return null;

        return [
            'id' => $best->id,
            'name' => $best->name,
            'value' => $best->$metric
        ];
    }

    /**
     * Get worst performer by metric
     */
    protected function getWorstPerformer(Collection $subCampaigns, string $metric): ?array
    {
        $worst = $subCampaigns->sortBy($metric)->first();

        if (!$worst) return null;

        return [
            'id' => $worst->id,
            'name' => $worst->name,
            'value' => $worst->$metric
        ];
    }

    /**
     * Calculate consistency score across sub-campaigns
     */
    protected function calculateConsistencyScore(Collection $subCampaigns): float
    {
        $rois = $subCampaigns->pluck('roi')->filter();

        if ($rois->count() < 2) return 0;

        $mean = $rois->avg();
        $variance = $rois->map(function ($roi) use ($mean) {
            return pow($roi - $mean, 2);
        })->avg();

        $standardDeviation = sqrt($variance);

        // Lower standard deviation = higher consistency
        return round(max(0, 100 - $standardDeviation), 2);
    }

    /**
     * Calculate risk-adjusted performance
     */
    protected function calculateRiskAdjustedPerformance(Collection $subCampaigns): array
    {
        $performances = $subCampaigns->map(function ($subCampaign) {
            $roi = $subCampaign->roi;
            $winRate = $subCampaign->win_rate;
            $totalBets = $subCampaign->total_bets;

            // Simple risk-adjusted score: ROI weighted by win rate and bet count
            $riskAdjustedScore = $totalBets > 0 ?
                ($roi * ($winRate / 100) * log($totalBets + 1)) : 0;

            return [
                'id' => $subCampaign->id,
                'name' => $subCampaign->name,
                'roi' => $roi,
                'win_rate' => $winRate,
                'risk_adjusted_score' => $riskAdjustedScore
            ];
        })->sortByDesc('risk_adjusted_score');

        return [
            'rankings' => $performances->values()->toArray(),
            'best_risk_adjusted' => $performances->first(),
            'average_score' => $performances->avg('risk_adjusted_score')
        ];
    }

    /**
     * Rebalance sub-campaign allocations
     */
    public function rebalanceSubCampaigns(Campaign $parentCampaign, array $newAllocations): bool
    {
        return DB::transaction(function () use ($parentCampaign, $newAllocations) {
            $subCampaigns = $parentCampaign->subCampaigns()
                ->whereIn('status', ['active', 'pending'])
                ->get()
                ->keyBy('id');

            $totalNewAllocation = array_sum(array_values($newAllocations));
            $totalCurrentBalance = $subCampaigns->sum('current_balance') + $parentCampaign->current_balance;

            if ($totalNewAllocation > $totalCurrentBalance) {
                throw new \Exception('Tổng phân bổ mới vượt quá số dư khả dụng');
            }

            // Return all balances to parent first
            foreach ($subCampaigns as $subCampaign) {
                $parentCampaign->increment('current_balance', $subCampaign->current_balance);
                $subCampaign->update(['current_balance' => 0, 'allocated_balance' => 0]);
            }

            // Reallocate according to new allocations
            foreach ($newAllocations as $subCampaignId => $newBalance) {
                if (isset($subCampaigns[$subCampaignId]) && $newBalance > 0) {
                    $subCampaigns[$subCampaignId]->update([
                        'allocated_balance' => $newBalance,
                        'current_balance' => $newBalance
                    ]);
                    $parentCampaign->decrement('current_balance', $newBalance);
                }
            }

            return true;
        });
    }

    /**
     * Get empty aggregation structure
     */
    protected function getEmptyAggregation(): array
    {
        return [
            'summary' => [
                'total_sub_campaigns' => 0,
                'active_sub_campaigns' => 0,
                'completed_sub_campaigns' => 0,
                'total_allocated_balance' => 0,
                'total_current_balance' => 0,
                'total_profit_loss' => 0,
                'average_roi' => 0
            ],
            'financial' => [
                'total_allocated' => 0,
                'total_current' => 0,
                'total_change' => 0,
                'change_percentage' => 0,
                'best_performer' => null,
                'worst_performer' => null
            ],
            'betting' => [
                'total_bets' => 0,
                'total_bet_amount' => 0,
                'total_win_amount' => 0,
                'total_loss_amount' => 0,
                'overall_win_rate' => 0,
                'net_profit_loss' => 0,
                'average_bet_size' => 0,
                'best_win_rate' => null,
                'most_active' => null
            ],
            'performance' => [
                'roi_statistics' => ['min' => 0, 'max' => 0, 'avg' => 0, 'median' => 0],
                'win_rate_statistics' => ['min' => 0, 'max' => 0, 'avg' => 0, 'median' => 0],
                'consistency_score' => 0,
                'risk_adjusted_performance' => ['rankings' => [], 'best_risk_adjusted' => null, 'average_score' => 0]
            ],
            'by_type' => [],
            'by_strategy' => [],
            'status_distribution' => []
        ];
    }

    /**
     * Auto-start eligible sub-campaigns
     */
    public function autoStartSubCampaigns(Campaign $parentCampaign): int
    {
        $started = 0;

        $eligibleSubCampaigns = $parentCampaign->subCampaigns()
            ->where('status', 'pending')
            ->where('auto_start', true)
            ->where('start_date', '<=', now())
            ->get();

        foreach ($eligibleSubCampaigns as $subCampaign) {
            if ($subCampaign->start()) {
                $started++;
            }
        }

        return $started;
    }

    /**
     * Auto-stop eligible sub-campaigns
     */
    public function autoStopSubCampaigns(Campaign $parentCampaign): int
    {
        $stopped = 0;

        $activeSubCampaigns = $parentCampaign->subCampaigns()
            ->where('status', 'active')
            ->get();

        foreach ($activeSubCampaigns as $subCampaign) {
            if ($subCampaign->shouldAutoStop() && $subCampaign->stop('auto_stop')) {
                $stopped++;
            }
        }

        return $stopped;
    }
}
