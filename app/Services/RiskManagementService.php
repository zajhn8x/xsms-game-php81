<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\User;
use App\Models\RiskManagementRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RiskManagementService
{
    public function checkUserRisk(User $user): bool
    {
        $riskRules = $user->riskRules()->where('is_active', true)->get();

        foreach ($riskRules as $rule) {
            $context = $this->buildUserRiskContext($user);

            if ($rule->shouldTrigger($context)) {
                $this->triggerRiskRule($rule, $context);
                return false; // Risk triggered, stop processing
            }
        }

        return true; // No risk detected
    }

    public function checkCampaignRisk(Campaign $campaign): bool
    {
        $user = $campaign->user;

        // Check user-level risk rules
        if (!$this->checkUserRisk($user)) {
            return false;
        }

        // Check campaign-specific risk
        $context = $this->buildCampaignRiskContext($campaign);

        // Check daily loss limit
        if ($this->isDailyLossLimitExceeded($campaign, $context)) {
            $this->pauseCampaignForRisk($campaign, 'Daily loss limit exceeded');
            return false;
        }

        // Check consecutive losses
        if ($this->isConsecutiveLossLimitExceeded($campaign, $context)) {
            $this->pauseCampaignForRisk($campaign, 'Consecutive loss limit exceeded');
            return false;
        }

        // Check balance threshold
        if ($this->isBalanceThresholdBreached($campaign, $context)) {
            $this->pauseCampaignForRisk($campaign, 'Balance threshold breached');
            return false;
        }

        return true;
    }

    protected function buildUserRiskContext(User $user): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        // Get user's betting activity
        $todayBets = $this->getUserBetsForPeriod($user, $today, now());
        $weekBets = $this->getUserBetsForPeriod($user, $thisWeek, now());
        $monthBets = $this->getUserBetsForPeriod($user, $thisMonth, now());

        // Calculate losses
        $todayLoss = $this->calculateLoss($todayBets);
        $weekLoss = $this->calculateLoss($weekBets);
        $monthLoss = $this->calculateLoss($monthBets);

        // Get consecutive losses
        $consecutiveLosses = $this->getUserConsecutiveLosses($user);

        // Get current balances
        $wallet = $user->wallet;

        return [
            'user' => $user,
            'today_bets' => $todayBets,
            'week_bets' => $weekBets,
            'month_bets' => $monthBets,
            'daily_loss' => $todayLoss,
            'weekly_loss' => $weekLoss,
            'monthly_loss' => $monthLoss,
            'consecutive_losses' => $consecutiveLosses,
            'total_bet_amount' => $todayBets->sum('amount'),
            'balance' => $wallet ? $wallet->usable_balance : 0,
            'real_balance' => $wallet ? $wallet->real_balance : 0,
            'win_streak' => $this->getUserWinStreak($user)
        ];
    }

    protected function buildCampaignRiskContext(Campaign $campaign): array
    {
        $today = now()->startOfDay();

        // Get campaign's today bets
        $todayBets = $campaign->bets()->whereDate('created_at', $today)->get();

        // Get recent bets for trend analysis
        $recentBets = $campaign->bets()
            ->where('created_at', '>=', $today->copy()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'campaign' => $campaign,
            'today_bets' => $todayBets,
            'recent_bets' => $recentBets,
            'daily_loss' => $this->calculateLoss($todayBets),
            'consecutive_losses' => $this->getCampaignConsecutiveLosses($campaign),
            'current_balance' => $campaign->current_balance,
            'profit_loss' => $campaign->current_balance - $campaign->initial_balance,
            'daily_bet_count' => $todayBets->count()
        ];
    }

    protected function getUserBetsForPeriod(User $user, $startDate, $endDate)
    {
        return DB::table('campaign_bets')
            ->join('campaigns', 'campaign_bets.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.user_id', $user->id)
            ->whereBetween('campaign_bets.created_at', [$startDate, $endDate])
            ->select('campaign_bets.*')
            ->get();
    }

    protected function calculateLoss($bets): float
    {
        $totalLoss = 0;

        foreach ($bets as $bet) {
            if (!$bet->is_win) {
                $totalLoss += $bet->amount;
            }
        }

        return $totalLoss;
    }

    protected function getUserConsecutiveLosses(User $user): int
    {
        $recentBets = DB::table('campaign_bets')
            ->join('campaigns', 'campaign_bets.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.user_id', $user->id)
            ->orderBy('campaign_bets.created_at', 'desc')
            ->limit(50)
            ->select('campaign_bets.*')
            ->get();

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

    protected function getCampaignConsecutiveLosses(Campaign $campaign): int
    {
        $recentBets = $campaign->bets()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

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

    protected function getUserWinStreak(User $user): int
    {
        $recentBets = DB::table('campaign_bets')
            ->join('campaigns', 'campaign_bets.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.user_id', $user->id)
            ->orderBy('campaign_bets.created_at', 'desc')
            ->limit(50)
            ->select('campaign_bets.*')
            ->get();

        $winStreak = 0;
        foreach ($recentBets as $bet) {
            if ($bet->is_win) {
                $winStreak++;
            } else {
                break;
            }
        }

        return $winStreak;
    }

    protected function isDailyLossLimitExceeded(Campaign $campaign, array $context): bool
    {
        $dailyLossLimit = $campaign->max_loss_per_day;

        if (!$dailyLossLimit) {
            return false;
        }

        return $context['daily_loss'] >= $dailyLossLimit;
    }

    protected function isConsecutiveLossLimitExceeded(Campaign $campaign, array $context): bool
    {
        // Default consecutive loss limit
        $consecutiveLossLimit = $campaign->strategy_config['max_consecutive_losses'] ?? 10;

        return $context['consecutive_losses'] >= $consecutiveLossLimit;
    }

    protected function isBalanceThresholdBreached(Campaign $campaign, array $context): bool
    {
        $stopLossAmount = $campaign->stop_loss_amount;

        if (!$stopLossAmount) {
            return false;
        }

        $currentLoss = $campaign->initial_balance - $campaign->current_balance;
        return $currentLoss >= $stopLossAmount;
    }

    protected function triggerRiskRule(RiskManagementRule $rule, array $context): void
    {
        Log::warning("Risk rule triggered: {$rule->rule_name} for user {$rule->user_id}");

        $results = $rule->trigger($context);

        foreach ($results as $result) {
            Log::info("Risk action executed: " . json_encode($result));
        }
    }

    protected function pauseCampaignForRisk(Campaign $campaign, string $reason): void
    {
        $campaign->update([
            'status' => 'paused_by_risk',
            'notes' => ($campaign->notes ?? '') . "\n[" . now() . "] Auto-paused: {$reason}"
        ]);

        Log::warning("Campaign {$campaign->id} paused for risk: {$reason}");
    }

    // Risk rule management methods
    public function createRiskRule(User $user, array $ruleData): RiskManagementRule
    {
        return $user->riskRules()->create($ruleData);
    }

    public function getDefaultRiskRules(): array
    {
        return [
            [
                'rule_name' => 'Daily Loss Limit',
                'rule_type' => 'daily_loss_limit',
                'conditions' => [
                    ['type' => 'daily_loss_amount', 'value' => 500000] // 500k VND
                ],
                'actions' => [
                    ['type' => 'pause_campaigns', 'params' => []],
                    ['type' => 'send_notification', 'params' => ['message' => 'Daily loss limit reached']]
                ],
                'is_active' => true,
                'is_global' => true,
                'threshold_amount' => 500000,
                'time_window_hours' => 24
            ],
            [
                'rule_name' => 'Consecutive Loss Protection',
                'rule_type' => 'consecutive_loss_limit',
                'conditions' => [
                    ['type' => 'consecutive_losses', 'value' => 10]
                ],
                'actions' => [
                    ['type' => 'reduce_bet_amounts', 'params' => ['reduction_percent' => 50]],
                    ['type' => 'send_notification', 'params' => ['message' => 'Too many consecutive losses']]
                ],
                'is_active' => true,
                'is_global' => true,
                'threshold_count' => 10
            ],
            [
                'rule_name' => 'Balance Protection',
                'rule_type' => 'balance_threshold',
                'conditions' => [
                    ['type' => 'balance_threshold', 'value' => 100000] // 100k VND minimum
                ],
                'actions' => [
                    ['type' => 'pause_campaigns', 'params' => []],
                    ['type' => 'send_notification', 'params' => ['message' => 'Low balance alert']]
                ],
                'is_active' => true,
                'is_global' => true,
                'threshold_amount' => 100000
            ]
        ];
    }

    public function setupDefaultRiskRules(User $user): void
    {
        $defaultRules = $this->getDefaultRiskRules();

        foreach ($defaultRules as $ruleData) {
            $this->createRiskRule($user, $ruleData);
        }
    }
}
