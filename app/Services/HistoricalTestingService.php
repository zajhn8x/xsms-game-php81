<?php

namespace App\Services;

use App\Models\HistoricalCampaign;
use App\Models\HistoricalBet;
use App\Models\LotteryResult;
use Illuminate\Support\Facades\DB;

class HistoricalTestingService
{
    public function createTestCampaign($userId, $config)
    {
        // Set default dates if not provided
        if (empty($config['data_start_date'])) {
            $config['data_start_date'] = $config['test_start_date'];
        }
        if (empty($config['data_end_date'])) {
            $config['data_end_date'] = $config['test_end_date'];
        }

        return HistoricalCampaign::create([
            'user_id' => $userId,
            'name' => $config['name'],
            'description' => $config['description'] ?? null,
            'test_start_date' => $config['test_start_date'],
            'test_end_date' => $config['test_end_date'],
            'data_start_date' => $config['data_start_date'],
            'data_end_date' => $config['data_end_date'],
            'initial_balance' => $config['initial_balance'],
            'final_balance' => 0,
            'betting_strategy' => $config['betting_strategy'],
            'strategy_config' => $config['strategy_config'] ?? null,
            'status' => 'pending'
        ]);
    }

    public function runHistoricalTest($campaignId)
    {
        $campaign = HistoricalCampaign::findOrFail($campaignId);
        $campaign->update(['status' => 'running']);

        try {
            $currentDate = $campaign->test_start_date->copy();
            $currentBalance = $campaign->initial_balance;

            while ($currentDate <= $campaign->test_end_date) {
                // Skip days without lottery results
                if (!$this->hasLotteryResult($currentDate)) {
                    $currentDate = $currentDate->addDay();
                    continue;
                }

                // Simulate betting for this day
                $dayResults = $this->simulateDailyBetting($campaign, $currentDate);
                $currentBalance += $dayResults['profit'];

                // Check stop conditions (out of money, reached target)
                if ($currentBalance <= 0) {
                    break;
                }

                $currentDate = $currentDate->addDay();
            }

            $campaign->update([
                'final_balance' => $currentBalance,
                'status' => 'completed'
            ]);

        } catch (\Exception $e) {
            $campaign->update(['status' => 'failed']);
            throw $e;
        }

        return $campaign;
    }

    private function simulateDailyBetting($campaign, $date)
    {
        $engine = app(TimeTravelBettingEngine::class);
        return $engine->processDay($campaign, $date);
    }

    private function hasLotteryResult($date)
    {
        return LotteryResult::whereDate('draw_date', $date)->exists();
    }

    public function getBetHistory($campaignId)
    {
        return HistoricalBet::where('historical_campaign_id', $campaignId)
            ->orderBy('bet_date', 'desc')
            ->get();
    }

    public function calculateResults($campaign)
    {
        $bets = $campaign->bets;

        return [
            'total_bets' => $bets->count(),
            'win_bets' => $bets->where('is_win', true)->count(),
            'win_rate' => $campaign->win_rate,
            'total_bet_amount' => $bets->sum('amount'),
            'total_win_amount' => $bets->sum('win_amount'),
            'profit' => $campaign->profit,
            'profit_percentage' => $campaign->profit_percentage,
            'duration_days' => $campaign->duration
        ];
    }
}
