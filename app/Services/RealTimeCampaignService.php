<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignAutoRule;
use App\Models\LotteryResult;
use App\Models\CampaignBet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RealTimeCampaignService
{
    protected $walletService;
    protected $riskManagementService;

    public function __construct(WalletService $walletService, RiskManagementService $riskManagementService)
    {
        $this->walletService = $walletService;
        $this->riskManagementService = $riskManagementService;
    }

    public function processActiveCampaigns()
    {
        $activeCampaigns = Campaign::where('status', 'active')
            ->whereIn('campaign_type', ['live', 'auto'])
            ->with(['autoRules' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        foreach ($activeCampaigns as $campaign) {
            $this->processCampaign($campaign);
        }
    }

    public function processCampaign(Campaign $campaign)
    {
        try {
            // Check if campaign should be processed today
            if (!$this->shouldProcessToday($campaign)) {
                return;
            }

            // Check risk management rules first
            if (!$this->riskManagementService->checkCampaignRisk($campaign)) {
                Log::info("Campaign {$campaign->id} stopped by risk management");
                return;
            }

            // Process auto rules
            $this->processAutoRules($campaign);

        } catch (\Exception $e) {
            Log::error("Error processing campaign {$campaign->id}: " . $e->getMessage());
        }
    }

    protected function shouldProcessToday(Campaign $campaign): bool
    {
        // Check if today is within campaign period
        $today = now()->startOfDay();

        if ($campaign->start_date && $today < $campaign->start_date->startOfDay()) {
            return false;
        }

        if ($campaign->end_date && $today > $campaign->end_date->endOfDay()) {
            // Campaign ended, mark as completed
            $campaign->update(['status' => 'completed']);
            return false;
        }

        return true;
    }

    protected function processAutoRules(Campaign $campaign)
    {
        $autoRules = $campaign->autoRules()
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($autoRules as $rule) {
            if ($rule->canExecute()) {
                $this->executeAutoRule($campaign, $rule);
            }
        }
    }

    protected function executeAutoRule(Campaign $campaign, CampaignAutoRule $rule)
    {
        // Get context for rule evaluation
        $context = $this->buildRuleContext($campaign);

        // Check if rule conditions are met
        if (!$rule->evaluateConditions($context)) {
            return;
        }

        Log::info("Executing auto rule {$rule->id} for campaign {$campaign->id}");

        // Execute the rule actions
        $this->executeRuleActions($campaign, $rule, $context);

        // Mark rule as executed
        $rule->markExecuted();
    }

    protected function buildRuleContext(Campaign $campaign): array
    {
        $today = now()->startOfDay();

        // Get latest lottery results
        $latestResult = LotteryResult::whereDate('date', $today)
            ->orWhereDate('date', $today->copy()->subDay())
            ->orderBy('date', 'desc')
            ->first();

        // Get campaign performance data
        $todayBets = $campaign->bets()->whereDate('created_at', $today)->get();
        $recentBets = $campaign->bets()->where('created_at', '>=', $today->copy()->subDays(7))->get();

        // Get heatmap data if available
        $heatmapData = $this->getHeatmapData($today);

        // Get streak data
        $streakData = $this->getStreakData($today);

        return [
            'campaign' => $campaign,
            'today' => $today,
            'latest_result' => $latestResult,
            'today_bets' => $todayBets,
            'recent_bets' => $recentBets,
            'today_profit' => $todayBets->sum(function ($bet) {
                return $bet->is_win ? $bet->win_amount - $bet->amount : -$bet->amount;
            }),
            'recent_win_rate' => $recentBets->count() > 0 ? $recentBets->where('is_win', true)->count() / $recentBets->count() * 100 : 0,
            'consecutive_losses' => $this->getConsecutiveLosses($campaign),
            'heatmap_data' => $heatmapData,
            'streak_data' => $streakData,
            'current_balance' => $campaign->current_balance,
            'user_wallet' => $campaign->user->wallet
        ];
    }

    protected function executeRuleActions(Campaign $campaign, CampaignAutoRule $rule, array $context)
    {
        foreach ($rule->execution_actions as $action) {
            $this->executeAction($campaign, $action, $context);
        }
    }

    protected function executeAction(Campaign $campaign, array $action, array $context)
    {
        switch ($action['type']) {
            case 'place_bet':
                $this->placeBet($campaign, $action, $context);
                break;
            case 'adjust_bet_amount':
                $this->adjustBetAmount($campaign, $action, $context);
                break;
            case 'pause_campaign':
                $this->pauseCampaign($campaign, $action);
                break;
            case 'send_notification':
                $this->sendNotification($campaign, $action, $context);
                break;
        }
    }

    protected function placeBet(Campaign $campaign, array $action, array $context)
    {
        $strategy = $action['strategy'] ?? 'manual';
        $params = $action['params'] ?? [];

        switch ($strategy) {
            case 'heatmap':
                $this->placeBetByHeatmap($campaign, $params, $context);
                break;
            case 'streak':
                $this->placeBetByStreak($campaign, $params, $context);
                break;
            case 'pattern':
                $this->placeBetByPattern($campaign, $params, $context);
                break;
            case 'manual':
                $this->placeBetManual($campaign, $params, $context);
                break;
        }
    }

    protected function placeBetByHeatmap(Campaign $campaign, array $params, array $context)
    {
        $minHeatScore = $params['min_heat_score'] ?? 70;
        $maxNumbers = $params['max_numbers'] ?? 3;
        $baseAmount = $params['base_amount'] ?? 10000;

        $heatmapData = $context['heatmap_data'] ?? [];
        $hotNumbers = array_filter($heatmapData, function ($data) use ($minHeatScore) {
            return $data['score'] >= $minHeatScore;
        });

        // Sort by score and take top numbers
        usort($hotNumbers, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $numbersToBook = array_slice($hotNumbers, 0, $maxNumbers);

        foreach ($numbersToBook as $numberData) {
            $this->createBet($campaign, $numberData['number'], $baseAmount, 'heatmap_auto');
        }
    }

    protected function placeBetByStreak(Campaign $campaign, array $params, array $context)
    {
        $minStreakDays = $params['min_streak_days'] ?? 7;
        $maxNumbers = $params['max_numbers'] ?? 2;
        $baseAmount = $params['base_amount'] ?? 10000;
        $multiplierPerDay = $params['multiplier_per_day'] ?? 0.1;

        $streakData = $context['streak_data'] ?? [];
        $streakNumbers = array_filter($streakData, function ($data) use ($minStreakDays) {
            return $data['days'] >= $minStreakDays;
        });

        // Sort by streak length
        usort($streakNumbers, function ($a, $b) {
            return $b['days'] <=> $a['days'];
        });

        $numbersToBook = array_slice($streakNumbers, 0, $maxNumbers);

        foreach ($numbersToBook as $numberData) {
            $multiplier = 1 + ($numberData['days'] * $multiplierPerDay);
            $amount = $baseAmount * $multiplier;
            $this->createBet($campaign, $numberData['number'], $amount, 'streak_auto');
        }
    }

    protected function createBet(Campaign $campaign, string $number, float $amount, string $source)
    {
        try {
            // Check wallet balance
            $wallet = $campaign->user->wallet;
            if ($wallet->usable_balance < $amount) {
                Log::warning("Insufficient balance for campaign {$campaign->id} bet: {$amount}");
                return;
            }

            // Deduct from wallet
            $this->walletService->deductForBetting($campaign->user_id, $amount, $campaign->id);

            // Create bet record
            $bet = CampaignBet::create([
                'campaign_id' => $campaign->id,
                'lo_number' => $number,
                'amount' => $amount,
                'date' => now()->toDateString(),
                'source' => $source,
                'is_win' => false, // Will be updated when results are available
                'win_amount' => 0
            ]);

            // Update campaign stats
            $campaign->increment('total_bet_amount', $amount);
            $campaign->increment('total_bet_count');
            $campaign->update(['last_bet_at' => now()]);

            Log::info("Auto bet placed: Campaign {$campaign->id}, Number {$number}, Amount {$amount}");

        } catch (\Exception $e) {
            Log::error("Failed to create bet for campaign {$campaign->id}: " . $e->getMessage());
        }
    }

    protected function getConsecutiveLosses(Campaign $campaign): int
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

    protected function getHeatmapData($date): array
    {
        // This would integrate with your existing heatmap system
        // For now, return mock data
        return [
            ['number' => '15', 'score' => 85],
            ['number' => '27', 'score' => 78],
            ['number' => '38', 'score' => 72],
            ['number' => '49', 'score' => 69],
        ];
    }

    protected function getStreakData($date): array
    {
        // This would integrate with your existing streak analysis
        // For now, return mock data
        return [
            ['number' => '12', 'days' => 15],
            ['number' => '34', 'days' => 12],
            ['number' => '67', 'days' => 8],
        ];
    }

    protected function pauseCampaign(Campaign $campaign, array $action)
    {
        $reason = $action['reason'] ?? 'Auto-paused by rule';
        $campaign->update([
            'status' => 'paused',
            'notes' => ($campaign->notes ?? '') . "\n" . "[" . now() . "] " . $reason
        ]);

        Log::info("Campaign {$campaign->id} paused: {$reason}");
    }

    protected function sendNotification(Campaign $campaign, array $action, array $context)
    {
        $message = $action['message'] ?? 'Campaign notification';
        // Integrate with notification system
        Log::info("Campaign {$campaign->id} notification: {$message}");
    }
}
