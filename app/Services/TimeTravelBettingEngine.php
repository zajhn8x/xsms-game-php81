<?php

namespace App\Services;

use App\Models\HistoricalBet;
use App\Models\LotteryResult;
use Carbon\Carbon;

class TimeTravelBettingEngine
{
    public function processDay($campaign, $currentDate)
    {
        // Get lottery results for this date
        $lotteryResults = $this->getLotteryResults($currentDate);

        if (empty($lotteryResults)) {
            return ['profit' => 0, 'bets' => []];
        }

        // Apply betting strategy
        $betsToPlace = $this->applyBettingStrategy($campaign, $currentDate);

        if (empty($betsToPlace)) {
            return ['profit' => 0, 'bets' => []];
        }

        // Check results and record bets
        $dayProfit = 0;
        $placedBets = [];

        foreach ($betsToPlace as $bet) {
            $isWin = $this->checkBetResult($bet, $lotteryResults);
            $winAmount = $isWin ? $bet['amount'] * 80 : 0; // Assuming 1:80 payout
            $profit = $isWin ? $winAmount - $bet['amount'] : -$bet['amount'];

            $dayProfit += $profit;

            // Record the bet
            $placedBet = HistoricalBet::create([
                'historical_campaign_id' => $campaign->id,
                'bet_date' => $currentDate,
                'lo_number' => $bet['number'],
                'amount' => $bet['amount'],
                'win_amount' => $winAmount,
                'is_win' => $isWin,
                'balance_before' => $campaign->final_balance,
                'balance_after' => $campaign->final_balance + $profit,
                'notes' => $bet['notes'] ?? null
            ]);

            $placedBets[] = $placedBet;
        }

        return [
            'profit' => $dayProfit,
            'bets' => $placedBets
        ];
    }

    public function applyBettingStrategy($campaign, $currentDate)
    {
        switch ($campaign->betting_strategy) {
            case 'manual':
                return $this->manualStrategy($campaign, $currentDate);
            case 'auto_heatmap':
                return $this->heatmapStrategy($campaign, $currentDate);
            case 'auto_streak':
                return $this->streakStrategy($campaign, $currentDate);
            default:
                return [];
        }
    }

    private function manualStrategy($campaign, $currentDate)
    {
        $config = $campaign->strategy_config ?? [];
        $betsToPlace = [];

        // Sử dụng target_numbers từ config hoặc random numbers
        $targetNumbers = $config['target_numbers'] ?? [];
        $betAmount = $config['bet_amount'] ?? 10000;
        $maxDailyBets = $config['max_daily_bets'] ?? 3;

        if (empty($targetNumbers)) {
            // Nếu không có target numbers, tạo random
            $targetNumbers = [];
            for ($i = 0; $i < $maxDailyBets; $i++) {
                $targetNumbers[] = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
            }
        }

        // Giới hạn số lần đặt hàng ngày
        $numbersTobet = array_slice($targetNumbers, 0, $maxDailyBets);

        foreach ($numbersTobet as $number) {
            $betsToPlace[] = [
                'number' => $number,
                'amount' => $betAmount,
                'notes' => 'Manual strategy bet - ' . $number
            ];
        }

        return $betsToPlace;
    }

    private function heatmapStrategy($campaign, $currentDate)
    {
        $config = $campaign->strategy_config;
        $betsToPlace = [];

        // Get numbers with high heat scores
        $hotNumbers = $this->getHotNumbers($currentDate, $config['min_heat_score'] ?? 70);

        // Limit to max numbers per day
        $maxNumbers = $config['max_numbers_per_day'] ?? 3;
        $hotNumbers = array_slice($hotNumbers, 0, $maxNumbers);

        foreach ($hotNumbers as $number) {
            $betsToPlace[] = [
                'number' => $number,
                'amount' => $config['base_bet_amount'] ?? 10000,
                'notes' => 'Heatmap strategy bet'
            ];
        }

        return $betsToPlace;
    }

    private function streakStrategy($campaign, $currentDate)
    {
        $config = $campaign->strategy_config;
        $betsToPlace = [];

        // Get numbers with long streaks
        $streakNumbers = $this->getStreakNumbers($currentDate, $config);

        foreach ($streakNumbers as $number => $streakDays) {
            $multiplier = min($streakDays / 10, 5); // Cap at 5x
            $amount = ($config['base_bet_amount'] ?? 10000) * $multiplier;

            $betsToPlace[] = [
                'number' => $number,
                'amount' => $amount,
                'notes' => "Streak strategy bet - {$streakDays} days streak"
            ];
        }

        return $betsToPlace;
    }

    private function checkBetResult($bet, $lotteryResults)
    {
        // Check if the bet number appears in lottery results lo_array
        return in_array($bet['number'], $lotteryResults);
    }

    private function getLotteryResults($date)
    {
        $result = LotteryResult::whereDate('draw_date', $date)->first();

        if (!$result) {
            return [];
        }

        // Trả về lo_array - danh sách các số lô 2 chữ số
        return $result->lo_array ?? [];
    }

    private function getHotNumbers($date, $minHeatScore)
    {
        // This would integrate with your heatmap system
        // For now, return some mock hot numbers
        return ['15', '27', '38', '49', '56'];
    }

    private function getStreakNumbers($date, $config)
    {
        $minStreak = $config['min_streak_days'] ?? 7;
        $maxStreak = $config['max_streak_days'] ?? 30;

        // This would integrate with your streak analysis system
        // For now, return some mock streak numbers
        return [
            '12' => 15, // 15 days streak
            '34' => 22, // 22 days streak
            '67' => 8   // 8 days streak
        ];
    }
}
