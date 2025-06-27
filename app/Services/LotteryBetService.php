<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignBet;
use App\Models\LotteryResult;
use App\Contracts\LotteryBetServiceInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class LotteryBetService
{
    public function createCampaign($userId, array $data)
    {
        return DB::transaction(function () use ($userId, $data) {
            return Campaign::create([
                'user_id' => $userId,
                'start_date' => $data['start_date'],
                'days' => $data['days'],
                'initial_balance' => $data['initial_balance'],
                'current_balance' => $data['initial_balance'],
                'bet_type' => $data['bet_type'],
                'status' => 'active'
            ]);
        });
    }

    public function placeCampaignBet($campaignId, array $betData)
    {
        return DB::transaction(function () use ($campaignId, $betData) {
            $campaign = Campaign::findOrFail($campaignId);

            // Calculate total bet amount
            $amount = $betData['points'] * 23;

            // Check if campaign has enough balance
            if ($campaign->current_balance < $amount) {
                throw new Exception('Insufficient balance');
            }

            // Create bet
            $bet = CampaignBet::create([
                'campaign_id' => $campaignId,
                'lo_number' => $betData['lo_number'],
                'points' => $betData['points'],
                'amount' => $amount,
                'bet_date' => $betData['bet_date'],
                'status' => 'pending'
            ]);

            // Update campaign balance
            $campaign->current_balance -= $amount;
            $campaign->save();

            return $bet;
        });
    }

    public function processCampaignResults($date)
    {
        $result = LotteryResult::where('draw_date', $date)->first();
        if (!$result) return;

        $bets = CampaignBet::where('bet_date', $date)
            ->where('status', 'pending')
            ->get();

        foreach ($bets as $bet) {
            if (in_array($bet->lo_number, $result->lo_array)) {
                $winAmount = $bet->amount * 80;

                DB::transaction(function () use ($bet, $winAmount) {
                    // Update bet
                    $bet->update([
                        'is_win' => true,
                        'win_amount' => $winAmount,
                        'status' => 'completed'
                    ]);

                    // Update campaign balance
                    $bet->campaign->increment('current_balance', $winAmount);
                });
            } else {
                $bet->update(['status' => 'completed']);
            }
        }

        // Update campaigns last_updated
        Campaign::whereHas('bets', function ($query) use ($date) {
            $query->where('bet_date', $date);
        })->update(['last_updated' => $date]);
    }

    public function checkCompletedCampaigns()
    {
        $today = Carbon::today();

        Campaign::where('status', 'active')
            ->where('start_date', '<=', $today)
            ->whereRaw('DATE_ADD(start_date, INTERVAL days DAY) < ?', [$today])
            ->update(['status' => 'completed']);
    }

    public function getBetStatistics($userId)
    {
        $campaigns = Campaign::where('user_id', $userId)->get();

        $totalCampaigns = $campaigns->count();
        $activeCampaigns = $campaigns->where('status', 'active')->count();
        $completedCampaigns = $campaigns->where('status', 'completed')->count();

        $totalBets = CampaignBet::whereIn('campaign_id', $campaigns->pluck('id'))->count();
        $winningBets = CampaignBet::whereIn('campaign_id', $campaigns->pluck('id'))
            ->where('is_win', true)->count();

        $totalDeposited = $campaigns->sum('initial_balance');
        $currentBalance = $campaigns->sum('current_balance');
        $totalWinnings = CampaignBet::whereIn('campaign_id', $campaigns->pluck('id'))
            ->where('is_win', true)->sum('win_amount');

        $winRate = $totalBets > 0 ? round(($winningBets / $totalBets) * 100, 2) : 0;
        $profitLoss = $currentBalance - $totalDeposited + $totalWinnings;

        return [
            'total_campaigns' => $totalCampaigns,
            'active_campaigns' => $activeCampaigns,
            'completed_campaigns' => $completedCampaigns,
            'total_bets' => $totalBets,
            'winning_bets' => $winningBets,
            'win_rate' => $winRate,
            'total_deposited' => $totalDeposited,
            'current_balance' => $currentBalance,
            'total_winnings' => $totalWinnings,
            'profit_loss' => $profitLoss,
        ];
    }

    public function getUserBetHistory($userId, $days = 30)
    {
        $campaigns = Campaign::where('user_id', $userId)->pluck('id');

        return CampaignBet::whereIn('campaign_id', $campaigns)
            ->with(['campaign'])
            ->where('bet_date', '>=', Carbon::now()->subDays($days))
            ->orderBy('bet_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }
}
