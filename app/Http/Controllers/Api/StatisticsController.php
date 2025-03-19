<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LotteryBetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatisticsController extends Controller
{
    protected $betService;

    public function __construct(LotteryBetService $betService)
    {
        $this->betService = $betService;
    }

    public function index()
    {
        return response()->json($this->betService->getBetStatistics(Auth::id()));
    }

    public function betHistory()
    {
        return response()->json($this->betService->getUserBetHistory(Auth::id(), 30));
    }

    public function trends()
    {
        $days = request('days', 7);
        $history = $this->betService->getUserBetHistory(Auth::id(), $days);

        $trends = $history->groupBy(function($bet) {
            return $bet->bet_date->format('Y-m-d');
        })->map(function($dayBets) {
            $total = $dayBets->count();
            $wins = $dayBets->where('is_win', true)->count();
            return [
                'win_rate' => $total > 0 ? ($wins / $total) * 100 : 0,
                'total_bets' => $total
            ];
        });

        return response()->json($trends);
    }
}
