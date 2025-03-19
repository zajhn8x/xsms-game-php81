<?php

namespace App\Repositories;

use App\Models\LotteryBet;
use App\Contracts\Repositories\LotteryBetRepositoryInterface;
use Carbon\Carbon;

class LotteryBetRepository implements LotteryBetRepositoryInterface
{
    public function getUserBets($userId)
    {
        return LotteryBet::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data)
    {
        return LotteryBet::create($data);
    }

    public function update($id, array $data)
    {
        $bet = LotteryBet::findOrFail($id);
        $bet->update($data);
        return $bet;
    }

    public function delete($id)
    {
        return LotteryBet::destroy($id);
    }

    public function findByDateRange($userId, $startDate, $endDate)
    {
        return LotteryBet::where('user_id', $userId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate),
                Carbon::parse($endDate)
            ])->get();
    }
}