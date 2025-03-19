<?php

namespace App\Services;

use App\Models\LotteryBet;
use App\Models\LotteryResult;
use Carbon\Carbon;

class LotteryBetService
{
    public function placeBet($userId, $betData)
    {
        $bet = LotteryBet::create([
            'user_id' => $userId,
            'bet_date' => Carbon::now(),
            'lo_number' => $betData['lo_number'],
            'amount' => $betData['amount'],
            'is_win' => false,
            'win_amount' => 0
        ]);

        return $bet;
    }

    public function processWinnings($resultId)
    {
        $result = LotteryResult::findOrFail($resultId);
        $bets = LotteryBet::where('bet_date', $result->draw_date)
            ->where('is_win', false)
            ->get();

        foreach ($bets as $bet) {
            if (in_array($bet->lo_number, $result->lo_array)) {
                $winAmount = $bet->amount * 80; // Tỉ lệ cược 1:80
                $bet->update([
                    'is_win' => true,
                    'win_amount' => $winAmount
                ]);
            }
        }
    }

    public function getUserBetHistory($userId, $days = 30)
    {
        return LotteryBet::where('user_id', $userId)
            ->where('bet_date', '>=', Carbon::now()->subDays($days))
            ->orderBy('bet_date', 'desc')
            ->get();
    }

    public function getBetStatistics($userId)
    {
        $bets = LotteryBet::where('user_id', $userId)->get();
        
        return [
            'total_bets' => $bets->count(),
            'total_amount_bet' => $bets->sum('amount'),
            'total_wins' => $bets->where('is_win', true)->count(),
            'total_winnings' => $bets->sum('win_amount'),
            'net_profit' => $bets->sum('win_amount') - $bets->sum('amount')
        ];
    }
}