<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\SubCampaign;
use App\Models\CampaignBet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * Micro-task 2.3.1.1: Campaign performance metrics (4h)
 * Service for calculating comprehensive campaign performance metrics
 */
class CampaignMetricsService
{
    /**
     * Get comprehensive performance metrics for a campaign
     */
    public function getCampaignMetrics(Campaign $campaign, bool $useCache = true): array
    {
        $cacheKey = "campaign_metrics_{$campaign->id}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $metrics = [
            'basic_metrics' => $this->getBasicMetrics($campaign),
            'financial_metrics' => $this->getFinancialMetrics($campaign),
            'performance_metrics' => $this->getPerformanceMetrics($campaign),
            'risk_metrics' => $this->getRiskMetrics($campaign),
            'time_metrics' => $this->getTimeMetrics($campaign),
            'betting_metrics' => $this->getBettingMetrics($campaign),
            'sub_campaign_metrics' => $this->getSubCampaignMetrics($campaign),
            'trend_metrics' => $this->getTrendMetrics($campaign),
            'real_time_metrics' => $this->getRealTimeMetrics($campaign)
        ];

        // Cache for 5 minutes for active campaigns, 1 hour for completed ones
        $ttl = in_array($campaign->status, ['active', 'running']) ? 300 : 3600;
        Cache::put($cacheKey, $metrics, $ttl);

        return $metrics;
    }

    /**
     * Get basic campaign metrics
     */
    protected function getBasicMetrics(Campaign $campaign): array
    {
        return [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'status' => $campaign->status,
            'type' => $campaign->campaign_type,
            'days_running' => $this->getDaysRunning($campaign),
            'days_remaining' => $this->getDaysRemaining($campaign),
            'completion_percentage' => $this->getCompletionPercentage($campaign),
            'last_updated' => now()->toISOString()
        ];
    }

    /**
     * Get financial performance metrics
     */
    protected function getFinancialMetrics(Campaign $campaign): array
    {
        $totalBets = $campaign->bets()->count();
        $totalBetAmount = $campaign->bets()->sum('bet_amount');
        $totalWinAmount = $campaign->bets()->sum('win_amount');
        $totalCommission = $campaign->bets()->sum('commission');

        $profitLoss = $totalWinAmount - $totalBetAmount - $totalCommission;
        $roi = $totalBetAmount > 0 ? ($profitLoss / $campaign->initial_balance) * 100 : 0;
        $roiAnnualized = $this->calculateAnnualizedROI($campaign, $roi);

        return [
            'initial_balance' => $campaign->initial_balance,
            'current_balance' => $campaign->current_balance,
            'total_bet_amount' => $totalBetAmount,
            'total_win_amount' => $totalWinAmount,
            'total_commission' => $totalCommission,
            'profit_loss' => $profitLoss,
            'roi_percentage' => round($roi, 2),
            'roi_annualized' => round($roiAnnualized, 2),
            'balance_utilization' => $campaign->initial_balance > 0 ?
                round(($totalBetAmount / $campaign->initial_balance) * 100, 2) : 0,
            'remaining_balance_percentage' => $campaign->initial_balance > 0 ?
                round(($campaign->current_balance / $campaign->initial_balance) * 100, 2) : 0,
            'average_bet_size' => $totalBets > 0 ? round($totalBetAmount / $totalBets, 0) : 0,
            'profit_per_day' => $this->calculateProfitPerDay($campaign, $profitLoss),
            'break_even_point' => $this->calculateBreakEvenPoint($campaign),
            'target_progress' => $this->calculateTargetProgress($campaign, $profitLoss)
        ];
    }

    /**
     * Get performance metrics
     */
    protected function getPerformanceMetrics(Campaign $campaign): array
    {
        $bets = $campaign->bets();
        $totalBets = $bets->count();
        $winningBets = $bets->where('status', 'won')->count();
        $losingBets = $bets->where('status', 'lost')->count();

        $winRate = $totalBets > 0 ? ($winningBets / $totalBets) * 100 : 0;
        $lossRate = $totalBets > 0 ? ($losingBets / $totalBets) * 100 : 0;

        $avgWinAmount = $bets->where('status', 'won')->avg('win_amount') ?? 0;
        $avgLossAmount = $bets->where('status', 'lost')->avg('bet_amount') ?? 0;

        return [
            'total_bets' => $totalBets,
            'winning_bets' => $winningBets,
            'losing_bets' => $losingBets,
            'pending_bets' => $bets->where('status', 'pending')->count(),
            'win_rate' => round($winRate, 2),
            'loss_rate' => round($lossRate, 2),
            'average_win_amount' => round($avgWinAmount, 0),
            'average_loss_amount' => round($avgLossAmount, 0),
            'profit_factor' => $avgLossAmount > 0 ? round($avgWinAmount / $avgLossAmount, 2) : null,
            'largest_win' => $bets->max('win_amount') ?? 0,
            'largest_loss' => $bets->max('bet_amount') ?? 0,
            'consecutive_wins' => $this->getConsecutiveWins($campaign),
            'consecutive_losses' => $this->getConsecutiveLosses($campaign),
            'best_performing_numbers' => $this->getBestPerformingNumbers($campaign),
            'worst_performing_numbers' => $this->getWorstPerformingNumbers($campaign),
            'strategy_effectiveness' => $this->calculateStrategyEffectiveness($campaign)
        ];
    }

    /**
     * Calculate days running
     */
    protected function getDaysRunning(Campaign $campaign): int
    {
        $startDate = $campaign->start_date ?? $campaign->created_at->toDateString();
        return now()->diffInDays($startDate);
    }

    /**
     * Calculate days remaining
     */
    protected function getDaysRemaining(Campaign $campaign): ?int
    {
        if (!$campaign->end_date && !$campaign->days) {
            return null;
        }

        $endDate = $campaign->end_date ??
            $campaign->start_date->addDays($campaign->days);

        $remaining = now()->diffInDays($endDate, false);
        return max(0, $remaining);
    }

    /**
     * Calculate completion percentage
     */
    protected function getCompletionPercentage(Campaign $campaign): float
    {
        if (!$campaign->days) return 0;

        $daysRunning = $this->getDaysRunning($campaign);
        return min(100, ($daysRunning / $campaign->days) * 100);
    }

    /**
     * Calculate annualized ROI
     */
    protected function calculateAnnualizedROI(Campaign $campaign, float $roi): float
    {
        $daysRunning = max(1, $this->getDaysRunning($campaign));
        return ($roi / $daysRunning) * 365;
    }

    /**
     * Calculate profit per day
     */
    protected function calculateProfitPerDay(Campaign $campaign, float $profitLoss): float
    {
        $daysRunning = max(1, $this->getDaysRunning($campaign));
        return $profitLoss / $daysRunning;
    }

    /**
     * Calculate break-even point
     */
    protected function calculateBreakEvenPoint(Campaign $campaign): array
    {
        $totalBetAmount = $campaign->bets()->sum('bet_amount');
        $totalWinAmount = $campaign->bets()->sum('win_amount');
        $totalCommission = $campaign->bets()->sum('commission');

        $netResult = $totalWinAmount - $totalBetAmount - $totalCommission;
        $additionalNeeded = $netResult < 0 ? abs($netResult) : 0;

        return [
            'is_break_even' => $netResult >= 0,
            'current_net' => $netResult,
            'additional_needed' => $additionalNeeded,
            'break_even_percentage' => $totalBetAmount > 0 ?
                min(100, (($totalWinAmount + $totalCommission) / $totalBetAmount) * 100) : 0
        ];
    }

    /**
     * Calculate target progress
     */
    protected function calculateTargetProgress(Campaign $campaign, float $profitLoss): array
    {
        if (!$campaign->target_profit) {
            return ['has_target' => false];
        }

        $progress = ($profitLoss / $campaign->target_profit) * 100;

        return [
            'has_target' => true,
            'target_amount' => $campaign->target_profit,
            'current_profit' => $profitLoss,
            'progress_percentage' => round($progress, 2),
            'remaining_to_target' => max(0, $campaign->target_profit - $profitLoss),
            'is_target_achieved' => $profitLoss >= $campaign->target_profit
        ];
    }

    /**
     * Get risk metrics
     */
    protected function getRiskMetrics(Campaign $campaign): array
    {
        return [
            'max_drawdown' => $this->calculateMaxDrawdown($campaign),
            'max_drawdown_percentage' => $this->calculateMaxDrawdownPercentage($campaign),
            'value_at_risk' => $this->calculateValueAtRisk($campaign),
            'sharpe_ratio' => $this->calculateSharpeRatio($campaign),
            'volatility' => $this->calculateVolatility($campaign),
            'current_risk_exposure' => $this->getCurrentRiskExposure($campaign),
            'daily_var_95' => $this->calculateDailyVaR($campaign, 0.95),
            'risk_adjusted_return' => $this->calculateRiskAdjustedReturn($campaign)
        ];
    }

    /**
     * Get time-based metrics
     */
    protected function getTimeMetrics(Campaign $campaign): array
    {
        return [
            'start_date' => $campaign->start_date ?? $campaign->created_at->toDateString(),
            'end_date' => $campaign->end_date,
            'days_planned' => $campaign->days,
            'days_running' => $this->getDaysRunning($campaign),
            'days_remaining' => $this->getDaysRemaining($campaign),
            'completion_percentage' => $this->getCompletionPercentage($campaign),
            'average_bets_per_day' => $this->getAverageBetsPerDay($campaign),
            'average_amount_per_day' => $this->getAverageAmountPerDay($campaign)
        ];
    }

    /**
     * Get betting pattern metrics
     */
    protected function getBettingMetrics(Campaign $campaign): array
    {
        return [
            'betting_frequency' => $this->getBettingFrequency($campaign),
            'favorite_bet_types' => $this->getFavoriteBetTypes($campaign),
            'average_odds' => $this->getAverageOdds($campaign),
            'bet_size_distribution' => $this->getBetSizeDistribution($campaign),
            'strategy_adherence' => $this->getStrategyAdherence($campaign)
        ];
    }

    /**
     * Get sub-campaign aggregated metrics
     */
    protected function getSubCampaignMetrics(Campaign $campaign): array
    {
        $subCampaigns = $campaign->subCampaigns();

        if ($subCampaigns->count() === 0) {
            return [
                'has_sub_campaigns' => false,
                'total_sub_campaigns' => 0
            ];
        }

        return [
            'has_sub_campaigns' => true,
            'total_sub_campaigns' => $subCampaigns->count(),
            'active_sub_campaigns' => $subCampaigns->where('status', 'active')->count(),
            'completed_sub_campaigns' => $subCampaigns->where('status', 'completed')->count(),
            'sub_campaign_performance' => $this->getSubCampaignPerformance($campaign)
        ];
    }

    /**
     * Get trend metrics
     */
    protected function getTrendMetrics(Campaign $campaign): array
    {
        return [
            'performance_trend' => $this->getPerformanceTrend($campaign),
            'balance_trend' => $this->getBalanceTrend($campaign),
            'win_rate_trend' => $this->getWinRateTrend($campaign),
            'profit_trend' => $this->getProfitTrend($campaign)
        ];
    }

    /**
     * Get real-time metrics for active monitoring
     */
    protected function getRealTimeMetrics(Campaign $campaign): array
    {
        if (!in_array($campaign->status, ['active', 'running'])) {
            return ['is_active' => false];
        }

        $recentBets = $campaign->bets()->where('created_at', '>=', now()->subHours(24))->get();

        return [
            'is_active' => true,
            'last_bet_time' => $campaign->bets()->latest()->first()?->created_at,
            'bets_last_24h' => $recentBets->count(),
            'profit_last_24h' => $recentBets->sum('win_amount') - $recentBets->sum('bet_amount'),
            'current_streak' => $this->getCurrentStreak($campaign),
            'alert_level' => $this->getCurrentAlertLevel($campaign),
            'health_score' => $this->getCampaignHealthScore($campaign)
        ];
    }

    protected function getAverageBetsPerDay(Campaign $campaign): float
    {
        $daysRunning = max(1, $this->getDaysRunning($campaign));
        return $campaign->bets()->count() / $daysRunning;
    }

    protected function getAverageAmountPerDay(Campaign $campaign): float
    {
        $daysRunning = max(1, $this->getDaysRunning($campaign));
        return $campaign->bets()->sum('bet_amount') / $daysRunning;
    }

    // Placeholder methods for complex calculations
    protected function getConsecutiveWins(Campaign $campaign): int { return 0; }
    protected function getConsecutiveLosses(Campaign $campaign): int { return 0; }
    protected function getBestPerformingNumbers(Campaign $campaign): array { return []; }
    protected function getWorstPerformingNumbers(Campaign $campaign): array { return []; }
    protected function calculateStrategyEffectiveness(Campaign $campaign): float { return 0.0; }
    protected function calculateMaxDrawdown(Campaign $campaign): float { return 0.0; }
    protected function calculateMaxDrawdownPercentage(Campaign $campaign): float { return 0.0; }
    protected function calculateValueAtRisk(Campaign $campaign): float { return 0.0; }
    protected function calculateSharpeRatio(Campaign $campaign): float { return 0.0; }
    protected function calculateVolatility(Campaign $campaign): float { return 0.0; }
    protected function calculateRiskAdjustedReturn(Campaign $campaign): float { return 0.0; }
    protected function getCurrentRiskExposure(Campaign $campaign): float { return 0.0; }
    protected function calculateDailyVaR(Campaign $campaign, float $confidence): float { return 0.0; }
    protected function getBettingFrequency(Campaign $campaign): array { return []; }
    protected function getFavoriteBetTypes(Campaign $campaign): array { return []; }
    protected function getAverageOdds(Campaign $campaign): float { return 0.0; }
    protected function getBetSizeDistribution(Campaign $campaign): array { return []; }
    protected function getStrategyAdherence(Campaign $campaign): float { return 0.0; }
    protected function getSubCampaignPerformance(Campaign $campaign): array { return []; }
    protected function getPerformanceTrend(Campaign $campaign): array { return []; }
    protected function getBalanceTrend(Campaign $campaign): array { return []; }
    protected function getWinRateTrend(Campaign $campaign): array { return []; }
    protected function getProfitTrend(Campaign $campaign): array { return []; }
    protected function getCurrentStreak(Campaign $campaign): int { return 0; }
    protected function getCurrentAlertLevel(Campaign $campaign): string { return 'normal'; }
    protected function getCampaignHealthScore(Campaign $campaign): float { return 100.0; }

    /**
     * Clear metrics cache for a campaign
     */
    public function clearCache(Campaign $campaign): void
    {
        Cache::forget("campaign_metrics_{$campaign->id}");
    }

    /**
     * Get metrics for multiple campaigns
     */
    public function getBulkMetrics(Collection $campaigns, bool $useCache = true): array
    {
        $results = [];

        foreach ($campaigns as $campaign) {
            $results[$campaign->id] = $this->getCampaignMetrics($campaign, $useCache);
        }

        return $results;
    }

    /**
     * Get summary metrics across all campaigns for a user
     */
    public function getUserCampaignSummary(int $userId): array
    {
        $campaigns = Campaign::where('user_id', $userId)->get();

        return [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->where('status', 'active')->count(),
            'completed_campaigns' => $campaigns->where('status', 'completed')->count(),
            'total_invested' => $campaigns->sum('initial_balance'),
            'current_value' => $campaigns->sum('current_balance'),
            'total_profit_loss' => $this->calculateTotalProfitLoss($campaigns),
            'average_roi' => $this->calculateAverageROI($campaigns)
        ];
    }

    protected function calculateTotalProfitLoss(Collection $campaigns): float
    {
        $total = 0;
        foreach ($campaigns as $campaign) {
            $bets = $campaign->bets();
            $total += $bets->sum('win_amount') - $bets->sum('bet_amount') - $bets->sum('commission');
        }
        return $total;
    }

    protected function calculateAverageROI(Collection $campaigns): float
    {
        if ($campaigns->isEmpty()) return 0;

        $totalROI = 0;
        $count = 0;

        foreach ($campaigns as $campaign) {
            $bets = $campaign->bets();
            $totalBetAmount = $bets->sum('bet_amount');
            $totalWinAmount = $bets->sum('win_amount');
            $totalCommission = $bets->sum('commission');

            if ($campaign->initial_balance > 0) {
                $profitLoss = $totalWinAmount - $totalBetAmount - $totalCommission;
                $roi = ($profitLoss / $campaign->initial_balance) * 100;
                $totalROI += $roi;
                $count++;
            }
        }

        return $count > 0 ? $totalROI / $count : 0;
    }
}
