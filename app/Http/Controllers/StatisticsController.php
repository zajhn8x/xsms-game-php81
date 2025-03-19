
<?php

namespace App\Http\Controllers;

use App\Services\LotteryBetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatisticsController extends Controller
{
    protected $betService;

    public function __construct(LotteryBetService $betService)
    {
        $this->betService = $betService;
        $this->middleware('auth');
    }

    public function index()
    {
        $statistics = $this->betService->getBetStatistics(Auth::id());
        $history = $this->betService->getUserBetHistory(Auth::id(), 30);
        
        return view('statistics.index', [
            'statistics' => $statistics,
            'history' => $history
        ]);
    }
}
