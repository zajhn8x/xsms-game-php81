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
class CampaignMonitoringService
{
    /**
     * Monitor campaign performance and generate alerts
     */
    public function monitorCampaignPerformance(Campaign $campaign): array
    {
        // Performance monitoring logic from existing implementation
        $alerts = [];

        // Check basic performance alerts
        $metrics = $this->getCampaignMetrics($campaign);

        // Win rate alert
        if (isset($metrics['performance']['win_rate'])) {
            $winRate = $metrics['performance']['win_rate'];
            $totalBets = $metrics['performance']['total_bets'] ?? 0;

            if ($winRate < 15 && $totalBets >= 10) {
                $alerts[] = [
                    'type' => 'critical',
                    'category' => 'performance',
                    'title' => 'Tỷ lệ thắng cực thấp',
                    'message' => "Tỷ lệ thắng chỉ {$winRate}% (nguy hiểm)",
                    'value' => $winRate,
                    'action_required' => 'auto_stop',
                    'priority' => 'high',
                    'timestamp' => now()
                ];
            } elseif ($winRate < 30 && $totalBets >= 5) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'performance',
                    'title' => 'Tỷ lệ thắng thấp',
                    'message' => "Tỷ lệ thắng {$winRate}% cần theo dõi",
                    'value' => $winRate,
                    'action_required' => 'review',
                    'priority' => 'medium',
                    'timestamp' => now()
                ];
            }
        }

        // Balance alert
        if (isset($metrics['balance']['current']) && isset($metrics['balance']['initial'])) {
            $currentBalance = $metrics['balance']['current'];
            $initialBalance = $metrics['balance']['initial'];
            $balancePercentage = ($currentBalance / $initialBalance) * 100;

            if ($balancePercentage < 10) {
                $alerts[] = [
                    'type' => 'critical',
                    'category' => 'balance',
                    'title' => 'Số dư cực thấp',
                    'message' => "Số dư còn {$balancePercentage}% so với ban đầu",
                    'value' => $balancePercentage,
                    'action_required' => 'auto_stop',
                    'priority' => 'high',
                    'timestamp' => now()
                ];
            } elseif ($balancePercentage < 25) {
                $alerts[] = [
                    'type' => 'warning',
                    'category' => 'balance',
                    'title' => 'Số dư thấp',
                    'message' => "Số dư còn {$balancePercentage}% so với ban đầu",
                    'value' => $balancePercentage,
                    'action_required' => 'review',
                    'priority' => 'medium',
                    'timestamp' => now()
                ];
            }
        }

        return $alerts;
    }

    /**
     * Get comprehensive campaign metrics
     */
    public function getCampaignMetrics(Campaign $campaign): array
    {
        $bets = $campaign->bets();
        $totalBets = $bets->count();
        $winningBets = $bets->where('is_win', true)->count();
        $totalBetAmount = $bets->sum('bet_amount');
        $totalWinAmount = $bets->sum('win_amount');

        // Calculate performance metrics
        $winRate = $totalBets > 0 ? round(($winningBets / $totalBets) * 100, 2) : 0;
        $roi = $totalBetAmount > 0 ? round((($totalWinAmount - $totalBetAmount) / $totalBetAmount) * 100, 2) : 0;
        $profitLoss = $totalWinAmount - $totalBetAmount;

        // Calculate consecutive losses
        $consecutiveLosses = $this->getConsecutiveLosses($campaign);

        // Calculate betting frequency
        $firstBet = $bets->oldest()->first();
        $hoursActive = $firstBet ? $firstBet->created_at->diffInHours(now()) : 1;
        $betsPerHour = $hoursActive > 0 ? round($totalBets / $hoursActive, 2) : 0;

        return [
            'performance' => [
                'win_rate' => $winRate,
                'roi' => $roi,
                'profit_loss' => $profitLoss,
                'total_bets' => $totalBets,
                'winning_bets' => $winningBets,
                'losing_bets' => $totalBets - $winningBets,
                'consecutive_losses' => $consecutiveLosses
            ],
            'balance' => [
                'initial' => $campaign->initial_balance,
                'current' => $campaign->current_balance,
                'change' => $campaign->current_balance - $campaign->initial_balance,
                'change_percentage' => $campaign->initial_balance > 0 ?
                    round((($campaign->current_balance - $campaign->initial_balance) / $campaign->initial_balance) * 100, 2) : 0
            ],
            'betting' => [
                'total_amount' => $totalBetAmount,
                'average_bet' => $totalBets > 0 ? round($totalBetAmount / $totalBets, 2) : 0,
                'max_bet_amount' => $bets->max('bet_amount') ?? 0,
                'bets_per_hour' => $betsPerHour
            ],
            'trends' => [
                'performance_trend' => $this->getPerformanceTrend($campaign),
                'balance_trend' => $this->getBalanceTrend($campaign)
            ],
            'timestamp' => now()
        ];
    }

    protected function getConsecutiveLosses(Campaign $campaign): int
    {
        $recentBets = $campaign->bets()->orderBy('created_at', 'desc')->limit(20)->get();
        $consecutiveLosses = 0;

        foreach ($recentBets as $bet) {
            if (!$bet->is_win) {
                $consecutiveLosses++;
            } else {
                break;
            }
        }

        return $consecutiveLosses;
    }

    protected function getPerformanceTrend(Campaign $campaign): array
    {
        // Simple trend analysis - compare last 7 days vs previous 7 days
        $lastWeekWinRate = $this->getWinRateForPeriod($campaign, now()->subDays(7), now());
        $previousWeekWinRate = $this->getWinRateForPeriod($campaign, now()->subDays(14), now()->subDays(7));

        $change = $lastWeekWinRate - $previousWeekWinRate;

        return [
            'direction' => $change > 5 ? 'improving' : ($change < -5 ? 'declining' : 'stable'),
            'change_percentage' => round($change, 2),
            'severity' => abs($change) > 15 ? 'high' : (abs($change) > 5 ? 'medium' : 'low'),
            'period' => '7 ngày'
        ];
    }

    protected function getBalanceTrend(Campaign $campaign): array
    {
        $betsLast24h = $campaign->bets()->where('created_at', '>=', now()->subDay())->get();
        $dailyChange = $betsLast24h->sum('win_amount') - $betsLast24h->sum('bet_amount');

        return [
            'daily_change' => $dailyChange,
            'trend' => $dailyChange > 0 ? 'positive' : 'negative'
        ];
    }

    protected function getWinRateForPeriod(Campaign $campaign, $start, $end): float
    {
        $bets = $campaign->bets()->whereBetween('created_at', [$start, $end]);
        $totalBets = $bets->count();
        $winningBets = $bets->where('is_win', true)->count();

        return $totalBets > 0 ? round(($winningBets / $totalBets) * 100, 2) : 0;
    }
}